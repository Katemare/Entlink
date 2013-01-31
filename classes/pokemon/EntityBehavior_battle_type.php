<?
class EntityBehavior_battle_type extends EntityBehavior_work
{
	static $def_formats=array(
		'icon'=>'%>icon%',
		'name'=>'%>name_eng',
	);
	static $pre_styles=array('icon');
	
	public function __construct()
	{
		parent::__construct();
		$this->formats=array_merge($this->formats, self::$def_formats);
		$this->styles=array_merge(static::$pre_styles, $this->styles);
	}
	
	public function
}

?>