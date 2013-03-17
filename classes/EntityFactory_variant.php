<?

abstract class EntityFactory_variant extends EntityFactory_link
{
	static $type='list_item';
	static $behavior='list_item';
	static $entity1_type, $entity2_type;
	
	public static function init()
	{
		parent::init();
		if (self::$init) return;
		if (!is_null(static::$entity1_type)) static::$model['entity1']['type']=$entity1_type;
		if (!is_null(static::$entity2_type)) static::$model['entity2']['type']=$entity2_type;
		self::$init=1;	
	}
}

?>