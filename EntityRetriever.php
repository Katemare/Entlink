<?
// ���� ����� ������ �������� ������� �� ������ � ��������� �� ������ �� ���� ����������, ����� ��������� ����� �������� � ��. ��������, ���� �������� ����� ������ 20 �������� � ���������� ����������������, �� ������ 20 �������� "������ � �����-�� ���������������" ���� ����� ������ ��������� ������ "������� � ���������������� 1, 2, 3...". ����� ������� �������� ���� ��������� ������.
// �������������, ��� ���� ��������� �����:
// 1. ������� ������� ���������, ��� �� ����������� �����-�� ������. �������� ����������.
// 2. ������ ������� ���������: �� ���� ������ �������! ������ ����� ������! ��������� ��������� ������ � �������� ��� ����� ������ ������ � ������� ������ �������.
// 3. ������, ������������� ������, ���� �� �� ���������. ����� �������� ������� ������� ����� � ������ ������ ��������, �� ��� ������ �� �� (������ ��� ������ ��� ���� ��������).
// ����� ����, �������� ������ �������� �������� ������������� (���������) ������. � �������, ����� �������, ���� ��� �������� � ������ �������, �����������... ������ �� ������ ���������, ����� ����������� ����� (��� ������ ��������), � ����� - ��� (��� ������� ��������� � ��������).

class EntityRetriever extends EntityDBOperator
{
	public static $queries=array();
	// � ���� ������� �������� �������, ������� ���������� ���������. ��� �������� � ���� ��������, �������� ������������ EntityDBOperator.
	// ������� ������ ���������� � ����� ������� �� uniID � ������� ������������� �� ��������. ����� ������������ ������� ������ ������ uniID � ��� �� �������, �� ������ ����������� � ������ where.
	
	public static $data=array();
	// ����� �������� ������. �������� �� ������� �� �� ������ ����� ������� ���������, ����� �� ����������, ������� �������� ��������� ������ � ����� ��� ��� �� �����������. � ����� ������ ����� �������� � php �������� ��� ���� ��������� � ������, ���� �� ����� ������� ���������.
	
	public static $compiled_tables=array();
	// ��� ������ ������������ "������������� => ������ ������", ������� ����������, ����� ������� ����� ����� ������.
	
	// ��� ������� �������� ���������, ��� ������-�� ������� ����������� ������. ��, ����� ������, �������� ���������� �� �������� $rules[storage] ����� ������� (��� �� ������� ���������, ���� �� ������).
	// ��� ������� ������ �������� ������ ������� ��������-����������, ��������� ������ � �� ������ ������ � ������, � ����� ������ ��� �������� ������ �� do_input. ������������� ����������, ����� � �������� �������� �� ������ �������� ��� �������.
	public static function req($who, $storage_rules='')
	{
		if ($storage_rules==='') $storage_rules=$who->rules['storage'];
		
		if ($storage_rules['method']!=='uni') return; // STUB - ����� ������ ���� ��������� ������. ��������, �� ������� ������������ �������������� �, �������������, ������������� ���� ���������� ��� ��������� ���� � ��������, �� ������ �������� ��� �������.

		if ($who->uni<1) return; // STUB - ����� ������ ���� ��������� ������. ���� �� �� ����� ��������������, �� �� ����� �������� ������.
		// �������� ������ ������, ������ �� ������� ����� ���� ������.
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
	
	public static function get_input($who, $storage_rules='') // ���� �������� ������ ����������� ��� ���������� ������� $data � �����, ����������� � �������� do_input. ��� ������� ������ "������ ����� ������!".
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
	
	// ��� ������� �������� ������ ������, ������ �� ������� ����� ������. �� ���� ���� ������ ������ ���� ��������� ������, ������� uniID=$uni ���� ��� ����� �������. ���� ������ ������ �������� ����� ������� �������� ������, ��� ���� ������ ���� �������� ������.
	public static function compile_tables($who, $storage_rules='', &$compiled=null)
	{
		if (is_null($compiled)) // �� ����������� �����. ��� ����������� ��� ���������� - ������.
		{
			if (array_key_exists($who->id, static::$compiled_tables)) return static::$compiled_tables[$who->id]; // ������ ������ ��� ��� �������, ��������� ���.
			$recursive=false;
			$compiled=array();
		}
		else $recursive=true;
		
		if ($storage_rules==='') $rules=$who->rules['storage'];
		if ($storage_rules['method']=='uni')
		{
			if ($who->uni<1) return; // STUB - ����� ������ ���� ��������� ������. ���� �� �� ����� ��������������, �� �� ����� �������� ������.
			if ($storage_rules['uni_combo']) // ������������ ������ ������� ������ - ���� ����� (��� ������� �����) � ����� ������� � ���� �����.
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
					// ��������, ���������� ���������������� ���������� � ������� ���������, ������ ���� ������ � �������������� �������.
					$table=static::$prefix.static::get_entity_table($who, $storage_rules);
					$compiled[$table][]=$who->uni;
					// "������" ��������-����������, ��������� �� ������ ������, �� ����� ������ ���� �� ����. ���������� �������� - � ������.	
				}
				// STUB: ��� ��� �� ����������� ��������-������.
			}
		}
		elseif ($who instanceof Entity_value) // �� ����� ����������� �������������� � ������������ �� ���� ���� � ������ �������.
		{
			$table=static::$prefix.static::get_entity_table($who, $storage_rules);
			$compiled[$table][]='master';
		}
		
		// ��� ���������� ����� ������ �������� ���� ���������� ��������.
		if ($who instanceof Entity_combo)
		{
			foreach ($who->byrole as $role=>$list)
			{
				$entity_storage_rules=$who->model[$role]['storage'];
				foreach ($list as $entity)
				{
					static::compile_tables($entity, $entity_storage_rules, $compiled); // ����������� ����� - ������ $compiled ��������� �� ������ � �����������.
					
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
		
			static::$compiled_tables[$who->id]=$compiled; // ��������� ���.
			return $compiled;
		}
	}
}


?>