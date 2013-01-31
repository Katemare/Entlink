<?
abstract class EntityFactory
{
	static $type='abstract';
	static $entities_list=array(); // список всех сущностей, чтобы всегда можно было найти нужную сущность по идентификатору.
	static $entities_by_uni=array();
	static $lastid=0; // номер последней созданной сущности. счёт начинается с 1.
	static $behavior='abstract', $storage='abstract';
	
	public static function create_blank($uni=0, $type='' /* false предотвращает построение объекта. */)
	{
		$entity=new Entity();
	
		// все дочерние классы используют этот же счётчик, чтобы идентификатор всегда был уникальным.
		// это идентификатор используется для общения сущностей между собой во время операций и не имеет отношения к идентификатору, хранящемуся в базе данных.
		EntityFactory::$lastid++;
		$entity->id=EntityFactory::$lastid;
		EntityFactory::$entities_list[$entity->id]=$entity;
		
		if ($uni>0)
		{
			$entity->setUni($uni);
		}
		
		if (($type==='')&&($uni>0))
		{
			static::req($uni);
		}
		elseif ( (is_string($type))&&($type<>'') )
		{
			$entity->build($type);
		}
		
		return $entity;
	}
	
	public static function req($uni)
	{
		EntityRetriever::req($uni);
	}
	
	public static function build($entity)
	{
		$entity->type=static::$type;
		$entity->metadata['typed']=1;
	}
	
	public static function build_by_Retriever($entity, $type='')
	{
		if ($type=='')
		{
			if ($entity->uni<1) return false;
			if (!array_key_exists($entity->uni, EntityRetriever::$data['entities'])) return false;
			$type=EntityRetriever::$data['entities'][$entity->uni]['entity_type'];
		}
		$class='EntityFactory_'.$type;
		$class::build($entity);
		return true;
	}
	
	public static function prepare_behavior($entity)
	{
		if (is_string($entity->behavior)) $behavior=$entity->behavior;
		else $behavior=static::$behavior;
		$class='EntityBehavior_'.$behavior;
		$behavior=new $class();
		$entity->behavior=$behavior;
		$behavior->owner=$entity;
	}
	
	public static function prepare_storage($entity)
	{
		if (is_string($entity->storage)) $storage=$entity->storage;
		else $storage=static::$storage;	
		$class='EntityStorage_'.$storage;
		$storage=new $class();
		$entity->storage=$storage;
		$storage->owner=$entity;
	}
}

?>