<?

#########################
### Entity base class ###
#########################
// ��� ������� �����, �� �������� ���������� ��� ������ ������ � ����� ������.

abstract class Entity
{
	public $lang='rus'; // ����, �� ������� ����� ���������� ���������.
	public $html_prefix=''; // ��� �����, ���� � ����� ���� ��������� ��������� ������� ����.
	static $lastid=0; // ����� ��������� ��������� ��������. ���� ���������� � 1.
	public $id=0; // ������������� ������ �������� ������ ������� ���������.
	public $uni=0; // ������������� ��������, ������� ����������� ��������� ������ � ��. ���� �� � ����.
	
	public $data=array(); // ������ ��������.
	public $rules=array(); // ������������� ���������� � ���, ��� ���������� � ������� ��������.
	public $toretrieve=array(); // ������� �������� � ���� ������ (���� �� �������, �� "��").
	public $valid='nodata'; // ������ �����������, ����� ���� �� ��������� ��������:
	// nodata - ��� ������.
	// unknown - ���� ������, �� �����������.
	// invalid - ���� ������, ���� ��������� � �������� �������������
	// valid - ���� ������, ���� ��������� � �������� �����������
	
	static $entities_list=array(); // ������ ���� ���������, ����� ������ ����� ���� ����� ������ �������� �� ��������������.
	static $entities_by_uni=array();
	
	public function __construct($args='')
	{
		// ��� �������� ������ ���������� ���� �� �������, ����� ������������� ������ ��� ����������.
		// ��� ������������� ������������ ��� ������� ��������� ����� ����� �� ����� �������� � �� ����� ��������� � ��������������, ����������� � ���� ������.
		Entity::$lastid++;
		$this->id=Entity::$lastid;
		
		Entity::$entities_list[$this->id]=$this;
		
		// � ������� $args ���������� ��������� � ����� ��� �������� ��������. �� ������ ������ ����������, �� ����������� �� ������ �� ������ ��������, ��� ��� ������������ ������.
		if (is_array($args))
		{
			$html_prefix=(string)($args['html_prefix']);
			if (array_key_exists('storage', $args)) $this->rules['storage']=$args['storage'];
			if (array_key_exists('uni', $args)) $this->uni=$args['uni'];			
		}
		if (!is_array($this->rules['storage'])) $this->rules['storage']=array();
		
		if (
			(isset($this->rules['compatible_table'])) &&
			(!array_key_exists('value_table', $this->rules['storage']))
			)
		{
			$this->rules['storage']['value_table']='entities_'.$this->rules['compatible_table']; // ������ ����� �� ���������� �� Entity_int, ��� ��� ����� �������� � ����� �������.					
		}
		
		$this->html_prefix=$html_prefix;
	}
	
	// DEBUG
	public static function generate_uni()
	{
		foreach (Entity::$entities_list as $entity)
		{
			if ($entity->rules['storage']['method']==='uni') $entity->setUni($entity->id);
		}
	}
	
	public static function uni_exists($uni)
	{
		return array_key_exists($uni, Entity::$entities_by_uni);
	}
	
	public function setUni($uni)
	{
		$olduni=$this->uni;
		if ($olduni>0) unset(Entity::$entities_by_uni[$olduni]);
		$this->uni=$uni;
		if ($uni>0) Entity::$entities_by_uni[$uni]=$this;
		// STUB: ��� ��������� ������
	}
	
	public function entity_type()
	{
		return substr(get_class($this), 7);
	}
	
	// ���������� ������� �� ��������� ������ �� ��.
	public function req()
	{
		if ($this->valid!=='nodata') return; // ���� ������ ��� ����, ���������� �������.
		EntityRetriever::req($this);
	}	
	
	public function retrieve() // ������ ��������� ������ �� ��������� ������ �� ��. ������� �������� ��� ����� �����, ����� ������ ����� ����������.
	{
		if ($this->valid!=='nodata') return; // ���� ������ ��� ����, ���������� �������.	
		EntityRetriever::retrieve();
	}
	
	abstract public function receive(); // ��� ������� �������� ��������, ����� �� ������� ������. ��� ����� ��������� � ������, ��� ������� retrieve ��� ������ ��������, ������ ��� ������ ���������� ������ � ����� ��������� ���������.
	
