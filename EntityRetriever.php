<?
// этот класс должен собирать запросы на данные и выполнять их скопом по мере надобности, чтобы уменьшить число запросов к БД. например, если странице нужны данные 20 неофитов с известными идентификаторами, то вместо 20 запросов "неофит с таким-то идентификатором" этот класс должен выполнить запрос "неофиты с идентификаторами 1, 2, 3...". далее объекты неофитов сами разбирают данные.
// следовательно, нас есть следующие этапы:
// 1. объекты говорят ретриверу, что им понадобятся такие-то данные. ретривер запоминает.
// 2. объект говорит ретриверу: не могу больше терпеть! данные нужны сейчас! рертривер выполняет запрос и получает как можно больше данных с помощью одного запроса.
// 3. объект, потребовавший данные, берёт их из ретривера. когда подходит очередь срочной нужды в данных других объектов, то они делают то же (потому что данные уже были получены).
// кроме того, ретривер должен грамотно получать сопутствующие (связанные) данные. к примеру, атаки неофита, хотя они хранятся в другой таблице, комментарии... причём он должен различать, когда комментарии нужны (при показе страницы), а когда - нет (при скрытых операциях с неофитом).

class EntityRetriever extends EntityDBOperator
{
	public static $queries=array();
	// в этом массиве хранятся пары "таблица => список идентификаторов". это очередь на получение данных.
	
	public static $compiled_tables=array();
	
	public static $link_queries=array();
	/* массив идентификаторов сущностей, связи которых нужно получить. существует в виде пар:
	
		"уник. идентификатор => массив('A'=>типы связи, 'B'=>типы связи)". если в A и B списки совпадают, php всё равно экономит место и хранит их как один массив.
		
		или:
		
		"уник. идентификатор => 'all' - все связи.
	*/
	// STUB: нужно как-то регулирвать, какие связи получать, а какие игнорировать. например, не всегда нужен список комментов или, например, данные об атаках.
	// возможно, стоит хранить не массив идентификаторов, а массив ссылок на сущности? тогда их можно опрашивать, однако не сделаешь простого implode.
	
	public static $data=array();
	// здесь хранятся данные. ретривер не стирает их до самого конца прогона программы, чтобы не запоминать, сколько объектов запросили данные и когда они уже не понадобятся. в любом случае копии массивов в php хранятся как один экземпляр в памяти, пока не будет внесено изменение.
	
	public static $links=array();
	/* здесь находятся данные о связях в следующем виде:
	
		уник. связанной сущности => массив (
			индекс:тип связи => массив (
				уник. связи => строка из БД
				уник. связи => ...
			)
			индекс:другой тип связи =>...
		)
		уник. другой связанной сущности => ...
	*/
	
	// эта команда сообщает ретриверу, что такому-то объекту понадобятся данные. то, какие именно, ретривер определяет по правилам $rules[storage] этого объекта (или по второму аргументу, если он указан).
	// эту команду должна вызывать только верхняя сущность-комбинация, поскольку именно у неё полные данные о модели, а также именно она спускает данные по do_input. промежуточные комбинации, связи и конечные значения не должны вызывать эту функцию.
	public static function req($who, $storage_rules='')
	{
		if ($storage_rules==='') $storage_rules=$who->rules['storage'];
		
		if ($storage_rules['method']!=='uni') return; // STUB - здесь должна быть обработка ошибки. сущности, не имеющие собственного идентификатора и, следовательно, промежуточные либо хранящиеся как отдельные поля в таблицах, не должны вызывать эту функцию.

		if ($who->uni<1) return; // STUB - здесь должна быть обработка ошибки. если мы не знаем идентификатора, то не можем получить данные.
		// получаем список таблиц, данные из которых хочет этот объект.
		
		$compiled=static::compile_tables($who, $storage_rules);
		
		foreach ($compiled as $table=>$ids)
		{
			static::req_id($table, $ids);
		}
		
		if (array_key_exists('links', $storage_rules))
		{
			static::req_links($who->uni, $storage_rules['links']);
		}
	}
	
	// STUB: требуются функции, которые могут получать списки типа "Выбрать всех неофитов, исправленных за последние 10 дней"; "выбрать всех неофитов психического типа, упорядочить по алфавиту"...
	// возможно, эти функции должны быть отдельным объектом, типа EntityLister.
	
