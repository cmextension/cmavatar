<?php
/**
 * @package    PlgUserCMAvatar
 * @copyright  Copyright (C) 2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('file');

/**
 * Form field for avatar.
 *
 * @package  PlgUserCMAvatar
 * @since    1.0.0
 */
class JFormFieldCMAvatar extends JFormFieldFile
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	protected $type = 'CMAvatar';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.0.0
	 */
	protected function getInput()
	{
		if (empty($this->value))
		{
			$currentAvatar = JText::_('PLG_USER_CMAVATAR_NO_AVATAR');
		}
		else
		{
			$currentAvatar = '<img src="' . $this->value . '">';
		}

		$uploadField = parent::getInput();

		if (empty($this->value))
		{
			$deleteField = '';
		}
		else
		{
			$deleteField = '<input type="checkbox" value="yes" name="delete-avatar" id="deleteAvatar">';
		}

		$data = array(
			'current_avatar'	=> $currentAvatar,
			'upload_field'		=> $uploadField,
			'delete_field'		=> $deleteField,
		);

		$layout = new JLayoutFile('default', $basePath = JPATH_PLUGINS . '/user/cmavatar/layouts');
		$html = $layout->render($data);

		return $html;
	}
}
