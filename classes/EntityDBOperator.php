<?
// это класс, от которого образуются Storage и Retriever - классы по операциям сохранения и получения данных из базы данных.
abstract class EntityDBOperator
{
	// до того, как запрос собирается в строку с помощью compose_query, он представляется в форме массива. если не сказано обратное, функции этого и дочерних классов работают именно с формой массива.
	// массив имеет такой формат.
	// table => название таблицы или массив названий.
	// action => update/replace/insert/select
	
	// для insert/replace/update:
	// fields => массив (поле => значение). для update - что обновить. для insert/replace - какие значения вставить.
	
	//для select:
	// fields => массив (поля) . это поля, которые нужно выбрать. в отсутствие этого массива - *.
	
	// where => массив (поле => значение; цифровой ключ => условие). только для update и select.
	
	// set_uni - если истинно, то после операции insert/replace сгенерированный идентификатор присваивается объекту-сущности. STUB: что делать со вставкой нескольких сущностей - не знаю пока. будем решать проблемы по мере их поступления.

	static $db_prefix=''; // префикс таблиц на случай, если при установке движка он был указан.
	static $db; // содержит название класса, с помощью которого осуществляется непосредственное общение с БД. к нему всегда следует обращаться <s>вежливо</s> parent::$db, а не static:: или self::.

	// эта функция выбирает класс, который будет непосредственно заниматься базой данных.
	// STUB - использует старую систему команд mysql_.
	public static function setupDB()
	{
		static::$db='EntityOldMysql';
	}
	
	// подготавливает значение к тому, чтобы вставить его в запрос SQL. считается, что значения уже проверены и обезопасены!
	public static function sql_value($value)
	{
		if (is_string($value)) $res="'$value'";
		elseif (is_null($value)) $res="NULL";
		elseif (is_numeric($value)) $res=$value;
		return $res;
	}
	
	// эта функция подготавливает данные о полях, превращая их из голых значений и массивов в строки, которые можно включить в запрос. обрабатывает массивы fields и where запросов в стандартной форме, описанной выше.
	// эта функция должна выполняться непосредственно перед запросом, а не заранее! потому что иначе не удастся получить правильный insert_id();
	// функция имеет два режима: обычный вызов и рекурсивный вызов.
	// при обычном $data содержит массив полей или условий согласно стандартному виду запроса (см. в начале класса).
	// при рекурсивном $data содержито отдельный элемент массива fields (или его аналог в where), а $field - название поля.
	public static function prepare_fields($data, $field='')
	{
		if ($field<>'') // рекурсивный вызов.
		{
			if (is_array($data)) // если в поле массив значений для операций вроде id IN (2, 4, 10)
			{
				foreach ($data as &$value)
				{
					$value=static::prepare_fields($value, $field); // рекурсивный вызов, на этот раз для элемента массива. правда, нет проверки на то, не содержится ли массив в массиве, но неужели и такие ошибки стоит обрабатывать?
				}
				if (count($data)==1) $data=reset($data); // массив из одного элемента преобразуется в банальное значение.
			}
			else // рекурсивный вызов для обработки единичного значения.
			{
				if (preg_match('/^uniID/', $field)) $data=static::unize($data);
				$data=static::sql_value($data);
			}
			return $data;
		}
		else // обычный вызов
		{
			foreach ($data as $field=>&$value)
			{
				if (is_numeric($field)) continue; // так как эта функция используется ещё для обработки условий where, в нём могут быть готовые условия под нумерованными ключами. их не трогаем.
				$value=static::prepare_fields($value, $field); // каждое поле обрабатывается рекурсией.
			}
			return $data;
		}
	}
	
	// эта функция делает из массива where строку, которую нужно вставить в финальный строковый запрос.
	public static function compose_where($where, $operator='AND')
	{
		$where=static::prepare_fields($where); // делает значения полей применимыми в запросе-строке. не трогает готовых условий, у которых числовой ключ.
		$result=array();
		foreach ($where as $key=>$value)
		{
			if (is_numeric($key)) $result[]=$value; // готовое условие.
			elseif (is_array($value)) $result[]="`$key` IN (".implode(',', $value).")"; // набор значений.
			else $result[]="`$key`=$value"; // одно значение.
		}
		$result=implode(' '.$operator.' ', $result);
		return $result;
	}
	
	// эта функция создаёт из массива с данными о запросе непосредственно запрос SQL.
	// на выходе - строка.
	public static function compose_query($query)
	{
		if ($query['action']==='update') // обновляющий запрос...
		{
			// превращаем поля в строку, которая будет в SET.
			$query['fields']=static::prepare_fields($query['fields']);			
			$fields=array();
			foreach ($query['fields'] as $field=>$value)
			{
				$fields[]="`$field`=$value"; // не проверяет на массив в массиве.
			}
			$fields=implode(', ', $fields);
	
			// превращаем условия в строку, которая будет в WHERE.
			// если where уже строка, не трогаем.
			// STUB: нет обработки ошибки, когда where нет!
			if (is_array($where)) $where=static::compose_where($query['where'], $query['where_operator']);
			else $where=$query['where'];

			// строим запрос.
			$result="UPDATE `$query[table]` SET $fields WHERE $where";
		}
		elseif ($query['action']==='select')
		{
			if (array_key_exists('fields', $query)) $fields="`".implode("`,`", $query['fields'])."`";
			else $fields='*';
			
			$where='';
			if ( (array_key_exists('where', $query))&&(is_array($query['where'])) ) $where='WHERE '.static::compose_where($query['where'], $query['where_operator']);
			else $where='';
			
			$result="SELECT $fields FROM `$query[table]` $where ";
		}
		elseif (($query['action']==='insert')||($query['action']==='replace'))
		{
			$query['fields']=static::prepare_fields($query['fields']);		
			// эта процедура проще, потому что названия полей идут подряд, а потом значения идут подряд.
			// STUB: нет проверки на то, чтобы значения полей не были массивами.
			$result=strtoupper($query['action'])." INTO `$query[table]` (`".implode("`,`", array_keys($query['fields'])).'`) VALUES ('.implode(', ', $query['fields']).")";
		}
		
		return $result;
	}
	
	public static function unize($code)
	{
		if (is_numeric($code)) return $code;
		if (preg_match('/^uni(\d+)$/', $code, $m))
		{
			$id=$m[1];
			if (Entity::id_exists($uni)) return Entity::$entities_list[$id]->uni;
			else return $code;
		}
		return $code;
	}
}

EntityDBOperator::setupDB();
?>