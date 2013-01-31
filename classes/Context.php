<?
class Context
{
	public $purpose='';
	// dryrun, preprocess, display, dbmod, new, dbnew, edit
	public $media='html';
	public $root=null;
	
	public function __construct($purpose)
	{
		$this->purpose=$purpose;
	}
	
	public function do_req()
	{
		static $do_req=array('preprocess', 'dbmod');
		
		return in_array($this->purpose, $do_req);
	}
	
	public function create_blanks()
	{
		static $create_blanks=array('new', 'dbnew');
		
		return in_array($this->purpose, $create_blanks);	
	}
	
	public function display_values()
	{
		static $display_values=array('display');
		
		return in_array($this->purpose, $display_values);
	}
	
	public function display_input()
	{
		static $display_values=array('edit', 'new');
		
		return in_array($this->purpose, $display_values);	
	}
	
	public function media($m)
	{
		return $m==$this->media;
	}
	
	public function is_root($entity)
	{
		return $entity===$this->root;
	}
}

class Context_WorkPage extends Context
{
	public $root_uni=null;
	
	public function __construct($purpose, $root)
	{
		parent::__construct($purpose);
		$this->root_uni=$root;
	}
}
?>