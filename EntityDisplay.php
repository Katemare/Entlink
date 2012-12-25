<?
// ���� ������ �������� ���, ������� �������� ���������� ����.
// ���� ��� ���������� ����� ��������� �� �� �����... ����� ����, � ����� ��� ����� ������� �����-�� ������ �������. �� ���� ��� ��� �������� �� ��������� ������ ������ ����� �� ����� ���������� ��������, �� ��������� ������ ���������� ������������.
class EntityDisplay
{
	public static function display_combo(Entity_combo $who, $style='raw')
	{
		$who->style=$style; // ���������� ������������ ����������, ������ ��� ��� ����������� ������ ���������� ��������� � callback (�� ������� ���� �� php 5.4, � ��� ��� ���� ���������, �� � ���, �������, ������ ��� ��������� �������.
		
		// �� ������������ ���������� ��������� ���������� ��������� �����������:
		// %����% - ���������� �������� ������ ���� � �������� 0.
		// %����~%- ���������� ��� �������� ������ ����. ����� ��� ����� ����. ���� �� ���������, ��� ������ ������� ������ (STUB).
		// %����20% - ���������� �������� ������ ���� � �������� 20.
		// %����20[title]% - ���������� ����� "title" �������� � �������� 20 � ��������� �����.
		// %����~[title]- ���������� ��� �������� ������ ���� � ����� title.
		
		$result=preg_replace_callback(
			'/%(?<role>[a-z_]+)(?<index>\d*|~)(\[(?<style>[a-z]+)\])?%/i',
			array($who, 'display_subentity'),
			$who->format
			);
		return $result;
	}
	
	public static function display_subentity(Entity $who, $role, $index=null, $style='')
	{
		if (is_array($role)) // ����� ���������� ��� callback, �� ��������� ��������� �� �������� � �������� ������. ��� ������� ��� �������� ������ ������ ������� �������.
		{
			$m=$role;
			return static::display_subentity($who, $m['role'], $m['index'], $m['style']);
		}
		
		$result='';
		if ($role=='') $role='norole';
		if ($style=='') $style=$who->style; // �� ����� preg_replace_callback � ��� ���������� ������������ ����� �� ���������.
		if ($index==='~') // ���� ������� �������� ���...
		{
			if (!$who->exists($role)) $result.= '��� ��������: '.$role; // STUB
			else
			{
				foreach ($who->byrole[$role] as $index=>$entity)
				{
					$result.=static::display_subentity($who, $role, $index, $style); // ���������� � ����������. STUB! ��������, ������ �������� ������ ���� � $model
				}
			}
		}
		else
		{
			$index=(int)($index); // ���� ������� ���, �� ���������� ����.
			// ���� ���������� �������� ������, ���������� � ���. ����� ����� ���� �������.
			if (isset($who->byrole[$role][$index])) $result=$who->byrole[$role][$index]->display($style);
			else $result='?'; // STUB
		}
		return $result;		
	}
	
	// STUB: � ������� ��� ������ ���� ����������� ������ ��� ������ � html � ��������� ����.
	// STUB: ��� ������� �� ��������� ������������ ������.
	// ������� �����:
	// raw - ���� �������� ��������, ��� ����������.
	// rawtitle - ���� ���������, ��� ����������.
	// title - ��������� � �����������.
	// view - ����������� ��������� � ����� ��������.
	// input - ����������� ��������� � ����� ���� �����.	
	public static function display_value(Entity_value $who, $style='raw')
	{
		if ($style=='raw') $result= $who->data['value'];
		elseif ($style=='rawtitle') $result= $who->rules['title'];
		elseif ($style=='title') $result= '<strong>'.$who->rules['title'].'</strong>: ';
		elseif ($style=='view') $result= $who->display('title').' '.$who->data['value'];
		elseif ($style=='input') $result= $who->display('title').'<input type=text name="'.$who->input_name().'" value="'.htmlspecialchars($who->data['value']).'" />';
		else $result='����������� �����!'; // STUB
		return $result;
	}
}