	// ���������������� ���� ����� �����, ������ ��� ������� ����������� ������.
	// $input -  ������ � ������� ������������ �������. ���� ������, �� ������������ ������ $_GET � $_POST
	// $ready - ���� ������, �� ������ �� ����������� � �� ��������������, �������� ����� �����������.	
	public function input($input=false, $ready=false)
	{
		$this->do_input($input); // ������ ���������������� ���� ������ � $data.
		$this->after_input($ready); // ������������� ������, ��������� �� ������������ � ��� �����.
	}
	
	public abstract function do_input($input);
	
	public function after_input($ready=false)
	{
		if ($ready) $this->valid='valid'; // ���� �������� ������������� ������� ���������� ��������. ������ ��� �������, ����� ������ ��� ����� ���������! � �������, �������� �� ���� ������.
		else
		{
			if (!is_null($this->data['value'])) // ���� ���� ������...
			{
				$this->invalidate(); // ����������, ��� ������ ����, �� �� ������������ ����������.
				$this->safe(); // �����������!
				$this->validate(1); // ��������� ������������ (��������).
			}
			else $this->clear(); // ���� ������ ���, �� ���������, ��� �� ���.
		}
	}
	
	public abstract function safe(); // ���������� ������������ �������� ��� ���������������� �����. ������������ ������.
	
	public function validate($force=0) // �������� ������������ ��������.
	// ��� ������� ���������� ������ �� ���� ���������: valid - ������ ������������, � check - ������ ��������.
	// �������� valid ����� ��, ��� � ��������� ���������� valid.
	// �������� check:
	// nop - �������� �� ���� (������ ��� ������ �����������)
	// previous - ��������� ��������� ���������� ��������.
	// allgood - ��������� ��������� �������� �� ���������, ������� ����� ������ ������� �����������. ������� �������� ���: "�������� ���� � �������� �������!
	// STUB: �����, ��� ������� ����� ������ ��������� ���������������� ������.
	{
		if ($this->valid=='nodata') return array ('valid'=>'nodata', 'check'=>'nop');
		if (($this->valid<>'unknown')&&($force==0)) return array ('valid'=>$this->valid, 'check'=>'previous');
		$this->valid='valid';
		return array('valid'=>$this->valid, 'check'=>'allgood');
	}
	
	public function invalidate() // ����� ������������ ������. 
	{
		$this->valid='unknown';
	}
	
	public function clear() // ����� ������ (�� ������ ��, �������.)
	{
		$this->valid='nodata';
	}
	
	public abstract function display($style='raw'); // ����� ������.
	
	public abstract function store($rules=''); // ���������� ������. STUB - ���� ��� ������ ��������� ��������.
}

#########################
### Entity link class ###
#########################

class Entity_link
{
	// STUB
	public function do_input($input)
	{
	}
	
	// STUB
	public function safe()
	{
	}
	
	// ������ ����� ����� ���������� ������������ � ���� ������������ ��������� �������� � ������ �� ����������� �� ��������. ������ ����� �������� ����������� �������������� � �������� ��������������� �����, ����������� ��������� ��� �������.
	public function display($style='raw')
	{
		return '';
	}
	
	public function store($rules='')
	{
		return EntityStorage::store_link($this, $rules);
	}
}

#############################
### Combined Entity class ###
#############################
// ���� ����� ��������� ������� ���������. ����������� ��, ����� ������� ���������-������, ��������� � ����� ������.

abstract class Entity_combo extends Entity
{
	public $model=array(); // ������, �� ������� �������� ���������� ��������. STUB
	// ������ ����� ������ ������ ��������-����� �������� � ���, ����� ����������� ������������� ����� � ��������� ��������, � ����� - ����������� ������������� �������� � ����������� ����������������. �������� �������������� ����� ��� ������������ �������� ����� � ������ ������ �� ������, � ����� ��� ���������� � ��������, ������������� ������ ����� (MediaWiki, BattleEngine...).
	
