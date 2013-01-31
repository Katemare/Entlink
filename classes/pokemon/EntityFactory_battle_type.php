<?
class EntityFactory_battle_type extends EntityFactory_combo
{
	static $type='battle_type';
	static $behavior='battle_type';
	
	static $model=array(
		'name_rus'=>array(
			'table'=>'type_def',
			'type'=>'name',
			'lang'=>'rus'
			),
		'name_eng'=>array(
			'table'=>'type_def',
			'type'=>'name',
			'lang'=>'eng'
			),
		'color'=>array(
			'table'=>'type_def',
			'type'=>'color',
			),
		'special'=>array(
			'table'=>'type_def',
			'type'=>'effect_category',
			'qlink'=>1,
			'link_type'=>'B_is_effect_category_of_A'
			),
		'icon'=>array(
			'table'=>'type_def',
			'type'=>'icon',
			'qlink'=>1,
			'link_type'=>'B_is_icon_of_A'
			)
	);	
}
?>