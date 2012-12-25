<?
// этот классы скрывает код, которым сущности сохраняют себя.
// пока что реализация этого условлена не до конца... может быть, в итоге это будет сделано каким-то другим методом. но пока что это помогает не загружать сервер лишним кодом во время выполнения операций, не требующих ничего сохранять.
class EntityStorage
{
	static $prefix='';
	static $db;

	// эта функция выбирает класс, который будет непосредственно заниматься базой данных.
	// STUB - использует старую систему команд mysql_.
	public static function setupDB()
	{
		static::$db=new EntityOldMysql();
	}
	
	// эта функция должна возвращать массив параметров, из которых потом составляются запросы к БД. массив имеет такой формат.
	// отдельный запрос описывается так:
	// table => название таблицы
	// fields => массив (поле => значение)
	// where => массив (поле => значение; цифровой ключ => условие)
	// action => update/replace/insert
	// set_uni - если истинно, то после операции insert сгенерированный идентификатор присваивается объекту-сущности.
	
	// эта функция создаёт запросы, с помощью которых сущность-комбинация добавляется или обновляется в базе данных.
	public static function store_combo(Entity_combo $who, $rules='')
	{
		if ($rules==='') $rules=$who->rules['storage'];
		if (!is_array($rules)) return false; // STUB - тут должна быть обработка ошибки.
	
		$queries=array();
		$tables=array();	
		foreach ($who->byrole as $role=>$list)
		{
			$erules=$who->model[$role]['storage'];
			foreach ($list as $entity)
			{
				$res=$entity->store($erules);
				
				if (array_key_exists('tables', $res))
				{
					$tables=static::merge_table_fields($tables, $res['tables']);
					unset($res['tables']);
				}
				$queries=array_merge($queries, $res);
			}
		}
		
		if ($rules['method']=='uni')
		// эта сущность имеет отдельную, собственную запись в таблице сущностей. комбинации обычно именно такие, однако бывают комбинированные сущности, которые управляют наборами значений и создаются только во время показа страницы. например, "translate", объединяющий разные названия покмона.
		{
			$uni=$who->uni; 
			if ($uni>0) // у сущности есть свой идентификатор, значит, есть запись в БД и нужно только обновление данных.
			{
				$where=array('uniID'=>$uni);
				foreach ($tables as $table=>$fields)
				{
					$table_query=array(
						'table'=>static::$prefix.$table,
						'action'=>'update',
						'fields'=>$fields,
						'where'=>$where
					);
					$queries[]=$table_query;
				}
			}
			else // нет идентификатора, а значит, надо добавить сущность.
			{	
				$entities_query=static::new_entity_query($who, $rules);
				if ($rules['uni_combo'])
				{
					if (array_key_exists($rules['uni_table'], $tables))
					{
						$entities_query['fields']=$tables[$rules['uni_table']];
						unset($tables[$rules['uni_table']]);
					}
				}
				$queries[]=$entities_query;
								
				foreach ($tables as $table=>$fields)
				{
					$value_query=array(
						'table'=>static::$prefix.$table,
						'action'=>'insert',
						'fields'=>$fields
					);
					$value_query['fields']['uniID']='insert_id';
					$queries[]=$value_query;
				}
			}
			return $queries;
		}
		else // сущность, хотя это и комбинация, не имеет отдельной записи в БД. она передаёт свои данные верхней сущности, которая разберётся.
		{
			if (count($tables)>0) $queries['tables']=$tables;
			return $queries;
		}
	}
	
	// эта функция создаёт запросы, с помощью которых сущность-значение добавляется или обновляется в базе данных.
	// запросы возвращаются в форме, определённой выше.
	public static function store_value(Entity_value $who, $rules='')
	{
		if ($rules==='') $rules=$who->rules['storage'];
		if (!is_array($rules)) return false; // STUB - тут должна быть обработка ошибки.
	
		if ($rules['method']=='uni')
		// эта сущность имеет отдельную, собственную запись в таблице сущностей.
		{
			$queries=array();
			$uni=$who->uni; 
			if ($uni>0) // у сущности есть свой идентификатор, значит, есть запись в БД и нужно только обновление данных.
			{
				$where=array('uniID'=>$uni);
				
				// это обновление данных в таблице конкретных данных				
				$value_query=array(
					'table'=>static::$prefix.static::value_table($who, $rules),
					'action'=>'update',
					'fields'=>static::value_fields($who, $rules),
					'where'=>$where
				);
				
				$queries[]=$value_query;
			}
			else // у сущности нет идентификатора, а значит, нужно добавить её в базу данных.
			{
				// это добавление записи в таблицу всех сущностей.
				$entities_query=static::new_entity_query($who, $rules);
				
				// это добавление данных в таблицу конкретных данных.
				$value_query=array(
					'table'=>static::$prefix.static::value_table($who, $rules),
					'action'=>'insert',
					'fields'=>static::value_fields($who, $rules, 1)
				);
				
				// запросы будут выполняться в сторогом порядке, потому что второму нужны данные от первого (сгенерированный идентификатор новой сущности).
				$queries[]=$entities_query;
				$queries[]=$value_query;
			}
			
			return $queries;
		}
		else // у сущности нет собственной записи в таблице сущностей, она является полем в другой таблице.
		{
			// возвращаем только поля, родительский объект разберётся (это ведь он вызвал данную функцию).
			// вложенность массивов нужна потому, что если подобное возвращает промежуточный объект-комбинация - то в ответе могут быть и нумерованные готовые запросы.
			$result= array(
				'tables'=>array($rules['value_table']=>static::value_fields($who, $rules))
			);
			return $result;
		}
	}
	