	public $entities=array(); // ������ �� ����� ����������� ����������, �� ������
	public $byrole=array();  // ������ �� ����� ����������� ����������, �� ���� (������ ���� ����� ���� ���������)
	public $rolebyid=array(); // ������ ����� �� �������������� ��������. ����� ��� �������� �������� �� ������ �� �����, ����� �� ����������.
	public $format=array(); // ������ �������� �����������, ���� '������, %name%!'. ������ ��������� ������� �������� � ������ raw.
	public $style=''; // ������������ ��� �������� �����, ��������������� ������, � ���������� ���� ��� closure'��.
	public $built=false;
	
	public function receive()
	{
		foreach ($this->model as $role=>$options)
		{
			if ($options['storage']['method']=='uni')
			{
				// WIP
			}
			else
			{
				// ��� ����, ������� �������� � ������� ������ ��������� (�� ������� ������), ��� ��������, ������ ��� �� �� ����� ���� �������������� ����������. ��� ����� �� �������� �������������. �������������, �� ����� ������ �������� ������� �������� ����.
				// ����������, ���������� �� �� ������ uni, �������� ���������� � ������ ������ �������� ������� ����� ���������� ���������. ���� ��� �� ��������������, ��� ����� ���������� ����� ���� �������������.				
				$class='Entity_'.$options['class'];
				if ((is_subclass_of($class, 'Entity_value'))||(is_subclass_of($class, 'Entity_combo')))
				{
					foreach ($this->byrole as $index=>$entity)
					{
						$entity->receive();
					}
				}
				// �����, ���������� �� �� ������ uni, ����������.
			}
		}
		
		$dbop='EntityRetriever';
		if (
			($this->rules['storage']['method']==='uni') &&
			($this->uni>0) &&
			(array_key_exists($this->uni, $dbop::$links))
			)
		{
			$all_links=$dbop::$links[$this->uni];
			foreach ($all_links as $connection=>$links)
			{
				if ($connection==='A:A is combo parent of B')
				{
					foreach ($links as $uni=>$link)
					{
						if (!Entity::uni_exists($uni))
						{
							$link_entity=new Entity_link(array('uni'=>$uni));
							$link_entity->receive();
						}
						$child=$link['uniID2'];
						$details=unserialize($link['details']); // STUB
						if (Entity::uni_exists($child)) $entity=Entity::$entities_by_uni[$child];
						else $entity=$this->build($details['role']);
						$this->add($entity, $details['role']);
					}
				}
			}
		}
	}
	
	public function build($role='') // ������ ���������� �������� �������� ������.
	// ��� ������� �� ������������� ���������� ����� ����������� ������ ������, ����� ��� ��� �������� ������ �� �����.
	{
		if ($role=='') // ����� ��� ���������� ���� ������.
		{
			if ($this->built) return;		
			foreach ($this->model as $role=>$options)
			{
				if ($options['optional']) continue; // �������, ��� ����������� �� �����������, �� ��������� �� ���������.
				$entity=$this->build($role); // ������ ������ ���������� ����.
				$this->add($entity, $role); // � ��������� ���.
			}
		}
		else // ����� ��� ���������� ������� ���������� ����.
		{
			$options=$this->model[$role]; // �������� ��������� ������ ����.
			$class='Entity_'.$options['class']; // ��� �������� ������ �������, ������� ����� �������.
			
			// ��� ���������, ������� ���������� ������������.
			if (is_array($this->model[$role]['new_args'])) $args=$this->model[$role]['new_args'];
			else $args=array();
			if (array_key_exists('storage', $this->model[$role])) $args['storage']=$this->model[$role]['storage'];
			
			// ���������� ��������� ������ �� ���������� ��������, ��������������� � ���� ������, ����� ��������� �����.
			if ($this->rules['storage']['method']==='uni') $args['storage']['combo_parent']=$this;
			else $args['storage']['combo_parent']=$this->rules['storage']['combo_parent'];
			
			// ������� ������ ��������� � ������������ ��������.
			$args['html_prefix']=$this->html_prefix;
			
			// ������, ����������.
			$entity=new $class($args);
			return $entity;
		}
		
		$this->built=1;
	}
	
	// ����� ��������� ����, �� ������� ��������� ���� ���������� �������� �� �������.
	// ������ ����� ��������� ������, �������� ���� ��������������� ������ ������������ ������ ������ � ���� ������.
	public function do_input($input=false)
	{
		foreach ($this->entities as $entity)
		{
			$entity->do_input($input);
		}
	}
	
