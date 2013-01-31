<?
class EntityStorage_combo_member extends EntityStorage
{
	public $connection=null, $god=null;
	
	public function req_link($link, $context=null, $args='')
	{
	}
	
	public function get_linked($link, $context)
	{
	}
	
	public function receive($tables, $context, $args)
	{
		if (in_array($this->owner->metadata['model']['table'], $tables))
		{	
			$this->owner->setValue(EntityRetriever::$data[$this->owner->metadata['model']['table']][$this->god->uni][$this->value_field()], 'DB');
		}
		else { } // ERR
	}
	
	public function value_field()
	{
		if (array_key_exists('field', $this->owner->metadata['model'])) return $this->owner->metadata['model']['field'];
		else return $this->connection;
	}
}
?>