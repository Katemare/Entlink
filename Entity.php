<?

#########################
### Entity base class ###
#########################
// Это базовый класс, от которого образуются все классы данных в нашем движке.

abstract class Entity
{
	public $lang='rus'; // язык, на котором будут выводиться сообщения.
	public $prefix=''; // для ввода, если в форме есть несколько сущностей данного типа.
	static $lastid=0; // номер последней созданной сущности. счёт начинается с 1.
	public $id=0; // идентификатор данной сущности внутри прогона программы.
	public $uni=0; // идентификатор сущности, имеющей собственную отдельную запись в БД. есть не у всех.
	
	public $data=array(); // данные сущности.
	public $rules=array(); // сопутствующая информация о том, как отображать и хранить сущность.
	public $toretrieve=array(); // область запросов к базе данных (если не указана, то "всё").
	public $valid='nodata'; // статус содержимого, имеет одно из следующих значений:
	// nodata - нет данных.
	// unknown - есть данные, не проверялись.
	// invalid - есть данные, были проверены и признаны недопустимыми
	// valid - есть данные, были проверены и признаны допустимыми
	
	public function __construct($args='')
	{
		// все дочерние классы используют этот же счётчик, чтобы идентификатор всегда был уникальным.
		// это идентификатор используется для общения сущностей между собой во время операций и не имеет отношения к идентификатору, хранящемуся в базе данных.
		Entity::$lastid++;
		$this->id=Entity::$lastid;
		
		// в массиве $args содержатся аргументы и опции для создания сущности. их точный состав неизвестен, но создаваться всё должно по одному принципу, так что используется массив.
		if (is_array($args))
		{
			$prefix=(string)($args['prefix']);
			if (array_key_exists('storage', $args)) $this->rules['storage']=$args['storage'];
		}
		
		$this->prefix=$prefix;
	}
	
	public function entity_type()
	{
		return substr(get_class($this), 7);
	}
	
	public function req() { } // постановка запроса на получение из базы данных.
	
	public function retrieve() // получение из базы данных. следует вызывать как можно позже, когда данные нужны немедленно.
	{
		Retriever::retrieve($this->toretrieve);
	}
	
	// пользовательский ввод через форму, ссылку или заранее извлечённые данные.
	// $input -  массив с заранее извлечёнными данными. если указан, то используется вместо $_GET и $_POST
	// $ready - если истина, то данные не проверяются и не корректируются, считаясь сразу допустимыми.	
	public function input($input=false, $ready=false)
	{
		$this->do_input($input); // делает непосредственный ввод данных в $data.
		$this->after_input($ready); // обезопасивает данные, проверяет их допустимость и так далее.
	}
	
	public abstract function do_input($input);
	
	public function after_input($ready=false)
	{
		if ($ready) $this->valid='valid'; // этот параметр инструктирует функцию пропустить проверки. ТОЛЬКО для случаев, когда данные уже точно проверены! к примеру, получены из базы данных.
		else
		{
			if (!is_null($this->data['value'])) // если есть данные...
			{
				$this->invalidate(); // установить, что данные есть, но их допустимость неизвестна.
				$this->safe(); // обезопасить!
				$this->validate(1); // проверить допустимость (насильно).
			}
			else $this->clear(); // если данных нет, то запомнить, что их нет.
		}
	}
	
	public abstract function safe(); // исключение небезопасных значений при пользовательском вводе. корректирует данные.
	
	public function validate($force=0) // проверка допустимости значений.
	// эта функция возвращает массив из двух элементов: valid - статус допустимости, и check - детали проверки.
	// значения valid такие же, как у публичной переменной valid.
	// значения check:
	// nop - проверки не было (потому что данные отсутствуют)
	// previous - возвращён результат предыдущей проверки.
	// allgood - возвращён результат проверки по умолчанию, которая любые данные признаёт допустимыми. следует понимать так: "замените меня в дочерних классах!
	{
		if ($this->valid=='nodata') return array ('valid'=>'nodata', 'check'=>'nop');
		if (($this->valid<>'unknown')&&($force==0)) return array ('valid'=>$this->valid, 'check'=>'previous');
		$this->valid='valid';
		return array('valid'=>$this->valid, 'check'=>'allgood');
	}
	
	public function invalidate() // сброс допустимости данных. 
	{
		$this->valid='unknown';
	}
	
	public function clear() // сброс данных (не стирая их, впрочем.)
	{
		$this->valid='nodata';
	}
	
