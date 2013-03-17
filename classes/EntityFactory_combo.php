<?
class EntityFactory_combo extends EntityFactory
{
	static $model=array();
	static $mod_model=array();	
	static $init=0;
	static $storage='combo';
	
	public static function init()
	{
		if (self::$init) return;
/*		
		if (count(self::$mod_model)>0)
		{
			static::$model=static::special_merge(static::$model, self::$mod_model);
		}
*/
		self::$init=1;
	}
	
	public static function special_merge($arr1, $arr2)
	{
		$result=$arr1;
		foreach ($arr2 as $key=>$value)
		{
			if (!array_key_exists($key, $result)) $result[$key]=$value;
			elseif ( (is_array($result[$key])) && (is_array($value)) ) $result[$key]=static::special_merge($result, $value);
			else $result[$key]=$value;
		}
		return $result;
	}
	
	public static function create_blank($uni=0, $type='')
	{
		$entity=parent::create_blank($uni, $type);
		static::init();
		static::set_model($entity);
		return $entity;
	}
	
	public static function set_model($entity)
	{
		$entity->metadata['model']=static::$model;
	}
}
?>