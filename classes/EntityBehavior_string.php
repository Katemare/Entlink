<?
class EntityBehavior_string extends EntityBehavior_value
{

	public function check_safe()
	{
		$value=$this->owner->getValue();
		
		$value=preg_replace(             // вырезание всех опасных служебных символов
			'/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x84]/',
			'',
			$value
		);
		
		$this->owner->setValue($value, 'update');
	}
	
	public function check_correct()
	{
		$value=$this->owner->getValue();
		
		str_replace(           // CRLF -> LF, замена табов пробелами
			array("\r\n", "\r"),
			"\n",
			$value
		);
		
		$this->owner->setValue($value, 'update');
	}
	
	public function value($args, $context)
	{
		debug('value');
		$result=parent::value($args, $context);
		if ($context->media('html'))
		{
		  $result=str_replace(           // CRLF -> LF, замена табов пробелами
			array("\t", '\'',    '\\',    "\n"),
			array('  ', '&#39;', '&#92;', "<br>\n"),
				preg_replace_callback(      // разэкранировка десятичных HTML-сущностей
				  '/&amp;#(\d{2,5});/',
				  array($this, 'normalString__automata'),
					htmlspecialchars($str)
				)
			);
		}
		return $result;
	}
	
	public function normalString__automata($matches)
	{
	  $code = (int)($matches[1]);
	  return (($code < 32)
		|| (($code >= 127  ) && ($code <= 132  ))
		|| (($code >= 8234 ) && ($code <= 8238 ))
		|| (($code >= 55296) && ($code <= 57343))
		|| (($code >= 64976) && ($code <= 65007))
		|| ( $code >= 65534)
	  ) ? $matches[0] : "&#$code;";
	}
}
?>