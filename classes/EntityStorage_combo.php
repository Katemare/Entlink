<?
class EntityStorage_combo extends EntityStorage
{
	public $members=array();
	
	public function analyzeModel($code)
	{
		if (!array_key_exists($code, $this->owner->metadata['model'])) $result='error'; // ERR
		else
		{
			$result=$this->owner->metadata['model'][$code];
			if ($result['qlink']) $result['method']='uni';
			elseif (array_key_exists('table', $result)) $result['method']='member';
			else $result['method']='uni';
		}
		return $result;
	}
	
	// получить сущность, связанную с владельцем отношением $link. Предполагается один ответ.
	public function req_link($link, $context=null, $args='')
	{
		// STUB: здесь как-то должны подключаться модули. например, "комменты" скорее всего не занесены в модель комбинации. Иначе какой в ней смысл?
		
		$data=$this->analyzeModel($link);		
		if ($data==='error') { } // ERR
		elseif (($data['method']=='member')||($data['qlink'])||(!$data['multiple']))
		{
			$entity=EntityFactory::create_blank(0, $data['type']);
			
			if ($data['method']=='member')
			{
				$this->members[$link]=$entity;			
				EntityRetriever::req($this->owner->uni, $data['table'], $entity );			
				// эта же функция сразу вызывает "данные получены!", если они действительно уже были получены.
				$entity->storage='combo_member';
				$entity->metadata['model']=$data;
				$entity->prepare_storage();
				$entity->storage->connection=$link;
				$entity->storage->god=$this->owner;
			}
			elseif ($data['qlink'])
			{
				$link_entity=EntityFactory::create_blank(0, 'link');		
				$link_entity->set_link($this->owner, $entity);
				$this->members[$link]=$link_entity;				
				$args['code']=$link;
				EntityRetriever::req($this->owner->uni, $data['table'], array('entity'=>$this->owner, 'method'=>'follow_qlink', 'ask'=>'storage', 'context'=>$context, 'args'=>$args) );
				// эта же функция сразу вызывает "данные получены!", если они действительно уже были получены.
			}
			elseif ($data['method']=='uni')
			{
				// STUB! скорее всего должно быть реализовано иначе.
				$this->members[$link]=$entity;			
				$entity->req_dataset($context, $args);
			}
		}
		elseif ( ($data['method']=='uni')&&(!$data['multiple']) ) // STUB!
		{
			
		}
		// else { } // ERR
	}
	
	public function follow_qlink($tables, $context, $args)
	{
		// ERR
		$link=$args['code'];
		$data=$this->analyzeModel($link);
		// if (!$data['qlink']) { } // ERR
		$table=$data['table'];
		$field=$data['field'];
		if ($field=='') $field=$link;
		$entity=$this->members[$link]->subentity();
		
		debug('follow: '.$table.'->'.$this->owner->uni.'->'.$field);
		
		$uni=EntityRetriever::$data[$table][$this->owner->uni][$field];
		// if ($uni<1) { } // ERR or OPTIONAL
		$entity->setUni($uni);
		$entity->req_dataset($context, $args);
	}
	
	public function get_linked($link, $context)
	{
		$data=$this->analyzeModel($link);
		if ($data==='error') { } // ERR
		elseif (($data['method']=='member')||($data['qlink'])) // не самостоятельная сущность. данные извлекаются из модели.
		{
			// ERR
			return $this->members[$link];
		}
		else // STUB!
		{
		}	
	}
	
	public function receive($tables, $context, $args)
	{

	}
	
	public function analyzeComboData()
	{
		static $good=array(
			'got_data'=>true,
			'correct'=>true,
			'source'=>null,
			'safe'=>true,
		);
		
		static $incomplete=array(
			'got_data'=>false,
			'correct'=>false,
			'correctable'=>true,
			'source'=>null,
			'safe'=>false,
			'safe potential'=>true,
			'errors'=>array()
		);
		
		$result=$good;
		foreach ($this->owner->metadata['model'] as $code=>$options)
		{
			if ($options['optional']) continue;
			if (!array_key_exists($code, $this->members))
			{
				$result=$incomplete;
				$result['errors'][]=array('storage', 'missing member', array('member'=>$code) );
				break;
			}
			if (!$this->members[$code]->metadata('got_data'))
			{
				$result=$incomplete;
				$result['errors'][]=array('storage', 'member w/o data', array('member'=>$code) );
				break;
			}
			$member=$this->members[$code];
			
			$this->analyzeMemberData($member, $result);
			if ($member->link())
			{
				// STUB!
				$linked=$member->get_subentity();
				$this->analyzeMemberData($member, $result);
			}
		}
		
		$this->owner->metadata=array_merge($this->owner->metadata, $result);
	}
	
	public function analyzeMemberData($member, &$result)
	{
		if (!$member->metadata('correct')) $result['correct']=false;
		if (!$member->metadata('correctable')) $result['correctable']=false;
		if (!$member->metadata('safe')) $result['safe']=false;
		if (!$member->metadata('safe potential')) $result['safe potential']=false;
		
		if ($result['source']=='mixed') return;
		$s=$member->metadata('source');
		if ( (is_null($result['source']))&&(!is_null($s)) ) $result['source']=$s;
		elseif ( (!is_null($result['source']))&&($s!==$result['source'])) $result['source']='mixed';	
	}
}
?>