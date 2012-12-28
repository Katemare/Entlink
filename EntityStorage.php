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
						'table'=>static::$db_prefix.$table,
						'action'=>'update',
						'fields'=>$fields,
						'where'=>$where
					);
					$queries[]=$table_query;
				}
			}
			else // ��� ������������� ��������������, � ������, ���� �������� ��������.
			{	
				$entities_query=static::new_entity_query($who, 'entities');
				$queries[]=$entities_query; // ������ � ����� ������� ��������� �� ����� �����.
				
				if ($storage_rules['uni_combo']) // ��� ���������� ���������, ��� ���������� �������� ������ �������� - ��� ������ (��� ������� �����) � ����� �������� �������.
				{
					$entities_query=static::new_entity_query($who, $storage_rules);				
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
						'table'=>static::$db_prefix.$table,
						'action'=>'insert',
						'fields'=>$fields
					);
					$value_query['fields']['uniID']='uni'.$who->id; // ��� ������ ���� ������� � ������ ��� ����������� ���������. �� �� ���������� � ������� combo_parent ������, ��� �������������� �������� � ���� ��������� ����������� �������� ������ �� ���, ��� ������� ������ � $in_tables.
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
					'table'=>static::$db_prefix.static::get_entity_table($who, $storage_rules),
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
				// � ��������-�������� �� ����� ���� ����������� ������� 'uni_combo', ��� ��� ��� ������� ������ ����� ������ � entities.
				
				// ��� ���������� ������ � ������� ���������� ������.
				$value_query=array(
					'table'=>static::$db_prefix.static::get_entity_table($who, $storage_rules),
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
				'in_tables'=>array(static::$db_prefix.static::get_entity_table($who, $storage_rules)=>static::value_fields($who, $storage_rules))
			);
			return $result;
		}
	}
	
	// ��� ������� ���������� ������� � �� �� ���������� ������.
	public static function storage($queries)
	{
		$db=parent::$db;
		
		foreach ($queries as $q)
		{
			$query=static::compose_query($q);
			$result=$db::query($query);
			// STUB: ����� ��������� ������
			if ($q['set_uni']>0)
			{
				$uni=$db::insert_id();
				$entity=Entity::$entities_list[$q['set_uni']];
				$entity->setUni($uni);
			}
		}
	}
	
	// ��� ������� ���������� ������ fields ��� �������� �� ���������� � ����������.
	public static function value_fields($who, $storage_rules, $new=false)
	{
		$result=array();
		
		if ($who instanceof Entity_value)
		{
			$field=static::get_value_field($who, $storage_rules);
			$result[$field]=$who->data['value']; // STUB - ���� �� ������������ ��������, ������ ������� ����� ��������� � ���� ����� (�����-������ �������������� �����?)
		}
		elseif ($who instanceof Entity_link)
		{
			$result['uniID1']='uni'.$who->data['entity1']->id;
			$result['uniID2']='uni'.$who->data['entity2']->id;
			$result['connection']=$who->data['connection'];
		}
		
		if (($new)&&($storage_rules['method']=='uni')) $result['uniID']='uni'.$who->id; // ���� �������� �������� � ���� ��������: entities � ������� ������.
		return $result;
	}
	
	// ������ ������ fields ��� ���������� ����� ��������� � ������� ���������.
	public static function new_entity_query($who, $storage_rules='entities')
	{
		if (($storage_rules==='entities')||(!$storage_rules['uni_combo'])) // �� ��������� �������� ������������ � ��������� ������� ���������.
		{
			$result=array(
				'table'=>static::$db_prefix.'entities',
				'action'=>'insert',
				'fields'=>array('entity_type'=>$who->entity_type() ), // �������� ��������� "Entity_" �� �������� ������.
				'set_uni'=>$who->id
			);
		}
		else //if ($storage_rules['uni_combo']) // ��� ���������, ������� �� ������� ������� �������� � ���� ������ �����, ������ �� ������� - ��������-��������.
		{
			$result=array(
				'table'=>static::prefix.static::get_entity_table($who, $storage_rules),
				'action'=>'insert',
				'set_uni'=>$who->id
			);		
			// ������ fields ���, ������ ��� ��� �������� ��� �������� � ��������� (�� �� �����, ������ ��������� ������), � ����-�������� ��������� �����������.
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
