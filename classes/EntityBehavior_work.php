<?
class EntityBehavior_work extends EntityBehavior
{
	static $def_formats=array(	
		'work'=>array('behavior', 'work'),	
		'full'=>'<div class="work_title">%title%</div><div class="work_content">%details%</div>',
		'short'=>'SHORT STYLE',
		'hyperlink'=>'HYPERLINK STYLE',
		'input'=>'<form action="%form_action%" method=POST><div class="form_content">%form_content%</div><input type=submit value="%submit_value%"></form>',
		'form_action'=>array('behavior', 'form_action'),
		'form_content'=>array('behavior', 'form_content'),
		'submit_value'=>'Отправить'
	);
	static $def_styles=array('full', 'short', 'hyperlink');
	public $styles=array();
	
	public function __construct()
	{
		parent::__construct();
		$this->formats=array_merge($this->formats, self::$def_formats);
		$this->styles=static::$def_styles;
	}
	
/*	
	public function expandCode($code, $args='', $context=null)
	{
		$this->analyzeContext($context);
		if ($code==='work')
		{
			if ((int)$args['uni']>0)
			{
				$auni=(int)$args['uni'];
				if ($this->uni==0) $this->setUni($auni);
				elseif ($this->uni<>$auni) { echo 'Error!'; } // ERR
				elseif ($this->uni<>$auni)
				{
					EntityFactory::create_from_uni($auni);
				}
			}
		}
		
		return parent::expandCode($code, $args, $context);
	}
*/

	public function analyzeStyle(&$args)
	{
		if ( (array_key_exists('style', $args)) && (in_array($args['style'], $this->styles)) ) $style=$args['style'];
		else
		{
			$style=array_intersect($args, $this->styles);
			if (count($style)==0) $style=$this->styles[0];
			else $style=$style[0];
			$args['style']=$style;
			$args=array_diff($args, $this->styles);
		}
		return $style;
	}
	
	public function work($args, $context)
	{
		$this->analyzeContext($context);
		$style=$this->analyzeStyle($args);
		debug ('work style '.$style);
		
		if ( ($context->display_input()) && ($context->is_root($this->owner)) )
		{
			$result=$this->expandCode('input', $args, $context);
		}
		else
		{
			$result=$this->expandFormat($this->formats[$style], $context);		
		}
		
		return $result;
	}
	
	public function form_action($args, $context)
	{
		return 'action.php';
		// FIX
	}
	
	public function form_content($args, $context)
	{
		return $this->expandFormat($this->formats['full'], $context);
	}
}
?>