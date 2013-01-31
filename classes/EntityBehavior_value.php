<?
abstract class EntityBehavior_value extends EntityBehavior
{
	static $def_formats=array(
		'work'=>array('behavior', 'show'),
		'value'=>array('behavior', 'value'),
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
	
	public function show($args, $context)
	{
		debug('!!!');	
		if ($context->display_values())
		{
			return $this->value($args, $context);
		}
		elseif ($context->display_input())
		{
			return $this->expandCode('input', $args, $context);
		}
	}
	
	public function value($args, $context)
	{
		if ($this->owner->metadata('ready'))
		{
			return $this->owner->getValue();
		}
		else return 'NO DATA';
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
	}
	
	abstract public function check_safe();
	
	abstract public function check_correct();	
	
}
?>