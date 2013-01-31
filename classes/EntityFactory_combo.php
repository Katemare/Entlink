<?
class EntityFactory_combo extends EntityFactory
{
	static $model=array();
	static $storage='combo', $behavior='combo';
	
	public static function create_blank($uni=0, $type='')
	{
		$entity=parent::create_blank($uni, $type);
		static::set_model($entity);
		return $entity;
	}
	
	public static function set_model($entity)
	{
		$entity->metadata['model']=static::$model;
	}
}
?>