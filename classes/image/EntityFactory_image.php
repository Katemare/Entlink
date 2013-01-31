<?
class EntityFactory_image extends EntityFactory_combo
{
	static $type='image';
	static $storage='combo';	
	static $behavior='image';	
	
	static $model=array(
		'nam'=>array(
			'table'=>'pictures',
			'type'=>'string'
		),
		'path'=>array(
			'table'=>'pictures',
			'type'=>'string'
		),
		'thumb'=>array(
			'table'=>'pictures',
			'type'=>'string',
			'optional'=>1,
			'default'=>'none'
		)
	);
}
?>