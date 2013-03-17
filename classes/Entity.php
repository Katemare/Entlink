<?
class Entity
{
	public $type=null;

	public $data=array(); // данные
	
	// данные о данных - допустимы ли они, изменились ли, получены ли и откуда...
	public $metadata=array(
		'typed'=>false
	);

	public $uni=0;
	
	public $behavior=null, $storage=null;
	
	public function setUni($uni)
	{
		$uni=(int)$uni;
		$olduni=$this->uni;
		if ($olduni>0) unset(EntityFactory::$entities_by_uni[$olduni]);
		$this->uni=$uni;
		if ($uni>0) EntityFactory::$entities_by_uni[$uni]=$this;
		// ERR: нет обработки ошибки
	}
	
	public function build($type)
	{
		if ($this->metadata('typed')) return;
		$factory='EntityFactory_'.$type;
		$factory::build($this);
	}
	
	public function prepare_behavior()
	{
		if (is_object($this->behavior)) return;
		if (!$this->metadata('typed')) return;		
		$factory='EntityFactory_'.$this->type;
		$factory::prepare_behavior($this);		
	}
	
	public function prepare_storage()
	{
		if (is_object($this->storage)) return;
		if (!$this->metadata('typed')) return;
		$factory='EntityFactory_'.$this->type;
		$factory::prepare_storage($this);
	}
	
	public function req_dataset($context, $args)
	{
		debug('req_dataset'.$this->id.'...');
		if ($this->metadata('typed'))
		{
			$this->prepare_behavior();
			$this->behavior->req_dataset($context, $args='');
		}
		elseif ($this->uni>0)
		{
			EntityRetriever::req($this->uni, 'entities', $this);
		}
		// else { } // ERR
	}
	
	public function receive($tables, $context, $args)
	{
		debug('received '.$this->id);
		if ((!$this->metadata('typed'))&&(in_array('entities', $tables)))
		{
			$result=EntityFactory::build_by_Retriever($this);
			if (!$result) { } // ERR - not found
		}
		// elseif (!$this->metadata('typed)) { } //ERR
		$this->prepare_storage();
		$this->storage->receive($tables, $context, $args);
	}
	
	public function metadata($code='')
	{
		if ($code=='typed') return $this->metadata['typed'];
		if ($this->metadata['typed']=='') return false;
		$this->analyzeData();
		if ($code=='') return $this->metadata;
		elseif ($code=='ready') return ($this->metadata['got_data'] && $this->metadata['correct'] && $this->metadata['safe']);
		elseif (array_key_exists($code, $this->metadata)) return $this->metadata[$code];
		else return false;
	}
	
	public function combo()
	{
		if (is_null($this->storage)) return false;
		return $this->storage instanceof EntityStorage_combo;
	}
	
	public function link()
	{
		return ($this->behavior instanceof EntityBehavior_link);
	}
	
	public function analyzeData()
	{
		if ($this->metadata['checked']) return;
		if ($this->combo())
		{
			$this->prepare_storage();
			$this->storage->analyzeComboData();
		}
		$this->prepare_behavior();
		$this->behavior->analyzeData();	
	}
	
	public function getValue($code='value', $readied=true)
	{
		if (($readied)&&(!$this->metadata('ready'))) return null;
		if (array_key_exists($code, $this->data)) return $this->data[$code];
		return null;
	}
	
	// допустимые источники данных:
	// default - значения по умолчанию. не проверяются на валидность и безопасность, так как уже были проверены создателем модуля или средствами редактирования модуля.
	// DB - из базы данных. не проверяется на валидность и безопасность, так как уже были проверены при добавлении в БД.
	// update - изменение данных в процессе прогона программы. не сохраняется в метаданные, но видно по changed.
	// input - пользовательский ввод. проверяется на валидность и безопасность.
	public function setValue($value, $source, $rewrite=true)
	{
		if (is_null($source))
		{
			$this->metadata['got_data']=false;
			setSource($source);
		}
		
		if (!is_array($value)) $set=array('value'=>$value);
		else $set=$value;
		$changed=false;
		if (($source=='update')||($source=='input')&&(!$this->metadata['changed']))
		{
			foreach ($set as $key=>$val)
			{
				if ($this->data[$key]!==$val)
				{
					$changed=1;
					break;
				}
			}
			
			if (($rewrite)&&(!$changed))
			{
				$diff=array_diff(array_keys($this->data), array_keys($set));
				if (count($diff)>0) $changed=1;
			}
		}
		else $changed=$this->metadata['changed'];
		
		if (($rewrite)&&($changed)) $this->data=$set;
		elseif ((!$rewrite)&&($changed)) $this->data=array_merge($this->data, $set);
		
		$this->metadata['changed']=$changed;
		$this->setSource($source);
	}
	
	public function setSource($source)
	{
		if ($source=='update') return;
		$this->metadata['source']=$source;
		$this->metadata['checked']=false;
		$this->metadata['correct']=false;
		$this->metadata['safe']=false;
	}
	
	public function __call($name, $arguments)
	{
		$this->prepare_storage();
		if (method_exists($this->storage, $name))
		{
			$result=call_user_func_array( array ($this->storage, $name), $arguments);
			return $result;
		}
		$this->prepare_behavior();
		if (method_exists($this->behavior, $name))
		{
			$result=call_user_func_array( array ($this->behavior, $name), $arguments);
			return $result;		
		}
		// ERR!
	}
}

function debug($msg)
{
	echo $msg.'<br>';
}
?>