	public function listFormat() // DEBUG! ������� ��� ������: ������������ ������� ������ ������.
	{
		$format=array();
		foreach ($this->model as $role=>$options)
		{
			$format[]='%'.$role.'~%';
		}
		$format=implode('<br>', $format);
		$this->format=$format;
	}
	
	public function safe() { } // ��� ������� ������ �� ������, ������ ��� ���� �������� ���������� �������������.
	
	public function validate($force=0) // �������� ���� ��������. �� ��������� ��������� ��������. ��� ���� �������� �������.
	{
		$result=parent::validate($force);
		if ($result['check']<>'allgood') return $result; // ���� �������� "��� ��������" � "�� ���������� ��������" �������������� �� ����.
		
		$result=array('check'=>'performed'); $valid='valid'; // �������� �� ���������.
		
		// ����������� ������ ���������� ��������. ���� ���� �� ���� �� ����������, �� ��� ���������� �����������.
		foreach ($this->byrole as $role=>$list)
		{
			foreach ($list as $index=>$entity)
			{
				$res=$entity->validate($force);
				if ($res['valid']<>'valid') $valid='invalid';
				$result['byentity'][$role][$index]=$res; // ��� ���� ������ ���������� �������� ��������� ���������.
			}
		}
		$result['valid']=$valid;
		$this->valid=$valid;
		return $result;
	}
	
	// ���������, ���� �� �������� � ������ ����. ���� ������ ������, �� ��� ���������� ��������. ���� �� ������, �� ��� ������� ��������.
	public function exists($role, $index=0)
	{
		return is_object($this->byrole[$role][$index]);
	}
	
	// ��������� �������� � ������ ���������� ���������.
	public function add($add, $role='norole')
	{
		if (is_array($add)) // ���� ����� �������� ����� ���������...
		{
			foreach ($add as $key=>$entity)
			{
				if (is_numeric($key)) $this->add($entity, $role); // ������ � �������-������� ���� ���� �� ��������� $role.
				else $this->add($entity, $key); // ������ � �������-�������� ���� ���� � �������� ����. � ��� ����� � �������� $entity ����� ��������� �������� ������.
			}
		}
		elseif ($add instanceof Entity)
		{
			$this->entities[$add->id]=$add;
			$this->byrole[$role][]=$add; // ������� ���� ������, ������ ��� ���� �����-�� �� ��� ���������, �� ������ ���������� ���������.
			$this->rolebyid[$add->id]=$role;
		}
		else { echo '������!'; exit; }
	}
	
	// ������� �������� �� ������ ���������� ���������.
	// $remove - ��������, ������� ����� ������� (��� ������)
	// $recursive - ��������, ����������� ������ ��� ����������� ������, ����� ����������� � ����������� �������.
	// $tosort - ���� �������� ������������ ������, ���������� ������ �����, ������� ����� ����� �������������.
	public function remove($remove, $recursive=0, &$tosort=array() )
	{
		if (is_array($remove))
		{
			$tosort=array();
			foreach ($remove as $entity)
			{
				$this->remove($entity, 1, $tosort);
			}
			$tosort=array_unique($tosort); // ������� ����� �� ������ �����, ������� ���� �������������.
			foreach ($tosort as $ts)
			{
				$this->byrole[$ts]=array_values($this->byrole[$ts]); // ��������������� ����, ����� ��������� ������� ����� "0, 1, 2, 5".
			}
		}
		else
		{
			$id=$remove->id;
			unset($this->entities[$id]);
			$role=$this->rolebyid[$id];
			$rolekey=array_search($entity, $this->byrole[$role], 1);
			unset($this->byrole[$role][$rolekey]);
			unset($this->rolebyid[$id]);
			
			if ($recursive) $tosort[]=$role;
			else $this->byrole[$role]=array_values($this->byrole[$role]);
		}
	}
	
	// ������������� ������.
	public function display($style='raw')
	{
		// ���������� ��������� ������������� ���������� ������, ����� �������� ���, ������ ���� ������������.	
		return EntityDisplay::display_combo($this, $style);
	}
	
