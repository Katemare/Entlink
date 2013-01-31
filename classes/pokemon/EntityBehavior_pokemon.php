<?
class EntityBehavior_pokemon extends EntityBehavior_work
{
	static $def_formats=array(
		'title'=>'%name[lang=eng]%',
		'details'=>'<div class="pokemon_names">%name[lang=rus;explain]%<br>%names[nolang=eng,rus;explain]%</div><div class="pokemon_types">%>type1[official]%</div><div class="pokemon_desc">%description%</div>',
		'names'=>array('behavior', 'names'),
		'name'=>array('behavior', 'name'),
		'description'=>array('behavior', 'description')
	);
	
	public function __construct()
	{
		parent::__construct();
		$this->formats=array_merge($this->formats, self::$def_formats);
	}

	public function names($args, $context)
	{
		$this->analyzeContext($context);
		
		return 'THERE BE NAMES';
	}
	
	public function name($args, $context)
	{
		$this->analyzeContext($context);
		
		return 'THERE BE ONE '.$args['lang'].' NAME';
	}	
	
	public function description($args, $context)
	{
		$this->analyzeContext($context);
		
		return 'THERE BE DESCRIPTIONS';
	}
}
?>