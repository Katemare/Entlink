<?
class EntityFactory_B_is_attack_of_pokemon_A extends EntityFactory_variant
{
	static $type='B_is_attack_of_pokemon_A';
	static $mod_model=array(
		'ord'=>array(
			'table'=>'entities',
			'field'=>'number_int',
			'type'=>'int'
		)
	)
	static $entity1_type='pokemon', $entity2_type='pokemon_attack';
	
	public static function mod_model()
	{
		static::$model=array_merge(static::$model, static::$mod_model);
	}
}
EntityFactory_B_is_battle_type_of_pokemon_A::mod_model();
?>