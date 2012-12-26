<?
// ���� ������ �������� ���, ������� �������� ��������� ����.
// ���� ��� ���������� ����� ��������� �� �� �����... ����� ����, � ����� ��� ����� ������� �����-�� ������ �������. �� ���� ��� ��� �������� �� ��������� ������ ������ ����� �� ����� ���������� ��������, �� ��������� ������ ���������.
// ���� ������ ��� �� ���� ������� �����, ���� �� �� �������� ��������� ��������� ���� �������� ������:
// 1. ������� ��������� (entities) � ��������� � ��� ������� ���������-������ (entities_text, entities_int)...
// 2. ����� �������, �������� ���������� ��� ����� ������, �� ���� ������������ ��������. "������� => ���, ����, ���, �����..."
// 3. ��������� ���� � ������� �� ������ 2 ��� ���������-��������.
// 4. � ��� ���� ��������-����������, ������� ��������� ������ �� ����� ������ � ���������� �������, �� �� ����� ��������� ������� � ��. ��������, �������� "��������", ����������� ���������� ��� ���������, ���� ���� �������� �������� �� ������ 2 (��������) � 1 (��������������) � ������� � ��������� "�������".
// ������ �� ��� ����� ���������, ��-������, ��� ��������� �������� ������������ ������ �� ���� ������, ��-������, ��� ������������� � ������������� �������� ����� MediaWiki.

