<?
// этот классы скрывает код, которым сущности показывают себя.
// пока что реализация этого условлена не до конца... может быть, в итоге это будет сделано каким-то другим методом. но пока что это помогает не загружать сервер лишним кодом во время выполнения операций, не требующих ничего показывать пользователю.
class EntityDisplay
{
	public static function display_combo(Entity_combo $who, $style='raw')
	{
		$who->style=$style; // приходится использовать переменную, потому что нет возможности удобно передавать аргументы в callback (по крайней мере до php 5.4, а его ещё надо поставить, да и там, кажется, только для анонимных функций.
		
		// на демонстрацию внутренних сущностей заменяется следующая конструкция:
		// %роль% - показывает сущность данной роли с индексом 0.
		// %роль~%- показывает все сущности данной роли. Часто это всего одна. Если их несколько, они просто клеются подряд (STUB).
		// %роль20% - показывает сущность данной роли с индексом 20.
		// %роль20[title]% - показывает стиль "title" сущности с индексом 20 и указанной ролью.
		// %роль~[title]- показывает все сущности данной роли в стиле title.
		
		$result=preg_replace_callback(
			'/%(?<role>[a-z_]+)(?<index>\d*|~)(\[(?<style>[a-z]+)\])?%/i',
			array($who, 'display_subentity'),
			$who->format
			);
		return $result;
	}
	
	public static function display_subentity(Entity $who, $role, $index=null, $style='')
	{
		if (is_array($role)) // когда вызывается как callback, то разбираем аргументы по полочкам и вызываем заново. это сделано для удобства записи вызова обычным образом.
		{
			$m=$role;
			return static::display_subentity($who, $m['role'], $m['index'], $m['style']);
		}
		
		$result='';
		if ($role=='') $role='norole';
		if ($style=='') $style=$who->style; // во время preg_replace_callback в эту переменную записывается стиль по умолчанию.
		if ($index==='~') // если указано показать все...
		{
			if (!$who->exists($role)) $result.= 'Нет объектов: '.$role; // STUB
			else
			{
				foreach ($who->byrole[$role] as $index=>$entity)
				{
					$result.=static::display_subentity($who, $role, $index, $style); // перебираем и складываем. STUB! очевидно, формат сложения должен быть в $model
				}
			}
		}
		else
		{
			$index=(int)($index); // если индекса нет, он становится нулём.
			// если внутренняя сущность задана, обращаемся к ней. иначе выдаём знак вопроса.
			if (isset($who->byrole[$role][$index])) $result=$who->byrole[$role][$index]->display($style);
			else $result='?'; // STUB
		}
		return $result;		
	}
	
	// STUB: в будущем тут должны быть обработчики текста для показа в html и текстовом поле.
	// STUB: эта функция не учитывает допустимости данных.
	// текущие стили:
	// raw - тупо основное значение, без оформления.
	// rawtitle - тупо заголовок, без оформления.
	// title - заголовок с оформлением.
	// view - оформленный заголовок и далее значение.
	// input - оформленный заголовок и далее поле ввода.	
	public static function display_value(Entity_value $who, $style='raw')
	{
		if ($style=='raw') $result= $who->data['value'];
		elseif ($style=='rawtitle') $result= $who->rules['title'];
		elseif ($style=='title') $result= '<strong>'.$who->rules['title'].'</strong>: ';
		elseif ($style=='view') $result= $who->display('title').' '.$who->data['value'];
		elseif ($style=='input') $result= $who->display('title').'<input type=text name="'.$who->input_name().'" value="'.htmlspecialchars($who->data['value']).'" />';
		else $result='Неизвестный стиль!'; // STUB
		return $result;
	}
}