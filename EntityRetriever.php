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
	// в этом массиве хранятся запросы, которые необходимо выполнить. они хранятся в виде массивов, согласно спецификации EntityDBOperator.
	// запросы всегда обращаются к одной таблице по uniID и поэтому отсортированы по таблицам. когда предлагается сделать запрос нового uniID к той же таблице, он просто добавляется в массив where.
	
	public static $data=array();
	// здесь хранятся данные. ретривер не стирает их до самого конца прогона программы, чтобы не запоминать, сколько объектов запросили данные и когда они уже не понадобятся. в любом случае копии массивов в php хранятся как один экземпляр в памяти, пока не будет внесено изменение.
	
	public static $compiled_tables=array();
	// это массив соответствий "идентификатор => список таблиц", который запоминает, какие таблицы хотел какой объект.
	
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
	}
	
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
			if ((is_array(static::$data[$table]))&&(array_key_exists($id, static::$data[$table]))) return;
			if ((is_array(static::$queries[$table]))&&(in_array($id, static::$queries[$table], 1))) return;
			static::$queries[$table][]=$id;
		}
	}
	
	public static function get_input($who, $storage_rules='') // этой командой объект запрашивает своё содержимое массива $data в форме, совместимой с функцией do_input. эта команда значит "данные нужны сейчас!".
	{
		$compiled=static::compile_tables($who, $storage_rules);
		$retrieve=array_intersect(array_keys($compiled), array_keys(static::$queries));
		$db=parent::$db;
		
		foreach ($retrieve as $table)
		{
			$ids=static::$queries[$table];
			
			$query=array(
				'action'=>'select',
				'table'=>$table,
				'where'=>array('uniID'=>$ids),
			);
			
			$query=static::compose_query($query);
			echo $query.'<br>'; // DEBUG
			$list=$db::query($query);
			
			while ($row=$db::fetch($list))
			{
				static::$data[$table][$row['uniID']]=$row;
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
		return $result;
	}
	
	// эта функция получает список таблиц, данные из которых хочет объект. из всех этих таблиц должны быть извлечены записи, имеющие uniID=$uni того или иного объекта. этот список нельзя получить более простым способом потому, что есть четыре типа хранения данных.
	public static function compile_tables($who, $storage_rules='', &$compiled=null)
	{
		if (is_null($compiled)) // не рекурсивный вызов. при рекурсивном эта переменная - массив.
		{
			if (array_key_exists($who->id, static::$compiled_tables)) return static::$compiled_tables[$who->id]; // список таблиц уже был получен, возвращае его.
			$recursive=false;
			$compiled=array();
		}
		else $recursive=true;
		
		if ($storage_rules==='') $rules=$who->rules['storage'];
		if ($storage_rules['method']=='uni')
		{
			if ($who->uni<1) return; // STUB - здесь должна быть обработка ошибки. если мы не знаем идентификатора, то не можем получить данные.
			if ($storage_rules['uni_combo']) // традиционный способ хранить данные - весь набор (или большая часть) в одной таблице в виде полей.
			{
				$table=static::$prefix.static::get_entity_table($who, $storage_rules);
				$compiled[$table][]=$who->uni;
			}
			else
			{
				$table=static::$prefix.'entities';
				$compiled[$table][]=$who->uni;
				
				if ($who instanceof Entity_value)
				{
					// значения, являющиеся самостоятельными сущностями в таблице сущностей, держат свои данные в дополнительной таблице.
					$table=static::$prefix.static::get_entity_table($who, $storage_rules);
					$compiled[$table][]=$who->uni;
					// "чистая" сущность-комбинация, созданная по новому методу, не имеет данных сама по себе. внутренние сущности - её данные.	
				}
				// STUB: тут ещё не учитываются сущности-ссылки.
			}
		}
		elseif ($who instanceof Entity_value) // не имеет уникального идентификатора и представляет из себя поле в другой таблице.
		{
			$table=static::$prefix.static::get_entity_table($who, $storage_rules);
			$compiled[$table][]='master';
		}
		
		// все комбинации также должны опросить свои внутренние сущности.
		if ($who instanceof Entity_combo)
		{
			foreach ($who->byrole as $role=>$list)
			{
				$entity_storage_rules=$who->model[$role]['storage'];
				foreach ($list as $entity)
				{
					static::compile_tables($entity, $entity_storage_rules, $compiled); // рекурсивный вызов - массив $compiled передаётся по ссылке и дополняется.
					
					if ($storage_rules['method']=='uni')
					{
						foreach ($compiled as $table=>&$ids)
						{
							$i=array_search('master', $ids);
							if ($i!==false) $ids[$i]=$who->uni;
						}
					}
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