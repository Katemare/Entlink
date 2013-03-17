<?
class EntityBehavior_link extends EntityBehavior
{
	static $def_formats=array(
		'work'=>array('behavior', 'show'),
		'input'=>'<input type="%input_type%" size="%input_size%" name=%input_name% value="%value%">',
		'input_type'=>'text',
		'input_size'=>50,
		'input_name'=>'replace_me'
	);
	public $formats=array();
	
	public function __construct()
	{
		parent::__construct();
		$this->formats=array_merge($this->formats, self::$def_formats);
	}
	
	public function get_topentity()
	{
		return $this->owner->data['entity1'];
	}	
	public function get_subentity()
	{
		return $this->owner->data['entity2'];
	}
	
	public function show($args, $context)
	{
		debug('???');	
		if ($context->display_values())
		{
			// STUB!
			$entity=$this->get_subentity();
			$entity->display($context, $args);
		}
		elseif ($context->display_input())
		{
			return $this->expandCode('input', $args, $context);
		}
	}
	
	public function set_link($entity1, $entity2, $source)
	{
		if ($entity1 instanceof Entity) $uni1=$entity1->uni;
		elseif (is_numeric($entity1)) $uni1=$entity1;
		else $uni1=null;
		
		if ($entity2 instanceof Entity) $uni2=$entity2->uni;
		elseif (is_numeric($entity2)) $uni2=$entity2;
		else $uni2=null;
		
		$set=array('entity1'=>$uni1, 'entity2'=>$uni2);
		$this->owner->setData($set, $source);
	}
	
	public function analyzeData()
	{
		$realcheck=parent::analyzeData();
		if (!$realcheck)
		{
			$this->check_safe();
			$this->check_correct();
			$this->owner->metadata['checked']=1;
		}
		
		//STUB!
		$entity=$this->get_subentity();
		$entity->analyzeData();
	}
	
	public function check_safe()
	{
		// FIX!
	}
	
	public function check_correct()
	{
		// FIX!
	}
	
}
?>