<?php
/**
 * @package    PlgUserCMAvatar
 * @copyright  Copyright (C) 2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Helper for plg_user_cmavatar plugin.
 *
 * @package  PlgUserCMAvatar
 * @since    1.0.0
 */
class PlgUserCMAvatarHelper
{
	/**
	 * Get user's avatar.
	 *
	 * @param   integer  $userId  The ID of the user.
	 *
	 * @return  string   The path to the avatar's file.
	 *
	 * @since   1.0.0
	 */
	public static function getAvatar($userId)
	{
		$profileKey = 'cmavatar';

		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('profile_value'))
			->from($db->qn('#__user_profiles'))
			->where($db->qn('user_id') . ' = ' . $db->q((int) $userId))
			->where($db->qn('profile_key') . ' = ' . $db->quote($profileKey));
		$avatar = $db->setQuery($query)->loadResult();

		// Check for a database error.
		if ($error = $db->getErrorMsg())
		{
			throw new RuntimeException($error);

			return false;
		}

		if (!empty($avatar))
		{
			$extension = 'jpg';
			$plugin = JPluginHelper::getPlugin('user', 'cmavatar');
			$params = new JRegistry($plugin->params);
			$folder = $params->get('folder', '');

			$avatar = JPath::clean($folder . '/' . $avatar . '.' . $extension);
		}

		return $avatar;
	}
}