class EntityStorage extends EntityDBOperator
{	
	// ��� ������� ������ �������, � ������� ������� ��������-���������� ����������� ��� ����������� � ���� ������. ��� ���������� �� � ���� ������� � ���������-���������.
	public static function store_combo(Entity_combo $who, $storage_rules='')
	{
		if ($storage_rules==='') $storage_rules=$who->rules['storage'];
		if (!is_array($storage_rules)) return false; // STUB - ��� ������ ���� ��������� ������.
	
		$queries=array(); // ����� ����� ��������� ������� �������-�������
		$in_tables=array();	// ����� ����� ��������� ����, ������� ���� ���������� �������� � ������ ��������.
		foreach ($who->byrole as $role=>$list) // ��������� ��� ���������� ��������...
		{
			$entity_storage_rules=$who->model[$role]['storage'];
			foreach ($list as $entity) // ������ ���� - ��� ������ ���������.
			{
				$res=$entity->store($entity_storage_rules); // �������� �������-������� ��� ���������� ���������� ��������.
				
				if (array_key_exists('in_tables', $res)) // ���� �������� �� ������ ������� �������, �� � ����������� � ���� ��������� ������...
				{
					$in_tables=static::merge_table_fields($in_tables, $res['in_tables']); // ���������� �� ����������� ������� � ������� ������������� � ����� ��� �� ������.
					unset($res['in_tables']);
				}
				$queries=array_merge($queries, $res); // ���������� � ������ �����.
			}
		}
		
		if ($storage_rules['method']=='uni')
		// ��� �������� ����� ���������, ����������� ������ � ������� ���������. ���������� ������ ������ �����, ������ ������ ��������������� ��������, ������� ��������� �������� �������� � ��������� ������ �� ����� ������ ��������. ��������, "translate", ������������ ������ �������� ��������.
		{
			$uni=$who->uni; 
			if ($uni>0) // � �������� ���� ���� �������������, ������, ���� ������ � �� � ����� ������ ���������� ������.
			{
				$where=array('uniID'=>$uni);
				foreach ($in_tables as $table=>$fields) // ������ ������� �� ���������� ����� � ��������� ��������.
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
			else // ��� ������������� ��������������, � ������, ���� �������� ��������.
			{	
				$entities_query=static::new_entity_query($who, $storage_rules);
				if ($storage_rules['uni_combo']) // ��� ���������� ���������, ��� ���������� �������� ������ �������� - ��� ������ (��� ������� �����) � ����� �������� �������.
				{
					$table=static::get_entity_table($who, $storage_rules);
					if (array_key_exists($table, $in_tables)) // ���� �������, ��� �������� ����������, ����� ���������� � �����������...
					{
						$entities_query['fields']=$in_tables[$table]; // ��������� ��� ����������� � ������ �� �������� ����� ��������.
						unset($in_tables[$table]);
					}
				}
				$queries[]=$entities_query;
								
				foreach ($in_tables as $table=>$fields) // ��������� ���������� ������ � �������.
				{
					$value_query=array(
						'table'=>static::$prefix.$table,
						'action'=>'insert',
						'fields'=>$fields
					);
					$value_query['fields']['uniID']='insert_id'; // ��� ������ ���� ������� � ������ ��� ����������� ���������. FIX!! � ���� ���� ���� �������� �����, �� ����� ������� �� ��� ���� ����������...
					$queries[]=$value_query;
				}
			}
			return $queries;
		}
		else // ��������, ���� ��� � ����������, �� ����� ��������� ������ � ��. ��� ������� ���� ������ ������� ��������, ������� ���������.
		{
			if (count($in_tables)>0) $queries['in_tables']=$in_tables;
			return $queries;
		}
	}
	
	// ��� ������� ������ �������, � ������� ������� ��������-�������� ����������� ��� ����������� � ���� ������.
	// ������� ������������ � �����, ����������� ����.
	public static function store_value(Entity_value $who, $storage_rules='')
	{
		if ($storage_rules==='') $storage_rules=$who->rules['storage'];
		if (!is_array($storage_rules)) return false; // STUB - ��� ������ ���� ��������� ������.
	
		if ($storage_rules['method']=='uni')
		// ��� �������� ����� ���������, ����������� ������ � ������� ���������.
		{
			$queries=array();
			$uni=$who->uni; 
			if ($uni>0) // � �������� ���� ���� �������������, ������, ���� ������ � �� � ����� ������ ���������� ������.
			{
				$where=array('uniID'=>$uni);
				
				// ��� ���������� ������ � ������� ���������� ������				
				$value_query=array(
					'table'=>static::$prefix.static::get_entity_table($who, $storage_rules),
					'action'=>'update',
					'fields'=>static::value_fields($who, $storage_rules),
					'where'=>$where
				);
				
				$queries[]=$value_query;
			}
			else // � �������� ��� ��������������, � ������, ����� �������� � � ���� ������.
			{
				// ��� ���������� ������ � ������� ���� ���������.
				$entities_query=static::new_entity_query($who, $storage_rules);
				
				// ��� ���������� ������ � ������� ���������� ������.
				$value_query=array(
					'table'=>static::$prefix.static::get_entity_table($who, $storage_rules),
					'action'=>'insert',
					'fields'=>static::value_fields($who, $storage_rules, 1)
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
				'in_tables'=>array(static::$prefix.static::get_entity_table($who, $storage_rules)=>static::value_fields($who, $storage_rules))
			);
			return $result;
		}
	}
	
	// ��� ������� ���������� ������ fields ��� �������� �� ���������� � ����������.
	public static function value_fields($who, $storage_rules, $new=false)
	{
		$result=array();
		if ($storage_rules['value_field']<>'') $field=$storage_rules['value_field'];
		elseif ($storage_rules['by_html_name']) $field=$who->rules['html_name'];
		elseif ($storage_rules['method']=='uni') $field='value';
		// STUB - ��� ��������� ������.
		
		$result=array($field=>$who->data['value']); // STUB - ���� �� ������������ ��������, ������ ������� ����� ��������� � ���� ����� (�����-������ �������������� �����?)
		
		if ($new) $result['uniID']='insert_id'; // ���� �������� ��������� ���������� ����������� ��������������. ����� � �������, ����� ����� �������� ��� ��������� � ������� ���������, � � ������� ������ - ��� ���.
		return $result;
	}
	
	// ������ ������ fields ��� ���������� ����� ��������� � ������� ���������.
	public static function new_entity_query($who, $storage_rules)
	{
		if ($storage_rules['uni_combo']) // ��� ���������, ������� �� ������� ������� �������� � ���� ������ �����, ������ �� ������� - ��������-��������.
		{
			$result=array(
				'table'=>static::prefix.static::get_entity_table($who, $storage_rules),
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
?>
