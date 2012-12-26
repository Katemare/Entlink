<?
class EntityOldMysql
{
	public static function get_insert_id()
	{
		// STUB
		// return mysql_insert_id();
		
		return 777;
	}

	public static function query($query)
	{
		return mysql_query($query);
	}
	
	public static function fetch($result)
	{
		return mysql_fetch_assoc($result);
	}
}
?>