	public abstract function display($style='raw'); // показ данных.
	
	public abstract function store($rules=''); // сохранение данных. STUB - пока что только получение запросов.
}

#############################
### Combined Entity class ###
#############################
// этот класс управляет набором сущностей. практически всё, кроме базовых сущностей-данных, относится к этмоу классу.

abstract class Entity_combo extends Entity
{
	public $model=array(); // модель, по которой строятся внутренние сущности. STUB
	// модель также должна давать сущности-комбо сведения о том, какие подсущности соответствуют полям в отдельных таблицах, а какие - полноценные прогрессивные сущности с уникальными идентификаторами. подобная двойственность нужна для гармоничного перехода сайта с одного метода на другой, а также при интеграции с движками, использующими первый метод (MediaWiki, BattleEngine...).
	
	public $entities=array(); // массив со всеми внутренними сущностями, по номеру
	public $byrole=array();  // массив со всеми внутренними сущностями, по роли (внутри роли может быть нумерация)
	public $rolebyid=array(); // массив ролей по идентификатору сущности. нужен для удаления сущности из списка по ролям, чтобы не перебирать.
	public $format=array(); // массив форматов отображения, типа 'Привет, %name%!'. должен содержать минимум значение с ключом raw.
	public $style=''; // используется для хранения стиля, обрабатываемого сейчас, в отсутствие пока что closure'ов.
	
	public function build($role='') // строит внутренние сущности согласно модели.
	// эта функция не автоматически вызывается после конструкции класса потому, чтобы код мог уточнить модель до этого.
	{
		if ($role=='') // вызов для построения всей модели.
		{
			foreach ($this->model as $role=>$options)
			{
				if ($options['optional']) continue; // объекты, чьё присутствие не обязательно, по умолчанию не создаются.
				$entity=$this->build($role); // создаём объект конкретной роли.
				$this->add($entity, $role); // и добавляем его.
			}
		}
		else // вызов для построения объекта конкретной роли.
		{
			$options=$this->model[$role]; // получаем параметры данной роли.
			$class='Entity_'.$options['class']; // это название класса объекта, который нужно создать.
			
			// это аргументы, которые передаются конструктору.
			if (is_array($this->model[$role]['new_args'])) $args=$this->model[$role]['new_args'];
			else $args=array();
			if (array_key_exists('storage', $this->model[$role])) $args['storage']=$this->model[$role]['storage'];
			
			// префикс должен совпадать с родительским объектом.
			$args['prefix']=$this->prefix;
			
			// создаём, возвращаем.
			$entity=new $class($args);
			return $entity;
		}
	}
	
	// когда требуется ввод, то команда передаётся всем внутренним объектам по очереди.
	// данные могут приходить сверху, особенно если комбинированный объект представляет единую запись в базе данных.
	public function do_input($input=false)
	{
		foreach ($this->entities as $entity)
		{
			$entity->do_input($input);
		}
	}
	
	public function listFormat() // DEBUG! функция для дебага: конструирует простой формат вывода.
	{
		$format=array();
		foreach ($this->model as $role=>$options)
		{
			$format[]='%'.$role.'~%';
		}
		$format=implode('<br>', $format);
		$this->format=$format;
	}
	
	public function safe() { } // эта функция ничего не делает, потому что ввод проверен отдельными подсущностями.
	
	public function validate($force=0) // проверка всех значений. не учитывает отношений значений. это дело дочерних классов.
	{
		$result=parent::validate($force);
		if ($result['check']<>'allgood') return $result; // типы проверок "без проверки" и "по предыдущей проверке" модифицировать не надо.
		
		$result=array('check'=>'performed'); $valid='valid'; // значения по умолчанию.
		
		// проверяется каждая внутренняя сущность. если хотя бы одна не допустимая, то вся комбинация недопустима.
		foreach ($this->byrole as $role=>$list)
		{
			foreach ($list as $index=>$entity)
			{
				$res=$entity->validate($force);
				if ($res['valid']<>'valid') $valid='invalid';
				$result['byentity'][$role][$index]=$res; // под этим ключом результаты проверок отдельных сущностей.
			}
		}
		$result['valid']=$valid;
		$this->valid=$valid;
		return $result;
	}
	
	// проверяет, есть ли сущность в данной роли. если индекс указан, то под конкретным индексом. если не указан, то под нулевым индексом.
	public function exists($role, $index=0)
	{
		return is_object($this->byrole[$role][$index]);
	}
	
