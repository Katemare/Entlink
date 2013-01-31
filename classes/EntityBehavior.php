<?
abstract class EntityBehavior
{
	public $owner=null;
	
	static $def_formats=array(
		'work'=>'%error[not behaved]%',
		'error'=>array('self', 'error')
	);
	public $formats=array();
	
	public function __construct()
	{
		$this->formats=self::$def_formats;
	}
	
	public function display($context, $args='')
	{
		if (is_array($args)) $args=$this->mergeArgs($args);
		return $this->expandFormat('%work'.(($args<>'')?('['.$args.']'):('')).'%', $context);
	}
	
	public function analyzeContext(&$context)
	{
		if (is_null($context)) $context=$this->context;
		elseif ($context!==$this->context) $this->context=$context;	
	}
	
	public function req_dataset($context, $args='')
	{
		$this->display($context, $args);
	}
	
	public function expandFormat($format, $context=null)
	{
		$this->analyzeContext($context);
		debug('xFormat: '.htmlspecialchars($format));
		$result=preg_replace_callback(
			'/%(?<code>[\<\>]?[a-z_0-9]+)(\[(?<args>[^\]]+)\])?%/i',
			array($this, 'expandCode_callback'),
			$format
			);
		return $result;		
	}
	
	public function expandCode_callback($m)
	{
		debug('xCode cb'.$this->owner->id.': '.$m[0].' ('.$m['code'].')');
		$args=$this->parseArgs($m['args']);
		$code=$m['code'];
		if (!array_key_exists('default', $args)) $args['default']=$m[0];
		if (!array_key_exists('original', $args)) $args['original']=$m['args'];
		return $this->expandCode($code, $args);
	}	
	
	public function expandCode($code, $args='', $context=null)
	{
		$this->analyzeContext($context);
		debug('xCode '.$this->owner->id.': '.$code);
		
		$result='';
		if (in_array($code[0], array('>', '<')))
		{
			debug ('xCode linked');
			$link=substr($code, 1);
			if ($context->do_req())
			{
				debug('req_link: '.$link);
				$this->owner->req_link($link, $context, $args);
				$result='REQ_LINK: '.$link; //$args['default'];
			}
			else //if ($context->display_values())
			{
				$linked=$this->owner->get_linked($link, $args);
				if ($linked instanceof Entity) $result=$linked->display($context, $args);
				else $result=$this->expandFormat('%error[no_linked;ask='.$link.']%');
			}
		}
		elseif (array_key_exists($code, $this->formats))
		{
			if (is_string($this->formats[$code]))
			{
				$result=$this->expandFormat($this->formats[$code]);
			}
			elseif (is_array($this->formats[$code]))
			{
				$ask=$this->formats[$code][0];
				if ($ask=='self') $obj=$this->owner;
				elseif ($ask=='behavior') $obj=$this;
				elseif (!is_object($this->owner->$ask)) $result= $this->expandFormat('%error[no_subobject;ask='.$ask.']%');
				else $obj=$this->owner->$ask;
				
				if ($result=='')
				{
					$method=$this->formats[$code][1];
					if ($method=='expandCode') $result= $this->expandFormat($obj->expandCode($code, $args, $context));
					else $result=$this->expandFormat($obj->$method($args, $context));
				}
			}
			
/*			elseif ($this->formats[$code] instanceof Entity)
			{
				$entity=$formats[$code];
				return $entity->expandCode($code, $args, $context);
			}
*/			
		}
		else
		{
			$result= $args['default'];
		}
		// else ERR
		
		return $result;
	}
	
	public function parseArgs($s='')
	{
		if ($s=='') return array();
		$list=explode(';', $s);
		$result=array();
		foreach ($list as $arg)
		{
			if ($arg[0]==='#') $result['uni']=substr($arg, 1);
			if (preg_match('/^([a-z\_0-9]+)=(.+)$/i', $arg, $m))
			{
				if (strpos($m[2], ',')!==false) $m[2]=explode(',', $m[2]);
				$result[$m[1]]=$m[2];
			}
			else $result[]=$arg;
		}
		$result['original']=$s;
		return $result;
	}
	
	public function mergeArgs(&$source='')
	{
		if (($source=='')||(count($source)==0)) return '';
		if (array_key_exists('original', $source)) return $source['original'];
		$s=$source;
		unset($s['original'], $s['default']);
		$result=array();
		foreach ($s as $key=>$value)
		{
			if (is_numeric($key)) $result[]=$value;
			elseif (is_array($value)) $result[]=$key.'='.implode(',', $value);
			else $result[]=$key.'='.$value;
		}
		$result=implode(';', $result);
		$source['original']=$result;
		return $result;
	}
	
	public function analyzeData()
	{
		$good=array(
			'checked'=>1,
			'safe'=>1,
			'correct'=>1
		);
		$bad=array(
			'checked'=>1,
			'safe'=>false,
			'safe potential'=>false,
			'correct'=>false,
			'correctable'=>false,
			'errors'=>array()
		);
		if ($this->owner->metadata['checked']) return;
		
		$result=array();
		$realcheck=true;
		
		if ( (is_null($this->owner->metadata['source'])) || (!$this->owner->metadata['got_data']) )
		{
			$result=$bad;
			$result['errors'][]='no data';
		}
		elseif (in_array($this->owner->metadata['source'], array('DB', 'default')))
		{
			$result=$good;
		}
		else
		{
			//$result=$good;
			$realcheck=false; // replace me!
		}
		
		if (count($result)>0) $this->owner->metadata=array_merge($this->owner->metadata, $result);
		return $realcheck;
	}
	
	public function error($args, $context)
	{
		return $args[0];
	}
}
?>