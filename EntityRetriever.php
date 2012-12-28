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
	// � ���� ������� �������� ���� "������� => ������ ���������������". ��� ������� �� ��������� ������.
	
	public static $compiled_tables=array();
	
	public static $link_queries=array();
	/* ������ ��������������� ���������, ����� ������� ����� ��������. ���������� � ���� ���:
	
		"����. ������������� => ������('A'=>���� �����, 'B'=>���� �����)". ���� � A � B ������ ���������, php �� ����� �������� ����� � ������ �� ��� ���� ������.
		
		���:
		
		"����. ������������� => 'all' - ��� �����.
	*/
	// STUB: ����� ���-�� �����������, ����� ����� ��������, � ����� ������������. ��������, �� ������ ����� ������ ��������� ���, ��������, ������ �� ������.
	// ��������, ����� ������� �� ������ ���������������, � ������ ������ �� ��������? ����� �� ����� ����������, ������ �� �������� �������� implode.
	
	public static $data=array();
	// ����� �������� ������. �������� �� ������� �� �� ������ ����� ������� ���������, ����� �� ����������, ������� �������� ��������� ������ � ����� ��� ��� �� �����������. � ����� ������ ����� �������� � php �������� ��� ���� ��������� � ������, ���� �� ����� ������� ���������.
	
	public static $links=array();
	/* ����� ��������� ������ � ������ � ��������� ����:
	
		����. ��������� �������� => ������ (
			������:��� ����� => ������ (
				����. ����� => ������ �� ��
				����. ����� => ...
			)
			������:������ ��� ����� =>...
		)
		����. ������ ��������� �������� => ...
	*/
	
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
		
		if (array_key_exists('links', $storage_rules))
		{
			static::req_links($who->uni, $storage_rules['links']);
		}
	}
	
	// STUB: ��������� �������, ������� ����� �������� ������ ���� "������� ���� ��������, ������������ �� ��������� 10 ����"; "������� ���� �������� ������������ ����, ����������� �� ��������"...
	// ��������, ��� ������� ������ ���� ��������� ��������, ���� EntityLister.
	
	// ��� ������� ��������� � ������� ������, �� ��������� ������ �� ������� $table � ����������� ���������������� $id. ��� ����������� ������ ��� ������ ����, �� ��� entities_link ��� ��� uniID1 � uniID2. �������� ������� ����� ��� $db_prefix - �� ����������� � ��������� ������.
	// FIX: ������ ��� �� ��������� ��������� ������ ������, ������� ������ ���� � ���������, � �������� ����� ������ ��� ������������� � ������� ��������, � ������� ������� ��������� �� ����.
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
			// ���� ������ ����� �������������� �� ���� ������� ��� ���� ��������, ����������.
			if ((is_array(static::$data[$table]))&&(array_key_exists($id, static::$data[$table]))) return;
			
			// ���� ������������� ��� ������������ � ������� �������� �� ��� �������, ����������.
			if ((is_array(static::$queries[$table]))&&(in_array($id, static::$queries[$table], 1))) return;
			
			// �� � �������, ��������� � �������.
			static::$queries[$table][]=$id;
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
		else
		{
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
	
	public static function get_links($who, $storage_rules='')
	{
		// WIP: ����� �������� (�� ����?) ������ ������, ��������� �������!
		
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
			// ��������� ������� �������.
			$queries=array();
			foreach ($queries as $hash=>$list)
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
					static::$data[$row['uniID']]=$row;
					static::$links[$row['uniID1']]['A:'.$row['connection']][$row['uniID']]=$row;
					static::$links[$row['uniID2']]['B:'.$row['connection']][$row['uniID']]=$row;
				}
			}
		}
	}
	
	public static function retrieve($who, $storage_rules='') // ���� �������� ������ ����������� ��� ���������� ������� $data � �����, ����������� � �������� do_input. ��� ������� ������ "������ ����� ������!".
	{
		$db=parent::$db;	
		foreach (static::$queries as $table=>$ids)
		{	
			$query=array(
				'action'=>'select',
				'table'=>static::$db_prefix.$table,
				'where'=>array('uniID'=>$ids),
			);
			
			// STUB! � ������� ��� ����� ������ ���� ����������� �����, � �� ������ �������� ��� ����� ���� �������� ��������.
			if ($table=='entities_link')
			{
				$query['where']['uniID1']=$ids;
				$query['where']['uniID2']=$ids;
				$query['where_operator']='OR';
			}
			
			$query=static::compose_query($query);
			echo $query.'<br>'; // DEBUG
			$list=$db::query($query);
			
			while ($row=$db::fetch($list))
			{
				static::$data[$table][$row['uniID']]=$row;
				if (($table=='entity_link')&&($row['connection']=='A is combo parent of B'))
				{
					// WIP
					//static::req(array($row['uniID1'], $row['uniID2']));
				}
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
	}
	
	// ��� ������� �������� ������ ������, ������ �� ������� ����� ������. �� ���� ���� ������ ������ ���� ��������� ������, ������� uniID=$uni ���� ��� ����� �������. ���� ������ ������ �������� ����� ������� �������� ������, ��� ���� ������ ���� �������� ������.
	// WIP
	public static function compile_tables($who, $storage_rules='', &$compiled=null)
	{
		if (is_null($compiled)) // �� ����������� �����. ��� ����������� ��� ���������� - ������.
		{
			if (array_key_exists($who->id, static::$compiled_tables)) return static::$compiled_tables[$who->id]; // ������ ������ ��� ��� �������, ��������� ���.
			$recursive=false;
			$compiled=array();
		}
		else $recursive=true;
		
		if ($storage_rules==='') $storage_rules=$who->rules['storage'];
		if ($storage_rules['method']=='uni')
		{
			if ($who->uni<1) return; // STUB - ����� ������ ���� ��������� ������. ���� �� �� ����� ��������������, �� �� ����� �������� ������.
			if ($storage_rules['uni_combo']) // ������������ ������ ������� ������ - ���� ����� (��� ������� �����) � ����� ������� � ���� �����.
			{
				$table=static::get_entity_table($who, $storage_rules);
				$compiled[$table][]='uni'.$who->id;
			}
			else
			{
				$table='entities';
				$compiled[$table][]='uni'.$who->id;
				
				if ($who instanceof Entity_value)
				{
					// ��������, ���������� ���������������� ���������� � ������� ���������, ������ ���� ������ � �������������� �������.
					$table=static::get_entity_table($who, $storage_rules);
					$compiled[$table][]='uni'.$who->id;
					// "������" ��������-����������, ��������� �� ������ ������, �� ����� ������ ���� �� ����. ���������� �������� - � ������.	
				}
				elseif ($who instanceof Entity_link)
				{
					$table=static::get_entity_table($who, $storage_rules); // ������ entities_link �� ����� ����
					$compiled[$table][]='uni'.$who->id;
					$compiled[$table][]='uni'.$who->data['entity1']->id;
					$compiled[$table][]='uni'.$who->data['entity2']->id;
				}				
				// STUB: ��� ��� �� ����������� ��������-������.
			}
		}
		elseif ($who instanceof Entity_value) // �� ����� ����������� �������������� � ������������ �� ���� ���� � ������ �������.
		{
			$table=static::get_entity_table($who, $storage_rules);
			if (!is_object($storage_rules['combo_parent'])) var_dump($storage_rules); //var_dump($who->rules['storage']);
			$compiled[$table][]='uni'.$storage_rules['combo_parent']->id;
		}
		
		// ��� ���������� ����� ������ �������� ���� ���������� ��������.
		if ($who instanceof Entity_combo)
		{
			foreach ($who->byrole as $role=>$list)
			{
				foreach ($list as $entity)
				{
					static::compile_tables($entity, '', $compiled); // ����������� ����� - ������ $compiled ��������� �� ������ � �����������.
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