	// добавляет сущность в список внутренних сущностей.
	public function add($add, $role='norole')
	{
		if (is_array($add)) // если нужно добавить много сущностей...
		{
			foreach ($add as $key=>$entity)
			{
				if (is_numeric($key)) $this->add($entity, $role); // массив с ключами-числами берёт роль из параметра $role.
				else $this->add($entity, $key); // массив с ключами-строками берёт ключ в качестве роли. в том числе в качестве $entity может выступать дочерний массив.
			}
		}
		elseif ($add instanceof Entity)
		{
			$this->entities[$add->id]=$add;
			$this->byrole[$role][]=$add; // индексы идут подряд, потому что если какой-то из них удаляется, то массив специально сжимается.
			$this->rolebyid[$add->id]=$role;
		}
		else { echo 'Ошибка!'; exit; }
	}
	
	// удаляет сущность из списка внутренних сущностей.
	// $remove - сущность, которую нужно удалить (или массив)
	// $recursive - параметр, добавляемый только при рекурсивном вызове, чтобы повременить с сортировкой списков.
	// $tosort - тоже параметр рекурсивного вызова, содержащий список ролей, которые нужно будет отсортировать.
	public function remove($remove, $recursive=0, &$tosort=array() )
	{
		if (is_array($remove))
		{
			$tosort=array();
			foreach ($remove as $entity)
			{
				$this->remove($entity, 1, $tosort);
			}
			$tosort=array_unique($tosort); // убираем дубли из списка ролей, которые надо отсортировать.
			foreach ($tosort as $ts)
			{
				$this->byrole[$ts]=array_values($this->byrole[$ts]); // отсортировываем роли, чтобы исключить индексы вроде "0, 1, 2, 5".
			}
		}
		else
		{
			$id=$remove->id;
			unset($this->entities[$id]);
			$role=$this->rolebyid[$id];
			$rolekey=array_search($entity, $this->byrole[$role], 1);
			unset($this->byrole[$role][$rolekey]);
			unset($this->rolebyid[$id]);
			
			if ($recursive) $tosort[]=$role;
			else $this->byrole[$role]=array_values($this->byrole[$role]);
		}
	}
	
	// демонстрирует данные.
	public function display($style='raw')
	{
		// управление передаётся подгружаемому статичному методу, чтобы спрятать код, нужный лишь эпизодически.	
		return EntityDisplay::display_combo($this, $style);
	}
	
	// обычно вызывается командой preg_replace_callback из предыдущей функции, а также иногда может вызываться отдельно.
	public function display_subentity($role, $index=null, $style='')
	{
		// управление передаётся подгружаемому статичному методу, чтобы спрятать код, нужный лишь эпизодически.
		return EntityDisplay::display_subentity($this, $role, $index, $style);
	}
	
	//STUB - только возвращает запрос и не проверяет допустимость значений.
	public function store($rules='')
	{
		return EntityStorage::store_combo($this, $rules);
	}
}

###########################
### Value-type entities ###
###########################
// это сущности самого низкого уровня, аналог переменных базовых типов. если что-то вводится одним тэгом <input> или <select>, то данные идут в сущность, образованную от Entity_value.

abstract class Entity_value extends Entity
{	
	public function __construct($args='')
	{
		parent::__construct($args);
		$this->rules['html_name']=(string)($args['html_name']); // нужно как в случае ввода из формы, так и чтобы разобрать массив данных $input, скинутый свыше.
		$this->rules['title']=$this->rules['html_name']; // STUB. в будущем будет браться из словаря.
	}
	
	// наконец-то что-то показываем!
	public function display($style='raw')
	{
		// управление передаётся подгружаемому статичному методу, чтобы спрятать код, нужный лишь эпизодически.	
		return EntityDisplay::display_value($this, $style);
	}
	
	// этой функцией (и дочерними) осуществляется весь пользовательский ввод и львиная доля ввода из БД.
	public function do_input($input=false)
	{
		$val=null;	
		if ($input===false) // если предлагается брать значения из вводе пользователя...
		{
			$name=$this->input_name(); // получаем, как должна называться переменная. это учитывает префикс.
			global $_GET, $_POST;
			if (array_key_exists($name, $_POST)) $val=$_POST[$name];
			elseif (array_key_exists($name, $_GET)) $val=$_GET[$name];
		}
		else // если предлагается разобрать массив данных, к примеру, полученный из базы данных...
		{
			$name=$this->rules['html_name']; // префикс не используется, потому что нет необходимости отличать ввод однородных объектов в единой форме;
			if (array_key_exists($name, $input)) $val=$input[$name];
		}
		
		$this->data['value']=$val; // даже если значение не было найдено, нужно записать это в данные.
		
	}
	
