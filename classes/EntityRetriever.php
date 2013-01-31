<?
// этот класс должен собирать запросы на данные и выполнять их скопом по мере надобности, чтобы уменьшить число запросов к БД. например, если странице нужны данные 20 неофитов с известными идентификаторами, то вместо 20 запросов "неофит с таким-то идентификатором" этот класс должен выполнить запрос "неофиты с идентификаторами 1, 2, 3...". далее объекты неофитов сами разбирают данные.
// следовательно, нас есть следующие этапы:
// 1. объекты говорят ретриверу, что им понадобятся такие-то данные. ретривер запоминает.
// 2. объект говорит ретриверу: не могу больше терпеть! данные нужны сейчас! рертривер выполняет запрос и получает как можно больше данных с помощью одного запроса.
// 3. объект, потребовавший данные, берёт их из ретривера. когда подходит очередь срочной нужды в данных других объектов, то они делают то же (потому что данные уже были получены).
// кроме того, ретривер должен грамотно получать сопутствующие (связанные) данные. к примеру, атаки неофита, хотя они хранятся в другой таблице, комментарии... причём он должен различать, когда комментарии нужны (при показе страницы), а когда - нет (при скрытых операциях с неофитом).

class EntityRetriever extends EntityDBOperator
{
	const MAX_CYCLE=10;
	public static $queue=array();

	public static $queries=array();
	// в этом массиве хранятся пары "таблица => список идентификаторов". это очередь на получение данных.
	
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
	
	public static function received($queue_data)
	{
	// ERR

		$method='receive';
		$ask='';
		if ($queue_data['method']<>'') $method=$queue_data['method'];
		if ($queue_data['ask']<>'') $ask=$queue_data['ask'];
		$entity=$queue_data['entity'];	
		
		debug('clearing queue: '.$entity->id.'->'.$ask.'->'.$method);
		
		if ($ask=='') $entity->$method($queue_data['tables'], $queue_data['context'], $queue_data['args']);
		else $entity->$ask->$method($queue_data['tables'], $queue_data['context'], $queue_data['args']);
	}
	
	public static function req($id, $table='entities', $call=null)
	{
		if (is_array($table))
		{
			$result=array();
			$table=array_unique($table);
			foreach ($table as $t)
			{
				$result[$t]=static::req($t, $id);
			}
			return $result;
		}
		elseif (is_array($id))
		{
			$result=array();
			$id=array_unique($id);
			foreach ($id as $i)
			{
				$result[$i]=static::req($table, $i);
			}
			return $result;
		}
		else
		{
			if (!is_numeric($id))
			{
				$id=static::unize($id);
			}
			if (
				(is_numeric($id)) &&
				(is_array(static::$data[$table])) &&
				(array_key_exists($id, static::$data[$table]))
				)
			{
				if ($who instanceof Entity) $who->receive();
				elseif (EntityFactory::exists($id)) EntityFactory::$entities_by_uni[$id]->receive();
				return static::$data[$table][$id];
			}
			
			static::$queries[$table][]=$id;
			if ($call instanceof Entity)
			{
				$data=array('entity'=>$call, 'tables'=>array($table));
				static::add_to_queue($data);
			}
			elseif (is_array($call))
			{
				$call['tables']=array($table);
				static::add_to_queue($call);
			}
			return false;
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
		elseif ($id instanceof Entity)
		{
			if ($id->uni>0) static::req_links($id->uni, $types);
			else static::req_links('uni'.$id->id, $types);
		}
		else
		{
			if (!is_numeric($id))
			{
				$id=static::unize($id);
			}
			
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
	
	public static function get_links($who)
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
			foreach ($pile as $hash=>$list)
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
					static::$data['entities_link'][$row['uniID']]=$row;
					static::$links[$row['uniID1']]['A:'.$row['connection']][$row['uniID']]=$row;
					static::$links[$row['uniID2']]['B:'.$row['connection']][$row['uniID']]=$row;
					
					$link_row=array('uniID'=>$row['uniID'], 'entity_type'=>'link');
					static::$data['entities'][$row['uniID']]=$link_row;
					$link_entity=Entity::create_from_db($link_row);
					$link_entity->receive();
				}
			}
		}
	}
	
	public static function retrieve($cycle=0) // этой командой объект запрашивает своё содержимое массива $data в форме, совместимой с функцией do_input. эта команда значит "данные нужны сейчас!".
	{
		if ($cycle==0)
		{
			debug ('retrieving all...');
			while (count(static::$queries)>0)
			{
				$cycle++;
				if ($cycle>static::MAX_CYCLE)
				{
					debug ('MAX CYCLE!');
					break;
				}
				static::retrieve($cycle);
			}
			return;
		}
		
		debug ('retrieving...');
		$db=parent::$db;
		foreach (static::$queries as $table=>$ids)
		{	
			$ids=array_unique($ids);
			$query=array(
				'action'=>'select',
				'table'=>static::$db_prefix.$table,
				'where'=>array('uniID'=>$ids),
			);
			
			$query=static::compose_query($query);
			$list=$db::query($query);
			
			while ($row=$db::fetch($list))
			{
				static::$data[$table][$row['uniID']]=$row;
			}
			
			unset(static::$queries[$table]);
		}
		
		debug ('clearing queue: '.count(static::$queue));
		$clear=array();
		foreach (static::$queue as $id=>$options)
		{
			static::received($options);
			$clear[]=$id;
		}
		foreach ($clear as $id)
		{
			unset(static::$queue[$id]);
		}
	}
	
	public static function add_to_queue($queue_data)
	{
		if (in_array($queue_data, static::$queue)) return;
		static::$queue[]=$queue_data;
	}
}
?>