<?

abstract class EntityFactory_link_list_item extends EntityFactory_link
{
	static $type='link_list_item';
	static $behavior='link_list_item';
	
	static $model=array();
	
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