	// ������ ���������� �������� preg_replace_callback �� ���������� �������, � ����� ������ ����� ���������� ��������.
	public function display_subentity($role, $index=null, $style='')
	{
		// ���������� ��������� ������������� ���������� ������, ����� �������� ���, ������ ���� ������������.
		return EntityDisplay::display_subentity($this, $role, $index, $style);
	}
	
	//STUB - ������ ���������� ������ � �� ��������� ������������ ��������.
	public function store($rules='')
	{
		return EntityStorage::store_combo($this, $rules);
	}
}

###########################
### Value-type entities ###
###########################
// ��� �������� ������ ������� ������, ������ ���������� ������� �����. ���� ���-�� �������� ����� ����� <input> ��� <select>, �� ������ ���� � ��������, ������������ �� Entity_value.
{

abstract class Entity_value extends Entity
{	
	public function __construct($args='')
	{
		parent::__construct($args);
		$this->rules['html_name']=(string)($args['html_name']); // ����� ��� � ������ ����� �� �����, ��� � ����� ��������� ������ ������ $input, �������� �����.
		$this->rules['title']=$this->rules['html_name']; // STUB. � ������� ����� ������� �� �������.
	}
	
	// �������-�� ���-�� ����������!
	public function display($style='raw')
	{
		// ���������� ��������� ������������� ���������� ������, ����� �������� ���, ������ ���� ������������.	
		return EntityDisplay::display_value($this, $style);
	}
	
	// ���� �������� (� ���������) �������������� ���� ���������������� ���� � ������� ���� ����� �� ��.
	public function do_input($input=false)
	{
		$val=null;	
		if ($input===false) // ���� ������������ ����� �������� �� ����� ������������...
		{
			$name=$this->input_name(); // ��������, ��� ������ ���������� ����������. ��� ��������� �������.
			global $_GET, $_POST;
			if (array_key_exists($name, $_POST)) $val=$_POST[$name];
			elseif (array_key_exists($name, $_GET)) $val=$_GET[$name];
		}
		else // ���� ������������ ��������� ������ ������, � �������, ���������� �� ���� ������...
		{
			$name=$this->rules['html_name']; // ������� �� ������������, ������ ��� ��� ������������� �������� ���� ���������� �������� � ������ �����;
			if (array_key_exists($name, $input)) $val=$input[$name];
		}
		
		$this->data['value']=$val; // ���� ���� �������� �� ���� �������, ����� �������� ��� � ������.
		
	}
	
	public function receive()
	{
		$dbop='EntityRetriever';
		$table=$dbop::$db_prefix.$dbop::get_entity_table($this);
		$field=$dbop::get_value_field($this);
		if ($this->rules['storage']['method']==='uni') $uni=$this->uni;
		else $uni=$this->rules['storage']['combo_parent']->uni;
		// STUB - ��� ��������� ������.
		$input[$this->rules['html_name']]=$dbop::$data[$table][$uni][$field];
		
		$this->input($input, 1);
	}
	
	public function input_name() // ������������ ��� ���� �����. � ��������, ����� ���� �� ������������ ���, �� �������� �� ���������.
	{
		return $this->html_prefix.'_'.$this->html_name;
	}
	
	//STUB - ������ ���������� ������ � �� ��������� ������������ ��������.
	public function store($rules='')
	{
		return EntityStorage::store_value($this, $rules);
	}	
}

// ��������� ������.
class Entity_text extends Entity_value
{
	public function safe()
	{
		$this->data['value']=$this->normalString($this->data['value']);
	}
	
	// ��� ����������� ������� ��� �������������� ��������� ������. ��� ������ ���������:
	// 0. ��������� ������������� �����, ����������� php.
	// 1. ���������� �������, ������� ����� ��������� � ������� � ���� ������. � ��� ����� �������� �� ��������� ��������.
	// 2. ������� ����������� �������, ������� �� ����� ���������� � ������� ��������� �����, ��� �������� �� ���������.
	// 3. ��������� �������� ���������� ����� � ������ ������.
	// 4. �������� �������, ������� ����� �������������� ��� html-��������.
	public function normalString($str)
	{
	  return str_replace(           // CRLF -> LF, ������ ����� ���������
		array("\r\n", "\r", "\t", '\'', '\\'),
		array("\n", "\n", '  ', '&#39;', '&#92;'),
		preg_replace_callback(      // �������������� ���������� HTML-���������
		  '/&amp;#(\d{2,5});/',
		  array($this, 'normalString__automata'),
		  preg_replace(             // ��������� ���� ������� ��������� ��������
			'/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x84]/',
			'',
			htmlspecialchars(stripslashes($str))
		  )
		)
	  );
	}
	
	public function normalString__automata($matches)
	{
	  $code = intval($matches[1]);
	  return (($code < 32)
		|| (($code >= 127  ) && ($code <= 132  ))
		|| (($code >= 8234 ) && ($code <= 8238 ))
		|| (($code >= 55296) && ($code <= 57343))
		|| (($code >= 64976) && ($code <= 65007))
		|| ( $code >= 65534)
	  ) ? $matches[0] : "&#$code;";
	}	
}

// ����� �����.
class Entity_int extends Entity_value
{
	public function safe()
	{
		$this->data['value']=(int)$this->data['value'];
	}
}

// ����������� ����� - �� ���� ����� �������������.
class Entity_natural extends Entity_value
{
	public function __consruct($args='')
	{
		$this->rules['compatible_table']='entities_int';
		parent::__construct($args);
	}

	public function safe()
	{
		$this->data['value']=(int)$this->data['value'];
		if ($this->data['value']<1) $this->data['value']=1; // STUB: ��� ��������� ������ ���� � ������� ��������� ������.
	}
}

// ����� � ��������� ������, �� �� ����� �� ������������� (������-��)
class Entity_real extends Entity_value
{
	public function safe()
	{
		$this->data['value']=(float)$this->data['value'];
		if ($this->data['value']<0) $this->data['value']=0; // STUB: ��� ��������� ������ ���� � ������� ��������� ������.
	}
}

}
############################################
### ��������������� �������� �� �������� ###
############################################

