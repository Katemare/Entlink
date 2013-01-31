<?
class EntityFactory_link_B_is_battle_type_of_pokemon_A extends EntityFactory_link_list_item
{
	static $type='link_B_is_battle_type_of_pokemon_A';
	
	static $model=array(
		'entity1'=>array(
			'type'=>'pokemon'
		),
		'entity2'=>array(
			'type'=>'battle_type'
		)
	);
}
?>