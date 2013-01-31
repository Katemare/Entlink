<?
// ��� �����, �� �������� ���������� Storage � Retriever - ������ �� ��������� ���������� � ��������� ������ �� ���� ������.
abstract class EntityDBOperator
{
	// �� ����, ��� ������ ���������� � ������ � ������� compose_query, �� �������������� � ����� �������. ���� �� ������� ��������, ������� ����� � �������� ������� �������� ������ � ������ �������.
	// ������ ����� ����� ������.
	// table => �������� ������� ��� ������ ��������.
	// action => update/replace/insert/select
	
	// ��� insert/replace/update:
	// fields => ������ (���� => ��������). ��� update - ��� ��������. ��� insert/replace - ����� �������� ��������.
	
	//��� select:
	// fields => ������ (����) . ��� ����, ������� ����� �������. � ���������� ����� ������� - *.
	
	// where => ������ (���� => ��������; �������� ���� => �������). ������ ��� update � select.
	
	// set_uni - ���� �������, �� ����� �������� insert/replace ��������������� ������������� ������������� �������-��������. STUB: ��� ������ �� �������� ���������� ��������� - �� ���� ����. ����� ������ �������� �� ���� �� �����������.

	static $db_prefix=''; // ������� ������ �� ������, ���� ��� ��������� ������ �� ��� ������.
	static $db; // �������� �������� ������, � ������� �������� �������������� ���������������� ������� � ��. � ���� ������ ������� ���������� <s>�������</s> parent::$db, � �� static:: ��� self::.

	// ��� ������� �������� �����, ������� ����� ��������������� ���������� ����� ������.
	// STUB - ���������� ������ ������� ������ mysql_.
	public static function setupDB()
	{
		static::$db='EntityOldMysql';
	}
	
	// �������������� �������� � ����, ����� �������� ��� � ������ SQL. ���������, ��� �������� ��� ��������� � �����������!
	public static function sql_value($value)
	{
		if (is_string($value)) $res="'$value'";
		elseif (is_null($value)) $res="NULL";
		elseif (is_numeric($value)) $res=$value;
		return $res;
	}
	
	// ��� ������� �������������� ������ � �����, ��������� �� �� ����� �������� � �������� � ������, ������� ����� �������� � ������. ������������ ������� fields � where �������� � ����������� �����, ��������� ����.
	// ��� ������� ������ ����������� ��������������� ����� ��������, � �� �������! ������ ��� ����� �� ������� �������� ���������� insert_id();
	// ������� ����� ��� ������: ������� ����� � ����������� �����.
	// ��� ������� $data �������� ������ ����� ��� ������� �������� ������������ ���� ������� (��. � ������ ������).
	// ��� ����������� $data ��������� ��������� ������� ������� fields (��� ��� ������ � where), � $field - �������� ����.
	public static function prepare_fields($data, $field='')
	{
		if ($field<>'') // ����������� �����.
		{
			if (is_array($data)) // ���� � ���� ������ �������� ��� �������� ����� id IN (2, 4, 10)
			{
				foreach ($data as &$value)
				{
					$value=static::prepare_fields($value, $field); // ����������� �����, �� ���� ��� ��� �������� �������. ������, ��� �������� �� ��, �� ���������� �� ������ � �������, �� ������� � ����� ������ ����� ������������?
				}
				if (count($data)==1) $data=reset($data); // ������ �� ������ �������� ������������� � ��������� ��������.
			}
			else // ����������� ����� ��� ��������� ���������� ��������.
			{
				if (preg_match('/^uniID/', $field)) $data=static::unize($data);
				$data=static::sql_value($data);
			}
			return $data;
		}
		else // ������� �����
		{
			foreach ($data as $field=>&$value)
			{
				if (is_numeric($field)) continue; // ��� ��� ��� ������� ������������ ��� ��� ��������� ������� where, � �� ����� ���� ������� ������� ��� ������������� �������. �� �� �������.
				$value=static::prepare_fields($value, $field); // ������ ���� �������������� ���������.
			}
			return $data;
		}
	}
	
	// ��� ������� ������ �� ������� where ������, ������� ����� �������� � ��������� ��������� ������.
	public static function compose_where($where, $operator='AND')
	{
		$where=static::prepare_fields($where); // ������ �������� ����� ����������� � �������-������. �� ������� ������� �������, � ������� �������� ����.
		$result=array();
		foreach ($where as $key=>$value)
		{
			if (is_numeric($key)) $result[]=$value; // ������� �������.
			elseif (is_array($value)) $result[]="`$key` IN (".implode(',', $value).")"; // ����� ��������.
			else $result[]="`$key`=$value"; // ���� ��������.
		}
		$result=implode(' '.$operator.' ', $result);
		return $result;
	}
	
	// ��� ������� ������ �� ������� � ������� � ������� ��������������� ������ SQL.
	// �� ������ - ������.
	public static function compose_query($query)
	{
		if ($query['action']==='update') // ����������� ������...
		{
			// ���������� ���� � ������, ������� ����� � SET.
			$query['fields']=static::prepare_fields($query['fields']);			
			$fields=array();
			foreach ($query['fields'] as $field=>$value)
			{
				$fields[]="`$field`=$value"; // �� ��������� �� ������ � �������.
			}
			$fields=implode(', ', $fields);
	
			// ���������� ������� � ������, ������� ����� � WHERE.
			// ���� where ��� ������, �� �������.
			// STUB: ��� ��������� ������, ����� where ���!
			if (is_array($where)) $where=static::compose_where($query['where'], $query['where_operator']);
			else $where=$query['where'];

			// ������ ������.
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
			// ��� ��������� �����, ������ ��� �������� ����� ���� ������, � ����� �������� ���� ������.
			// STUB: ��� �������� �� ��, ����� �������� ����� �� ���� ���������.
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