// ����� ��� �������� ������ �������� ������ ������ �����, �������� ��� ������. ��������, ����� ��������� �� ������ ������.
class Entity_translation extends Entity_combo
{
	public $code='name';
	// ��� �����, ��� �������� �������� ��������. ������������ � ��������� �������:
	// �������� ����� ����� - name_rus, name_eng...
	// �������� ����� � ���� ������ ��� ��������� � ������. ����������.
	// ��������� �������� ���������-�������. "�����������" ��-���������? "comments"!
	
	// ������ ������ ����������� ��� �����, ���� ������� ����� ������ ���� ��������������.
	// �������������� ����� �� ��������� ��� ���������� �������.
	// ���� - ��� �������� ������.
	public $model=array(
		'rus'=>array(
			'class'=>'text',
			'storage'=>array('entity_table'=>'pokemon_main', 'by_html_name'=>1)
		),
		'eng'=>array(
			'class'=>'text',
			'storage'=>array('entity_table'=>'pokemon_main', 'by_html_name'=>1)
		),
		'jap'=>array(
			'class'=>'text',
			'storage'=>array('entity_table'=>'pokemon_main', 'by_html_name'=>1)
		),
		'jap_kana'=>array(
			'optional'=>1,
			'class'=>'text',
			'storage'=>array('entity_table'=>'pokemon_main', 'by_html_name'=>1)
		),
		'ukr'=>array(
			'optional'=>1,		
			'class'=>'text',
			'storage'=>array('method'=>'uni')
		)
	);
	public $rules=array(
		'storage'=>array(
			'method'=>'uni',
			'uni_combo'=>1,
			'entity_table'=>'pokemon_main'
		)
	);
	
