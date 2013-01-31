<?
class EntityBehavior_image extends EntityBehavior_work
{
	static $def_formats=array(
		'full'=>'<img src="%src%">',
		'short'=>'<img src="%src% width=200>',
		'hyperlink'=>'<a href="%src%">%>nam%</a>',
		'src'=>array('behavior', 'src'),
		'input'=>'<input type=file name="%input_name%" size=50>',
		'input_name'=>'replace_me'
	);
	
	public function __construct()
	{
		parent::__construct();
		$this->formats=array_merge($this->formats, self::$def_formats);
	}
	
	public function src($args, $context)
	{
		$this->analyzeContext($context);
		if ($context->do_req())
		{
			debug('preparing src');
			$this->expandCode('>path', '', $context);
			$this->expandCode('>nam', '', $context);
			return 'SRC';
		}
		elseif ($this->owner->metadata('ready'))
		{
			debug('making src');
			$path=$this->owner->get_linked('path', $args); $path=$path->getValue();
			$nam=$this->owner->get_linked('nam', $args); $nam=$nam->getValue();
			// $thumb=$this->owner->get_linked('thumb', $args); $thumb=$thumb->getValue();
			
			if (substr($path, 0, 13)=='pictures/wiki') $src='http://wiki.pokeliga.com/'.substr($path, 13).'/'.$nam;
			else $pic='/'.$path.'/'./*safelink*/($nam); // STUB
			return $pic;
		}
	}
	
	public function work($args, $context)
	{
		$this->analyzeContext($context);
		$style=$this->analyzeStyle($args);
		if ( ($context->display_input()) && (in_array($style, array('full', 'short'))) )
		{
			$result=$this->expandFormat($this->formats['input'], $context);
		}
		else
		{
			$result=parent::work($args, $context);
		}
		return $result;
	}
}

?>