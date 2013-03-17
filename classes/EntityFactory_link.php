<?
class EntityFactory_link extends EntityFactory_combo
{
	static $type='link';
	static $behavior='link';
	static $storage='compact';
	
	static $model=array(
		'entity1'=>array(
			'table'=>'entities'
		),
		'entity2'=>array(
			'table'=>'entities'
		)
	);
}
?>