	// эта функция генерирует массив fields для запросов на обновление и прочее.
	public static function value_fields($who, $rules, $new=false)
	{
		$result=array();
		if ($rules['value_field']<>'') $field=$rules['value_field'];
		elseif ($rules['by_html_name']) $field=$who->rules['html_name'];
		elseif ($rules['method']=='uni') $field='value';
		// STUB - нет обработки ошибки.
		
		$result=array($field=>$who->data['value']); // STUB - пока не поддерживает сущности, данные которых могут храниться в двух полях (какие-нибудь иррациональные числа?)
		
		if ($new) $result['uniID']='insert_id'; // этот параметр добавляет присвоение уникального идентификатора. нужен в случаях, когда новая сущность уже добавлена в таблицу сущностей, а в таблицу данных - ещё нет.
		return $result;
	}
	
	// создаёт массив fields для добавления новых сущностей в таблицу сущностей.
	public static function new_entity_query($who, $rules)
	{
		if ($rules['uni_combo']) // для сущностей, которые по старому образцу хранятся в виде набора полей, каждая из которых - сущность-значение.
		{
			$result=array(
				'table'=>$rules['uni_table'],
				'action'=>'insert',
				'set_uni'=>1
			);		
			// пункта fields нет, потому что тип сущности уже известен в контексте (мы же знали, откуда извлекать данные), а поля-значения обеспечат подсущности.
		}
		else // по умолчанию сущность записывается в отдельную таблицу сущностей.
		{
			$result=array(
				'table'=>static::$prefix.'entities',
				'action'=>'insert',
				'fields'=>array('entity_type'=>$who->entity_type() ), // отсекает приставку "Entity_" от названия класса.
				'set_uni'=>1
			);
		}
		return $result;
	}
	
	public static function value_table($who, $rules)
	{
		if ($rules['value_table']<>'') $value_table=$rules['value_table'];
		else $value_table='entities_'.$who->entity_type();
		return $value_table;
	}
	
	// подготавливает значение к тому, чтобы вставить его в запрос SQL. считается, что значения уже проверены и обезопасены!
	public static function sql_value($value)
	{
		if (is_string($value)) $res="'$value'";
		elseif (is_null($value)) $res="NULL";
		elseif (is_numeric($value)) $res=$value;
		return $res;
	}
	
	// эта функция создаёт из массива с данными о запросе непосредственно запрос SQL.
	// на выходе - строка.
	public static function compose_query($query)
	{
		// подготавливаем поля. это нужно для всех типов запросов.
		foreach ($query['fields'] as $field=>&$value)
		{
			if (($field==='uniID')&&($value==='insert_id')) $value=static::$db->get_insert_id();
			$value=static::sql_value($value);
		}
		
		if ($query['action']==='update') // обновляющий запрос...
		{
			// превращаем поля в строку, которая будет в SET.
			$fields=array();
			foreach ($query['fields'] as $field=>$value)
			{
				$fields[]="`$field`=$value";
			}
			$fields=implode(', ', $fields);
	
			// превращаем условия в строку, которая будет в WHERE.
			$where=array();
			foreach ($query['where'] as $key=>$value)
			{
				if (is_numeric($key)) $where[]=$value;
				else $where.="`$key`=".static::sql_value($value);
			}
			$where=implode(' AND ', $where);

			// строим запрос.
			$result="UPDATE `$query[table]` SET $fields WHERE $where";
		}
		elseif (($query['action']==='insert')||($query['action']==='replace'))
		{
			// эта процедура проще, потому что названия полей идут подряд, а потом значения идут подряд.
			$result=strtoupper($query['action'])." INTO `$query[table]` (`".implode("`,`", array_keys($query['fields'])).'`) VALUES ('.implode(', ', $query['fields']).")";
		}
		
		return $result;
	}
	
	// эта функция берёт два массива, описывающие частичный запрос (которые возвращаются от сущностей-значений, не имеющих отдельной записи в таблице сущности). она объединяет их, чтобы массивы полей соединились. не уверена, что array_merge_recursive сделал бы то же самое, встретив элемент-массив.
	public static function merge_table_fields($arr1, $arr2)
	{
		if (count($arr1)+count($arr2)==0) return array();
		if (count($arr2)==0) return $arr1;
		if (count($arr1)==0) return $arr2;
		
		foreach ($arr1 as $table=>&$fields)
		{
			if (array_key_exists($table, $arr2)) // если таблица упомянута в обоих массивах...
			{
				$fields=array_merge($fields, $arr2[$table]); // добавляем все поля из второго массива в первый...
				unset($arr2[$table]); // и стираем из второго упоминания таблицы.
			}
		} 
		
		$arr1=array_merge($arr1, $arr2); // если во втором массиве остались элементы, то это таблицы, не упомянутые в первом массиве. объединяем их.
		// таблицы, упомянутые только в первом массиве, уже там есть.
		
		return $arr1;
	}
}

EntityStorage::setupDB();
?>
