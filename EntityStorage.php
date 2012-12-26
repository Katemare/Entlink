<?
// этот классы скрывает код, которым сущности сохраняют себя.
// пока что реализация этого условлена не до конца... может быть, в итоге это будет сделано каким-то другим методом. но пока что это помогает не загружать сервер лишним кодом во время выполнения операций, не требующих ничего сохранять.
// этот объект мог бы быть гораздо проще, если бы не пришлось учитывать следующие типа хранения данных:
// 1. таблица сущностей (entities) и связанные с ней таблицы сущностей-данных (entities_text, entities_int)...
// 2. общая таблица, хранящая комбинацию как набор данных, то есть традиционным способом. "неофиты => имя, рост, вес, автор..."
// 3. отдельное поле в таблице из пункта 2 для сущностей-значений.
// 4. а ещё есть сущности-комбинации, которые возникают только во время показа и управления данными, но не имеют отдельных записей в БД. например, сущность "переводы", управляющая переводами имён покемонов, хотя сами переводы хранятся по методу 2 (основные) и 1 (дополнительные) и связаны с сущностью "покемон".
// однако всё это нужно учитывать, во-первых, для грамотной миграции существующих сайтов на этот движок, во-вторых, для совместимости с параллельными движками вроде MediaWiki.

class EntityStorage extends EntityDBOperator
{	
	// эта функция создаёт запросы, с помощью которых сущность-комбинация добавляется или обновляется в базе данных. она возвращает их в виде массива с запросами-массивами.
	public static function store_combo(Entity_combo $who, $storage_rules='')
	{
		if ($storage_rules==='') $storage_rules=$who->rules['storage'];
		if (!is_array($storage_rules)) return false; // STUB - тут должна быть обработка ошибки.
	
		$queries=array(); // здесь будут храниться готовые запросы-массивы
		$in_tables=array();	// здесь будут храниться поля, которым надо присворить значения в разных таблицах.
		foreach ($who->byrole as $role=>$list) // проверяем все внутренние сущности...
		{
			$entity_storage_rules=$who->model[$role]['storage'];
			foreach ($list as $entity) // каждая роль - это массив сущностей.
			{
				$res=$entity->store($entity_storage_rules); // получаем запросы-массивы для сохранения внутренней сущности.
				
				if (array_key_exists('in_tables', $res)) // если переданы не только готовые запросы, но и исправления в поля отдельных таблиц...
				{
					$in_tables=static::merge_table_fields($in_tables, $res['in_tables']); // объединяем их специальной функций с другими исправлениями в полях тех же таблиц.
					unset($res['in_tables']);
				}
				$queries=array_merge($queries, $res); // объединяем в единый ответ.
			}
		}
		
		if ($storage_rules['method']=='uni')
		// эта сущность имеет отдельную, собственную запись в таблице сущностей. комбинации обычно именно такие, однако бывают комбинированные сущности, которые управляют наборами значений и создаются только во время показа страницы. например, "translate", объединяющий разные названия покемона.
		{
			$uni=$who->uni; 
			if ($uni>0) // у сущности есть свой идентификатор, значит, есть запись в БД и нужно только обновление данных.
			{
				$where=array('uniID'=>$uni);
				foreach ($in_tables as $table=>$fields) // создаём запросы на обновления полей в отдельных таблицах.
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
			else // нет существующего идентификатора, а значит, надо добавить сущность.
			{	
				$entities_query=static::new_entity_query($who, $storage_rules);
				if ($storage_rules['uni_combo']) // эта инструкция указывает, что комбинация хранится старым способом - все данные (или большая часть) в одном элементе таблицы.
				{
					$table=static::get_entity_table($who, $storage_rules);
					if (array_key_exists($table, $in_tables)) // если таблица, где хранится комбинация, также упомянутая в подзапросах...
					{
						$entities_query['fields']=$in_tables[$table]; // добавляем эти исправления в запрос на создание новой сущности.
						unset($in_tables[$table]);
					}
				}
				$queries[]=$entities_query;
								
				foreach ($in_tables as $table=>$fields) // добавляем оставшиеся записи в таблицы.
				{
					$value_query=array(
						'table'=>static::$prefix.$table,
						'action'=>'insert',
						'fields'=>$fields
					);
					$value_query['fields']['uniID']='insert_id'; // они должны быть связаны с только что добавленной сущностью. FIX!! а ведь если этих запросов много, то после первого же это поле испортится...
					$queries[]=$value_query;
				}
			}
			return $queries;
		}
		else // сущность, хотя это и комбинация, не имеет отдельной записи в БД. она передаёт свои данные верхней сущности, которая разберётся.
		{
			if (count($in_tables)>0) $queries['in_tables']=$in_tables;
			return $queries;
		}
	}
	
	// эта функция создаёт запросы, с помощью которых сущность-значение добавляется или обновляется в базе данных.
	// запросы возвращаются в форме, определённой выше.
	public static function store_value(Entity_value $who, $storage_rules='')
	{
		if ($storage_rules==='') $storage_rules=$who->rules['storage'];
		if (!is_array($storage_rules)) return false; // STUB - тут должна быть обработка ошибки.
	
		if ($storage_rules['method']=='uni')
		// эта сущность имеет отдельную, собственную запись в таблице сущностей.
		{
			$queries=array();
			$uni=$who->uni; 
			if ($uni>0) // у сущности есть свой идентификатор, значит, есть запись в БД и нужно только обновление данных.
			{
				$where=array('uniID'=>$uni);
				
				// это обновление данных в таблице конкретных данных				
				$value_query=array(
					'table'=>static::$prefix.static::get_entity_table($who, $storage_rules),
					'action'=>'update',
					'fields'=>static::value_fields($who, $storage_rules),
					'where'=>$where
				);
				
				$queries[]=$value_query;
			}
			else // у сущности нет идентификатора, а значит, нужно добавить её в базу данных.
			{
				// это добавление записи в таблицу всех сущностей.
				$entities_query=static::new_entity_query($who, $storage_rules);
				
				// это добавление данных в таблицу конкретных данных.
				$value_query=array(
					'table'=>static::$prefix.static::get_entity_table($who, $storage_rules),
					'action'=>'insert',
					'fields'=>static::value_fields($who, $storage_rules, 1)
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
				'in_tables'=>array(static::$prefix.static::get_entity_table($who, $storage_rules)=>static::value_fields($who, $storage_rules))
			);
			return $result;
		}
	}
	
	// эта функция генерирует массив fields для запросов на обновление и добавление.
	public static function value_fields($who, $storage_rules, $new=false)
	{
		$result=array();
		if ($storage_rules['value_field']<>'') $field=$storage_rules['value_field'];
		elseif ($storage_rules['by_html_name']) $field=$who->rules['html_name'];
		elseif ($storage_rules['method']=='uni') $field='value';
		// STUB - нет обработки ошибки.
		
		$result=array($field=>$who->data['value']); // STUB - пока не поддерживает сущности, данные которых могут храниться в двух полях (какие-нибудь иррациональные числа?)
		
		if ($new) $result['uniID']='insert_id'; // этот параметр добавляет присвоение уникального идентификатора. нужен в случаях, когда новая сущность уже добавлена в таблицу сущностей, а в таблицу данных - ещё нет.
		return $result;
	}
	
	// создаёт массив fields для добавления новых сущностей в таблицу сущностей.
	public static function new_entity_query($who, $storage_rules)
	{
		if ($storage_rules['uni_combo']) // для сущностей, которые по старому образцу хранятся в виде набора полей, каждая из которых - сущность-значение.
		{
			$result=array(
				'table'=>static::prefix.static::get_entity_table($who, $storage_rules),
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
?>