	public function input_name() // конструирует имя поля ввода. в принципе, можно было бы использовать кэш, но операция не затратная.
	{
		return $this->prefix.'_'.$this->html_name;
	}
	
	//STUB - только возвращает запрос и не проверяет допустимость значений.
	public function store($rules='')
	{
		return EntityStorage::store_value($this, $rules);
	}	
}

// текстовые данные.
class Entity_text extends Entity_value
{
	public function safe()
	{
		$this->data['value']=$this->normalString($this->data['value']);
	}
	
	// это специальная функция для обезопасивания текстовых данных. она делает следующее:
	// 0. устраняет экранирование ввода, добавляемое php.
	// 1. экранирует символы, которые могут навредить в запросе к базе данных. в том числе защищает от вторичной инъекции.
	// 2. убирает технические символы, которые не могут пригодится в обычном текстовом вводе, или заменяет их пробелами.
	// 3. устраняет различия стандартов Винды и других систем.
	// 4. кодирует символы, которые могут использоваться для html-инъекций.
	public function normalString($str)
	{
	  return str_replace(           // CRLF -> LF, замена табов пробелами
		array("\r\n", "\r", "\t", '\'', '\\'),
		array("\n", "\n", '  ', '&#39;', '&#92;'),
		preg_replace_callback(      // разэкранировка десятичных HTML-сущностей
		  '/&amp;#(\d{2,5});/',
		  array($this, 'normalString__automata'),
		  preg_replace(             // вырезание всех опасных служебных символов
			'/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x84]/',
			'',
			htmlspecialchars(stripslashes($str))
		  )
		)
	  );
	}
	
	public function normalString__automata($matches)
	{
	  $code = intval($matches[1]);
	  return (($code < 32)
		|| (($code >= 127  ) && ($code <= 132  ))
		|| (($code >= 8234 ) && ($code <= 8238 ))
		|| (($code >= 55296) && ($code <= 57343))
		|| (($code >= 64976) && ($code <= 65007))
		|| ( $code >= 65534)
	  ) ? $matches[0] : "&#$code;";
	}	
}

// целое число.
class Entity_int extends Entity_value
{
	public function safe()
	{
		$this->data['value']=(int)$this->data['value'];
	}
}

// натуральное число - то есть целое положительное.
class Entity_natural extends Entity_value
{
	public function safe()
	{
		$this->data['value']=(int)$this->data['value'];
		if ($this->data['value']<1) $this->data['value']=1;
	}
}

// число с плавающей точкой, но всё равно не отрицательное (почему-то)
class Entity_real extends Entity_value
{
	public function safe()
	{
		$this->data['value']=(float)$this->data['value'];
		if ($this->data['value']<0) $this->data['value']=0;
	}
}

############################################
### Комбинированные сущности на практике ###
############################################

// класс для хранения разных языковых версий одного слова, названия или текста. например, имена покемонов на разных языках.
class Entity_translation extends Entity_combo
{
	public $code='name';
	// код слова, для которого хранятся переводы. используется в следующих случаях:
	// названия полей ввода - name_rus, name_eng...
	// названия полей в базе данных для получения и записи. аналогично.
	// получение перевода сущностью-словарём. "комментарии" по-английски? "comments"!
	
	// модель должна перечислять все языки, хотя большая часть должна быть необязательной.
	// необязательные языки не создаются при построении объекта.
	// роли - это названия языков.
	public $model=array(
		'rus'=>array(
			'class'=>'text',
			'storage'=>array('value_table'=>'pokemon_main', 'by_html_name'=>1)
		),
		'eng'=>array(
			'class'=>'text',
			'storage'=>array('value_table'=>'pokemon_main', 'by_html_name'=>1)
		),
		'jap'=>array(
			'class'=>'text',
			'storage'=>array('value_table'=>'pokemon_main', 'by_html_name'=>1)
		),
		'jap_kana'=>array(
			'optional'=>1,
			'class'=>'text',
			'storage'=>array('value_table'=>'pokemon_main', 'by_html_name'=>1)
		),
		'ukr'=>array(
			'optional'=>1,		
			'class'=>'text',
			'storage'=>array('method'=>'uni')
		)
	);
	public $rules=array(
		'storage'=>array(
			'method'=>'uni',
			'uni_combo'=>1,
			'uni_table'=>'pokemon_main'
		)
	);
	
