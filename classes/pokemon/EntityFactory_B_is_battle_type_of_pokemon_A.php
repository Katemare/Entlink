<?
class EntityFactory_B_is_battle_type_of_pokemon_A extends EntityFactory_variant
{
	static $type='B_is_battle_type_of_pokemon_A';
	static $mod_model=array(
		'ord'=>array(
			'table'=>'entities',
			'field'=>'number_int',
			'type'=>'int'
		)
	)
	static $entity1_type='pokemon', $entity2_type='battle_type';
	
	public static function init()
	{
		parent::init();
		if (self::$init) return;
		static::$model=static::special_merge(static::$model, self::$mod_model);
		self::$init=1;
	}
}
?>