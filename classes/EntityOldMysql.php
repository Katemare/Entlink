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
		debug('query '.htmlspecialchars($query));
		return mysql_query($query);
	}
	
	public static function fetch($result)
	{
		return mysql_fetch_assoc($result);
	}
}

$db = mysql_connect ('localhost', 'root', '');
mysql_select_db ('entlink');
?>