	// эта функция получает ввод от всех возможных языков. 
	public function do_input($input=false)
	{
		// проверяем все языки...
		foreach ($this->model as $role=>$options)
		{
			$new=true; // эта переменная подскажет нам, был ли создан новый объект-строка или взят существующий.
			if ($this->exists($role)) // если объект уже есть, берём его.
			{
				$entity=$this->byrole[$role][0];
				$new=false;
			}
			else $entity=$this->build($role); // если нет, создаём новый.
			
			$entity->input($input, $ready); // объект вводит значение.
			if (($entity->valid<>'nodata')&&($new)) $this->add($entity, $role); // если объект новый и он получил данные, то он добавляется во внутренние сущности.
			elseif ( ($this->valid=='nodata')&&(!$new)&&($this->model[$role]['optional']) ) $this->remove($entity); // если данных нет, объект старый, но необязательный - то он он удаляется из списка.
		}
	}
	
	public function build($role='')
	{
		// все внутренние сущности должны иметь имя поля ввода, состоящее из сода слова и кода языка.
		foreach ($this->model as $r=>&$options)
		{
			$options['new_args']['html_name']=$this->code.'_'.$r;
		}
		
		if ($role=='') return parent::build();
		$entity=parent::build($role);
		return $entity;
	}
	
	// эта функция для запросов от словаря. она возвращает вариант слова на нужном языке.
	public function translate($lang)
	{
		// если значение в этом языке есть, возвращаем его.
		if ($this->exists($lang)) return $this->byrole[$lang][0]->display('raw');
		
		// если нет, возвращаем первое попавшееся. считается, что языки расставлены в порядке приоритета. STUB.
		foreach ($this->byrole as $role=>$list)
		{
			if ($role==$lang) continue;
			$entity=$list[0];
			if ($entity->valid=='valid') return $entity->display('raw');
		}
		
		// если нет ничего, возвращаем код слова. STUB.
		return $this->code;
	}
}

// эта объект, который должен содержать все словарные слова, нужные в данный момент.
// в идеале он должен работать так: в период подготовки содержимого страницы разные сущности обращаются к нему и говорят "мне понадобится перевод такого-то слова на такой-то язык". он накапливает запросы. когда начинается показ страницы, он одним обращением к БД получает все нужные переводы. далее объекты спрашивают у него эти переводы по мере необходимости.
// это ещё должно как-то кэшироваться, но скорее всего на уровне БД и php.
class Entity_dictionary extends Entity_combo
{
	public $def='rus', $cache=array(); // это параметры мелкого кэша. язык по умолчанию - это язык, на котором понадобится львиная часть сообщений. ведь обычно весь сайт на одном языке.

	// запрос перевода конкретного слова на нужный язык. код - это уникальное кодовое название слова или куска текста, который надо перевести.
	public function translate($code, $lang='')
	{
		if ($lang=='') $lang=$this->def; // если язык не указан, берётся по умолчанию.
		if (is_array($code)) // если запрашивается массив переводов, то рекурсия.
		{
			$trans=array();
			foreach ($code as $c)
			{
				$trans[$c]=$this->translate($c, $lang);
			}
			return $trans;
		}
		else // если запрашивается конкретное слово или выражение.
		{
			if (($lang==$this->def)&&(array_key_exists($code, $this->cache))) return $this->cache[$code]; // если есть в кэше, возвращаем оттуда.
			$trans='';
			if (!$this->exists($code)) $trans=$code; // если даже выражения с таким кодом нет, придётся вернуть код.
			if ($trans=='') $trans=$this->byrole[$code][0]->translate($lang); // если выражение есть, пробуем перевести с помощью него.
			if ($trans=='') $trans=$code; // если пусто, возвращаем код.
			if ($lang==$this->def) $this->cache[$code]=$trans; // записываем кэщ.
			return $trans;
		}
	}
}

// это тестовый класс покемона, пока в разработке.
class Entity_neo extends Entity_combo
{
	public $model=array(
		'id'=>'natural',
		'name'=>'translation',
		'nickname'=>'translation',
		'type1'=>'poketype',
		'type2'=>'poketype',
		'height'=>'real',
		'weight'=>'real',
		'area'=>'pokearea',
		'body'=>'pokebody',
	);
}
?>