	// эта функция добавляет в очередь запрос, на получение данных из таблицы $table с уникальными идентификаторами $id. для большинства таблиц это только ключ, но для entities_link это ещё uniID1 и uniID2. название таблицы даётся без $db_prefix - он добавляется в последний момент.
	// FIX: сейчас код не различает системных таблиц движка, которые должны быть с префиксом, и заданных жёстко таблиц для совместимости с другими движками, к которым префикс добавлять не надо.
	public static function req_id($table, $id)
	{
		if (is_array($table))
		{
			foreach ($table as $t)
			{
				static::req_id($t, $id);
			}
		}
		elseif (is_array($id))
		{
			foreach ($id as $i)
			{
				static::req_id($table, $i);
			}
		}
		else
		{
			// если данные этого идентификатора из этой таблицы уже были получены, игнорируем.
			if ((is_array(static::$data[$table]))&&(array_key_exists($id, static::$data[$table]))) return;
			
			// если идентификатор уже присутствует в очереди запросов на эту таблицу, игнорируем.
			if ((is_array(static::$queries[$table]))&&(in_array($id, static::$queries[$table], 1))) return;
			
			// всё в порядке, добавляем в очередь.
			static::$queries[$table][]=$id;
		}
	}
	
	public static function req_links($id, $types='all')
	{
		if (is_array($id))
		{
			foreach ($id as $i)
			{
				static::req_links($i, $types);
			}
		}
		else
		{
			if (!array_key_exists($id, static::$link_queries, 1))
			{
				static::$link_queries[$id]=$types;
			}
			elseif ($types==='all')
			{
				if (static::$link_queries[$id]==='all') return;
				static::$link_queries[$id]='all';
			}
			else
			{
				foreach ($types as $index=>$connections)
				{
					if (!array_key_exists($index, static::$link_queries[$id]))
					{
						static::$link_queries[$id][$index]=$connections;
						continue;
					}
					static::$link_queries[$id][$index]=array_merge(static::$link_queries[$id][$index], $connections);
				}
			}
		}
	}
	
	public static function compact_link_queries($query, $uni, &$queries=array() )
	{
		$hash=static::compose_where($query['where'], $query['where_operator']);
		if (array_key_exists($hash, $queries, 1))
		{
			$queries[$hash]['uni'][]=$uni;
		}
		else
		{
			$query['action']='select';
			$query['table']=static::$db_prefix.'entities_link';
			$queries[$hash]['query']=$query;
			$queries[$hash]['uni'][]=$uni;
		}
	}
	
	public static function get_links($who, $storage_rules='')
	{
		// WIP: нужно получить (из кэша?) список связей, требуемых объекту!
		
		$pile=array();		
		foreach (static::$link_queries as $uni=>$types)
		{
			if ($types==='all')
			{
				$query=array(
					'where'=>array('uniID1'=>'%uni%', 'uniID2'=>'%uni%'),
					'where_operator'=>'OR'
				);
				static::compact_link_queries($query, $uni, $pile);
			}
			elseif (is_array($types))
			{
				foreach ($types as $index => $connections)
				{
					$connections=array_unique($connections);
					$query=array(
						'where'=>array(
							(($index==='A')?('uniID1'):('uniID2')) => '%uni%',
							'connection'=>$connections
						)
					);
					static::compact_link_queries($query, $uni, $pile);
				}
			}
		}
		unset (static::$link_queries);
		
		if (count($pile)>0)
		{
			// компонуем похожие запросы.
			$queries=array();
			foreach ($queries as $hash=>$list)
			{
				$uni=$list['uni'];
				$query=$list['query'];
				foreach ($query['where'] as &$condition)
				{
					if ($condition==='%uni%') $condition=$uni;
				}
				$queries[]=$query;
			}
		
			$db=parent::$db;		
			foreach ($queries as $query)
			{
				$query=static::compose_query($query);
				$list=$db::query($query);
				
				while ($row=$db::fetch($list))
				{
					static::$data[$row['uniID']]=$row;
					static::$links[$row['uniID1']]['A:'.$row['connection']][$row['uniID']]=$row;
					static::$links[$row['uniID2']]['B:'.$row['connection']][$row['uniID']]=$row;
				}
			}
		}
	}
	