	// ��� ������� �������� ���� �� ���� ��������� ������. 
	public function do_input($input=false)
	{
		// ��������� ��� �����...
		foreach ($this->model as $role=>$options)
		{
			$new=true; // ��� ���������� ��������� ���, ��� �� ������ ����� ������-������ ��� ���� ������������.
			if ($this->exists($role)) // ���� ������ ��� ����, ���� ���.
			{
				$entity=$this->byrole[$role][0];
				$new=false;
			}
			else
			{
				$entity=$this->build($role); // ���� ���, ������ �����.
			}
			
			$entity->input($input, $ready); // ������ ������ ��������.
			if (($entity->valid<>'nodata')&&($new)) $this->add($entity, $role); // ���� ������ ����� � �� ������� ������, �� �� ����������� �� ���������� ��������.
			elseif ( ($this->valid=='nodata')&&(!$new)&&($this->model[$role]['optional']) ) $this->remove($entity); // ���� ������ ���, ������ ������, �� �������������� - �� �� �� ��������� �� ������.
		}
	}
	
	public function build($role='')
	{
		// ��� ���������� �������� ������ ����� ��� ���� �����, ��������� �� ���� ����� � ���� �����.
		foreach ($this->model as $r=>&$options)
		{
			$options['new_args']['html_name']=$this->code.'_'.$r;
		}
		
		if ($role=='') return parent::build();
		$entity=parent::build($role);
		return $entity;
	}
	
	// ��� ������� ��� �������� �� �������. ��� ���������� ������� ����� �� ������ �����.
	public function translate($lang)
	{
		// ���� �������� � ���� ����� ����, ���������� ���.
		if ($this->exists($lang)) return $this->byrole[$lang][0]->display('raw');
		
		// ���� ���, ���������� ������ ����������. ���������, ��� ����� ����������� � ������� ����������. STUB.
		foreach ($this->byrole as $role=>$list)
		{
			if ($role==$lang) continue;
			$entity=$list[0];
			if ($entity->valid=='valid') return $entity->display('raw');
		}
		
		// ���� ��� ������, ���������� ��� �����. STUB.
		return $this->code;
	}
}

// ��� ������, ������� ������ ��������� ��� ��������� �����, ������ � ������ ������.
// � ������ �� ������ �������� ���: � ������ ���������� ����������� �������� ������ �������� ���������� � ���� � ������� "��� ����������� ������� ������-�� ����� �� �����-�� ����". �� ����������� �������. ����� ���������� ����� ��������, �� ����� ���������� � �� �������� ��� ������ ��������. ����� ������� ���������� � ���� ��� �������� �� ���� �������������.
// ��� ��� ������ ���-�� ������������, �� ������ ����� �� ������ �� � php.
class Entity_dictionary extends Entity_combo
{
	public $def='rus', $cache=array(); // ��� ��������� ������� ����. ���� �� ��������� - ��� ����, �� ������� ����������� ������� ����� ���������. ���� ������ ���� ���� �� ����� �����.

	// ������ �������� ����������� ����� �� ������ ����. ��� - ��� ���������� ������� �������� ����� ��� ����� ������, ������� ���� ���������.
	public function translate($code, $lang='')
	{
		if ($lang=='') $lang=$this->def; // ���� ���� �� ������, ������ �� ���������.
		if (is_array($code)) // ���� ������������� ������ ���������, �� ��������.
		{
			$trans=array();
			foreach ($code as $c)
			{
				$trans[$c]=$this->translate($c, $lang);
			}
			return $trans;
		}
		else // ���� ������������� ���������� ����� ��� ���������.
		{
			if (($lang==$this->def)&&(array_key_exists($code, $this->cache))) return $this->cache[$code]; // ���� ���� � ����, ���������� ������.
			$trans='';
			if (!$this->exists($code)) $trans=$code; // ���� ���� ��������� � ����� ����� ���, ������� ������� ���.
			if ($trans=='') $trans=$this->byrole[$code][0]->translate($lang); // ���� ��������� ����, ������� ��������� � ������� ����.
			if ($trans=='') $trans=$code; // ���� �����, ���������� ���.
			if ($lang==$this->def) $this->cache[$code]=$trans; // ���������� ���.
			return $trans;
		}
	}
}

// ��� �������� ����� ��������, ���� � ����������.
class Entity_neo extends Entity_combo
{
	public $model=array(
		'id'=>'natural',
		'name'=>'translation',
		'nickname'=>'translation',
		'type1'=>'poketype',
		'type2'=>'poketype',
		'height'=>'real',
		'weight'=>'real',
		'area'=>'pokearea',
		'body'=>'pokebody',
	);
}
?>