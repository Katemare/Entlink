<?
// ��� �����, �� �������� ���������� Storage � Retriever - ������ �� ��������� ���������� � ��������� ������ �� ���� ������.
abstract class EntityDBOperator
{
	// �� ����, ��� ������ ���������� � ������ � ������� compose_query, �� �������������� � ����� �������. ���� �� ������� ��������, ������� ����� � �������� ������� �������� ������ � ������ �������.
	// ������ ����� ����� ������.
	// table => �������� �������
	// action => update/replace/insert/select	
	// fields => ������ (���� => ��������). ��� update - ��� ��������. ��� insert/replace - ����� �������� ��������. ��� select ������ ������.
	// fields => ������ (����) . ��� ����, ������� ����� �������. � ���������� ����� ������� - *.
	// where => ������ (���� => ��������; �������� ���� => �������). ������ ��� update � select.
	// set_uni - ���� �������, �� ����� �������� insert/replace ��������������� ������������� ������������� �������-��������. STUB: ��� ������ �� �������� ���������� ��������� - �� ���� ����. ����� ������ �������� �� ���� �� �����������.

	static $prefix=''; // ������� ������ �� ������, ���� ��� ��������� ������ �� ��� ������.
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
				$db=self::$db;
				if (($field==='uniID')&&($data==='insert_id')) $data=$db::get_insert_id();
				else $data=static::sql_value($data);
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
	public static function compose_where($where)
	{
		$where=static::prepare_fields($where); // ������ �������� ����� ����������� � �������-������. �� ������� ������� �������, � ������� �������� ����.
		$result=array();
		foreach ($where as $key=>$value)
		{
			if (is_numeric($key)) $result[]=$value; // ������� �������.
			elseif (is_array($value)) $result[]="`$key` IN (".implode(',', $value).")"; // ����� ��������.
			else $result[]="`$key`=$value"; // ���� ��������.
		}
		$result=implode(' AND ', $result);
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
			$where=static::compose_where($query['where']);

			// ������ ������.
			$result="UPDATE `$query[table]` SET $fields WHERE $where";
		}
		elseif ($query['action']==='select')
		{
			if (array_key_exists('fields', $query)) $fields="`".implode("`,`", $query['fields'])."`";
			else $fields='*';
			
			if (array_key_exists('where', $query)) $where='WHERE '.static::compose_where($query['where']);
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
	
	// ��� ������� ��������� �������� �������, �� ������� ������� ������ ��� "��������� ��������" - ���, ������� ����� ��������� ������ � entities; � ����� ��� ���������, � ������� ���� ������� �������, � ��� ����� ������� ������������� ����� � ����������� �������� ���������� (���� ����� � ����� �������).
	// �� ��������� �������.
	public static function get_entity_table($who, $storage_rules)
	{
		if ($storage_rules['entity_table']<>'') $table=$storage_rules['entity_table'];
		//elseif ($storage_rules['uni_table
		else $table='entities_'.$who->entity_type();
		return $table;
	}
}

EntityDBOperator::setupDB();
?>