	public static function retrieve($who, $storage_rules='') // этой командой объект запрашивает своё содержимое массива $data в форме, совместимой с функцией do_input. эта команда значит "данные нужны сейчас!".
	{
		$db=parent::$db;	
		foreach (static::$queries as $table=>$ids)
		{	
			$query=array(
				'action'=>'select',
				'table'=>static::$db_prefix.$table,
				'where'=>array('uniID'=>$ids),
			);
			
			// STUB! в будущем эта часть должна быть реализована умнее, а не просто получать все связи всех мыслимых объектов.
			if ($table=='entities_link')
			{
				$query['where']['uniID1']=$ids;
				$query['where']['uniID2']=$ids;
				$query['where_operator']='OR';
			}
			
			$query=static::compose_query($query);
			echo $query.'<br>'; // DEBUG
			$list=$db::query($query);
			
			while ($row=$db::fetch($list))
			{
				static::$data[$table][$row['uniID']]=$row;
				if (($table=='entity_link')&&($row['connection']=='A is combo parent of B'))
				{
					// WIP
					//static::req(array($row['uniID1'], $row['uniID2']));
				}
			}
		}
		
		$result=array();
		foreach ($compiled as $table=>$ids)
		{
			foreach ($ids as $id)
			{
				$result[$table][$id]=static::$data[$table][$id];
			}
		}
	}
	
	// эта функция получает список таблиц, данные из которых хочет объект. из всех этих таблиц должны быть извлечены записи, имеющие uniID=$uni того или иного объекта. этот список нельзя получить более простым способом потому, что есть четыре типа хранения данных.
	// WIP
	public static function compile_tables($who, $storage_rules='', &$compiled=null)
	{
		if (is_null($compiled)) // не рекурсивный вызов. при рекурсивном эта переменная - массив.
		{
			if (array_key_exists($who->id, static::$compiled_tables)) return static::$compiled_tables[$who->id]; // список таблиц уже был получен, возвращае его.
			$recursive=false;
			$compiled=array();
		}
		else $recursive=true;
		
		if ($storage_rules==='') $storage_rules=$who->rules['storage'];
		if ($storage_rules['method']=='uni')
		{
			if ($who->uni<1) return; // STUB - здесь должна быть обработка ошибки. если мы не знаем идентификатора, то не можем получить данные.
			if ($storage_rules['uni_combo']) // традиционный способ хранить данные - весь набор (или большая часть) в одной таблице в виде полей.
			{
				$table=static::get_entity_table($who, $storage_rules);
				$compiled[$table][]='uni'.$who->id;
			}
			else
			{
				$table='entities';
				$compiled[$table][]='uni'.$who->id;
				
				if ($who instanceof Entity_value)
				{
					// значения, являющиеся самостоятельными сущностями в таблице сущностей, держат свои данные в дополнительной таблице.
					$table=static::get_entity_table($who, $storage_rules);
					$compiled[$table][]='uni'.$who->id;
					// "чистая" сущность-комбинация, созданная по новому методу, не имеет данных сама по себе. внутренние сущности - её данные.	
				}
				elseif ($who instanceof Entity_link)
				{
					$table=static::get_entity_table($who, $storage_rules); // всегда entities_link на самом деле
					$compiled[$table][]='uni'.$who->id;
					$compiled[$table][]='uni'.$who->data['entity1']->id;
					$compiled[$table][]='uni'.$who->data['entity2']->id;
				}				
				// STUB: тут ещё не учитываются сущности-ссылки.
			}
		}
		elseif ($who instanceof Entity_value) // не имеет уникального идентификатора и представляет из себя поле в другой таблице.
		{
			$table=static::get_entity_table($who, $storage_rules);
			if (!is_object($storage_rules['combo_parent'])) var_dump($storage_rules); //var_dump($who->rules['storage']);
			$compiled[$table][]='uni'.$storage_rules['combo_parent']->id;
		}
		
		// все комбинации также должны опросить свои внутренние сущности.
		if ($who instanceof Entity_combo)
		{
			foreach ($who->byrole as $role=>$list)
			{
				foreach ($list as $entity)
				{
					static::compile_tables($entity, '', $compiled); // рекурсивный вызов - массив $compiled передаётся по ссылке и дополняется.
				}
			}
		}
		
		if (!$recursive)
		{
			foreach ($compiled as $table=>&$ids)
			{
				$ids=array_unique($ids);
			}
		
			static::$compiled_tables[$who->id]=$compiled; // сохраняем кэш.
			return $compiled;
		}
	}
}


?>