<?
// ���� ������ �������� ���, ������� �������� ��������� ����.
// ���� ��� ���������� ����� ��������� �� �� �����... ����� ����, � ����� ��� ����� ������� �����-�� ������ �������. �� ���� ��� ��� �������� �� ��������� ������ ������ ����� �� ����� ���������� ��������, �� ��������� ������ ���������.
class EntityStorage
{
	static $prefix='';
	static $db;

	// ��� ������� �������� �����, ������� ����� ��������������� ���������� ����� ������.
	// STUB - ���������� ������ ������� ������ mysql_.
	public static function setupDB()
	{
		static::$db=new EntityOldMysql();
	}
	
	// ��� ������� ������ ���������� ������ ����������, �� ������� ����� ������������ ������� � ��. ������ ����� ����� ������.
	// ��������� ������ ����������� ���:
	// table => �������� �������
	// fields => ������ (���� => ��������)
	// where => ������ (���� => ��������; �������� ���� => �������)
	// action => update/replace/insert
	// set_uni - ���� �������, �� ����� �������� insert ��������������� ������������� ������������� �������-��������.
	
	// ��� ������� ������ �������, � ������� ������� ��������-���������� ����������� ��� ����������� � ���� ������.
	public static function store_combo(Entity_combo $who, $rules='')
	{
		if ($rules==='') $rules=$who->rules['storage'];
		if (!is_array($rules)) return false; // STUB - ��� ������ ���� ��������� ������.
	
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
		// ��� �������� ����� ���������, ����������� ������ � ������� ���������. ���������� ������ ������ �����, ������ ������ ��������������� ��������, ������� ��������� �������� �������� � ��������� ������ �� ����� ������ ��������. ��������, "translate", ������������ ������ �������� �������.
		{
			$uni=$who->uni; 
			if ($uni>0) // � �������� ���� ���� �������������, ������, ���� ������ � �� � ����� ������ ���������� ������.
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
			else // ��� ��������������, � ������, ���� �������� ��������.
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
		else // ��������, ���� ��� � ����������, �� ����� ��������� ������ � ��. ��� ������� ���� ������ ������� ��������, ������� ���������.
		{
			if (count($tables)>0) $queries['tables']=$tables;
			return $queries;
		}
	}
	
	// ��� ������� ������ �������, � ������� ������� ��������-�������� ����������� ��� ����������� � ���� ������.
	// ������� ������������ � �����, ����������� ����.
	public static function store_value(Entity_value $who, $rules='')
	{
		if ($rules==='') $rules=$who->rules['storage'];
		if (!is_array($rules)) return false; // STUB - ��� ������ ���� ��������� ������.
	
		if ($rules['method']=='uni')
		// ��� �������� ����� ���������, ����������� ������ � ������� ���������.
		{
			$queries=array();
			$uni=$who->uni; 
			if ($uni>0) // � �������� ���� ���� �������������, ������, ���� ������ � �� � ����� ������ ���������� ������.
			{
				$where=array('uniID'=>$uni);
				
				// ��� ���������� ������ � ������� ���������� ������				
				$value_query=array(
					'table'=>static::$prefix.static::value_table($who, $rules),
					'action'=>'update',
					'fields'=>static::value_fields($who, $rules),
					'where'=>$where
				);
				
				$queries[]=$value_query;
			}
			else // � �������� ��� ��������������, � ������, ����� �������� � � ���� ������.
			{
				// ��� ���������� ������ � ������� ���� ���������.
				$entities_query=static::new_entity_query($who, $rules);
				
				// ��� ���������� ������ � ������� ���������� ������.
				$value_query=array(
					'table'=>static::$prefix.static::value_table($who, $rules),
					'action'=>'insert',
					'fields'=>static::value_fields($who, $rules, 1)
				);
				
				// ������� ����� ����������� � �������� �������, ������ ��� ������� ����� ������ �� ������� (��������������� ������������� ����� ��������).
				$queries[]=$entities_query;
				$queries[]=$value_query;
			}
			
			return $queries;
		}
		else // � �������� ��� ����������� ������ � ������� ���������, ��� �������� ����� � ������ �������.
		{
			// ���������� ������ ����, ������������ ������ ��������� (��� ���� �� ������ ������ �������).
			// ����������� �������� ����� ������, ��� ���� �������� ���������� ������������� ������-���������� - �� � ������ ����� ���� � ������������ ������� �������.
			$result= array(
				'tables'=>array($rules['value_table']=>static::value_fields($who, $rules))
			);
			return $result;
		}
	}
	
	// ��� ������� ���������� ������ fields ��� �������� �� ���������� � ������.
	public static function value_fields($who, $rules, $new=false)
	{
		$result=array();
		if ($rules['value_field']<>'') $field=$rules['value_field'];
		elseif ($rules['by_html_name']) $field=$who->rules['html_name'];
		elseif ($rules['method']=='uni') $field='value';
		// STUB - ��� ��������� ������.
		
		$result=array($field=>$who->data['value']); // STUB - ���� �� ������������ ��������, ������ ������� ����� ��������� � ���� ����� (�����-������ �������������� �����?)
		
		if ($new) $result['uniID']='insert_id'; // ���� �������� ��������� ���������� ����������� ��������������. ����� � �������, ����� ����� �������� ��� ��������� � ������� ���������, � � ������� ������ - ��� ���.
		return $result;
	}
	
	// ������ ������ fields ��� ���������� ����� ��������� � ������� ���������.
	public static function new_entity_query($who, $rules)
	{
		if ($rules['uni_combo']) // ��� ���������, ������� �� ������� ������� �������� � ���� ������ �����, ������ �� ������� - ��������-��������.
		{
			$result=array(
				'table'=>$rules['uni_table'],
				'action'=>'insert',
				'set_uni'=>1
			);		
			// ������ fields ���, ������ ��� ��� �������� ��� �������� � ��������� (�� �� �����, ������ ��������� ������), � ����-�������� ��������� �����������.
		}
		else // �� ��������� �������� ������������ � ��������� ������� ���������.
		{
			$result=array(
				'table'=>static::$prefix.'entities',
				'action'=>'insert',
				'fields'=>array('entity_type'=>$who->entity_type() ), // �������� ��������� "Entity_" �� �������� ������.
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
	
	// �������������� �������� � ����, ����� �������� ��� � ������ SQL. ���������, ��� �������� ��� ��������� � �����������!
	public static function sql_value($value)
	{
		if (is_string($value)) $res="'$value'";
		elseif (is_null($value)) $res="NULL";
		elseif (is_numeric($value)) $res=$value;
		return $res;
	}
	
	// ��� ������� ������ �� ������� � ������� � ������� ��������������� ������ SQL.
	// �� ������ - ������.
	public static function compose_query($query)
	{
		// �������������� ����. ��� ����� ��� ���� ����� ��������.
		foreach ($query['fields'] as $field=>&$value)
		{
			if (($field==='uniID')&&($value==='insert_id')) $value=static::$db->get_insert_id();
			$value=static::sql_value($value);
		}
		
		if ($query['action']==='update') // ����������� ������...
		{
			// ���������� ���� � ������, ������� ����� � SET.
			$fields=array();
			foreach ($query['fields'] as $field=>$value)
			{
				$fields[]="`$field`=$value";
			}
			$fields=implode(', ', $fields);
	
			// ���������� ������� � ������, ������� ����� � WHERE.
			$where=array();
			foreach ($query['where'] as $key=>$value)
			{
				if (is_numeric($key)) $where[]=$value;
				else $where.="`$key`=".static::sql_value($value);
			}
			$where=implode(' AND ', $where);

			// ������ ������.
			$result="UPDATE `$query[table]` SET $fields WHERE $where";
		}
		elseif (($query['action']==='insert')||($query['action']==='replace'))
		{
			// ��� ��������� �����, ������ ��� �������� ����� ���� ������, � ����� �������� ���� ������.
			$result=strtoupper($query['action'])." INTO `$query[table]` (`".implode("`,`", array_keys($query['fields'])).'`) VALUES ('.implode(', ', $query['fields']).")";
		}
		
		return $result;
	}
	
	// ��� ������� ���� ��� �������, ����������� ��������� ������ (������� ������������ �� ���������-��������, �� ������� ��������� ������ � ������� ��������). ��� ���������� ��, ����� ������� ����� �����������. �� �������, ��� array_merge_recursive ������ �� �� �� �����, �������� �������-������.
	public static function merge_table_fields($arr1, $arr2)
	{
		if (count($arr1)+count($arr2)==0) return array();
		if (count($arr2)==0) return $arr1;
		if (count($arr1)==0) return $arr2;
		
		foreach ($arr1 as $table=>&$fields)
		{
			if (array_key_exists($table, $arr2)) // ���� ������� ��������� � ����� ��������...
			{
				$fields=array_merge($fields, $arr2[$table]); // ��������� ��� ���� �� ������� ������� � ������...
				unset($arr2[$table]); // � ������� �� ������� ���������� �������.
			}
		} 
		
		$arr1=array_merge($arr1, $arr2); // ���� �� ������ ������� �������� ��������, �� ��� �������, �� ���������� � ������ �������. ���������� ��.
		// �������, ���������� ������ � ������ �������, ��� ��� ����.
		
		return $arr1;
	}
}

EntityStorage::setupDB();
?>
