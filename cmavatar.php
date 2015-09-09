<?php
/**
 * @package    PlgUserCMAvatar
 * @copyright  Copyright (C) 2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

/**
 * Joomla!'s user profile plugin for user avatar.
 *
 * @package  PlgUserCMAvatar
 * @since    1.0.0
 */
class PlgUserCMAvatar extends JPlugin
{
	/**
	 * The profile key used in database.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	public $profileKey = 'cmavatar';

	/**
	 * The extension of the avatars.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	public $extension = 'jpg';

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since   1.0.0
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		JFormHelper::addFieldPath(__DIR__ . '/fields');
	}

	/**
	 * Runs on content preparation
	 *
	 * @param   string   $context  The context for the data
	 * @param   integer  $data     The user id
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function onContentPrepareData($context, $data)
	{
		// Only run in front-end.
		if (!JFactory::getApplication()->isSite())
		{
			return true;
		}

		// Check we are manipulating a valid form.
		if (!in_array($context, array('com_users.profile', 'com_users.user', 'com_users.registration', 'com_admin.profile')))
		{
			return true;
		}

		if (is_object($data))
		{
			$userId = isset($data->id) ? $data->id : 0;

			if (!isset($data->cmavatar['cmavatarcurrent']) and $userId > 0)
			{
				// Load the avatar's file name from the database.
				$currentAvatar = $this->getAvatar($userId);

				if (!empty($currentAvatar))
				{
					$folder = $this->params->get('folder', '');
					$avatarPath = $folder . '/' . $currentAvatar . '.' . $this->extension;

					$layout = JFactory::getApplication()->input->get('layout', 'default');

					if ($layout != 'default')
					{
						$data->cmavatar['cmavatar'] = $avatarPath;
					}
					else
					{
						$html = '<div class="cmavatar">';
						$html .= '<img src="' . $avatarPath . '">';
						$html .= '</div>';
						$data->cmavatar['cmavatar'] = $html;
					}
				}
				else
				{
					$data->cmavatar['cmavatar'] = '';
				}
			}
		}

		return true;
	}

	/**
	 * Add additional field to the user editing form
	 *
	 * @param   JForm  $form  The form to be altered.
	 * @param   array  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		if (!($form instanceof JForm))
		{
			throw new RuntimeException(JText::_('JERROR_NOT_A_FORM'));

			return false;
		}

		// Only run in front-end.
		if (!JFactory::getApplication()->isSite())
		{
			return true;
		}

		// Check we are manipulating a valid form.
		$name = $form->getName();

		if (!in_array($name, array('com_admin.profile', 'com_users.user', 'com_users.profile', 'com_users.registration')))
		{
			return true;
		}

		$folder = $this->params->get('folder', '');
		$avatarFolder = JPATH_ROOT . '/' . $folder;

		// If the avatar folder doesn't exist, we don't display the fields.
		if (!JFolder::exists($avatarFolder))
		{
			return true;
		}

		$layout = JFactory::getApplication()->input->get('layout', 'default');

		if ($layout != 'default' || ($layout == 'default' && $this->params->get('display_avatar_in_profile', 0) == 1))
		{
			JForm::addFormPath(__DIR__ . '/profiles');
			$form->loadFile('profile', false);
		}

		return true;
	}

	/**
	 * Save user profile data.
	 *
	 * @param   array    $data    Entered user data
	 * @param   boolean  $isNew   True if this is a new user
	 * @param   boolean  $result  True if saving the user worked
	 * @param   string   $error   Error message
	 *
	 * @return  boolean
	 */
	public function onUserAfterSave($data, $isNew, $result, $error)
	{
		// Only run in front-end.
		if (!JFactory::getApplication()->isSite())
		{
			return true;
		}

		$userId = JArrayHelper::getValue($data, 'id', 0, 'int');
		$folder = $this->params->get('folder', '');
		$avatarFolder = JPATH_ROOT . '/' . $folder;

		// If the avatar folder doesn't exist, we don't do anything.
		if (!JFolder::exists($avatarFolder))
		{
			return false;
		}

		$jinput = JFactory::getApplication()->input;
		$delete = $jinput->get('delete-avatar', '', 'word');

		if ($delete == 'yes')
		{
			$this->deleteAvatar($userId);

			return true;
		}

		if ($result && $userId > 0)
		{
			$files = $jinput->files->get('jform', array(), 'array');

			if (!isset($files['cmavatar']['cmavatar']))
			{
				return false;
			}

			$file = $files['cmavatar']['cmavatar'];

			if (empty($file['name']))
			{
				return true;
			}

			$fileTypes = explode('.', $file['name']);

			if (count($fileTypes) < 2)
			{
				// There seems to be no extension.
				throw new RuntimeException(JText::_('PLG_USER_CMAVATAR_ERROR_FILE_TYPE'));

				return false;
			}

			array_shift($fileTypes);

			// Check if the file has an executable extension.
			$executable = array(
				'php', 'js', 'exe', 'phtml', 'java', 'perl', 'py', 'asp','dll', 'go', 'ade', 'adp', 'bat', 'chm', 'cmd', 'com', 'cpl', 'hta', 'ins', 'isp',
				'jse', 'lib', 'mde', 'msc', 'msp', 'mst', 'pif', 'scr', 'sct', 'shb', 'sys', 'vb', 'vbe', 'vbs', 'vxd', 'wsc', 'wsf', 'wsh'
			);

			$check = array_intersect($fileTypes, $executable);

			if (!empty($check))
			{
				throw new RuntimeException(JText::_('PLG_USER_CMAVATAR_ERROR_FILE_TYPE'));

				return false;
			}

			$fileType = array_pop($fileTypes);
			$allowable = array_map('trim', explode(',', $this->params->get('allowed_extensions')));

			if ($fileType == '' || $fileType == false || (!in_array($fileType, $allowable)))
			{
				throw new RuntimeException(JText::_('PLG_USER_CMAVATAR_ERROR_FILE_TYPE'));

				return false;
			}

			$uploadMaxSize = $this->params->get('max_size', 0) * 1024 * 1024;
			$uploadMaxFileSize = $this->toBytes(ini_get('upload_max_filesize'));

			if (($file['error'] == 1)
				|| ($uploadMaxSize > 0 && $file['size'] > $uploadMaxSize)
				|| ($uploadMaxFileSize > 0 && $file['size'] > $uploadMaxFileSize))
			{
				throw new RuntimeException(JText::_('PLG_USER_CMAVATAR_ERROR_FILE_TOO_LARGE'));

				return false;
			}

			// Make the file name unique.
			$md5String = $userId . $file['name'] . JFactory::getDate();
			$avatarFileName = JFile::makeSafe(md5($md5String));

			if (empty($avatarFileName))
			{
				// No file name after the name was cleaned by JFile::makeSafe.
				throw new RuntimeException(JText::_('PLG_USER_CMAVATAR_ERROR_NO_FILENAME'));

				return false;
			}

			$avatarPath = JPath::clean($avatarFolder . '/' . $avatarFileName . '.' . $this->extension);

			if (JFile::exists($avatarPath))
			{
				// A file with this name already exists. It is almost impossible.
				throw new RuntimeException(JText::_('PLG_USER_CMAVATAR_ERROR_FILE_EXISTS'));

				return false;
			}

			// Start resizing the file.
			$avatar = new JImage($file['tmp_name']);
			$originalWidth = $avatar->getWidth();
			$originalHeight = $avatar->getHeight();
			$ratio = $originalWidth / $originalHeight;

			$maxWidth = (int) $this->params->get('width', 100);
			$maxHeight = (int) $this->params->get('height', 100);

			// Invalid value in the plugin configuration. Set avatar width to 100.
			if ($maxWidth <= 0)
			{
				$maxWidth = 100;
			}

			if ($maxHeight <= 0)
			{
				$maxHeight = 100;
			}

			if ($originalWidth > $maxWidth)
			{
				$ratio = $originalWidth / $originalHeight;

				$newWidth = $maxWidth;
				$newHeight = $newWidth / $ratio;

				if ($newHeight > $maxHeight)
				{
					$ratio = $newWidth / $newHeight;

					$newHeight = $maxHeight;
					$newWidth = $newHeight * $ratio;
				}
			}
			elseif ($originalHeight > $maxHeight)
			{
				$ratio = $originalWidth / $originalHeight;

				$newHeight = $maxHeight;
				$newWidth = $newHeight * $ratio;

				if ($newWidth > $maxWidth)
				{
					$ratio = $newWidth / $newHeight;

					$newWidth = $maxWidth;
					$newHeight = $newWidth / $ratio;
				}
			}
			else
			{
				$newWidth = $originalWidth;
				$newHeight = $originalHeight;
			}

			$resizedAvatar = $avatar->resize($newWidth, $newHeight, true);
			$resizedAvatar->toFile($avatarPath);

			// Delete current avatar if exists.
			$this->deleteAvatar($userId);

			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			// Save avatar's file name to database.
			if (!empty($currentAvatar))
			{
				$query->update($db->qn('#__user_profiles'))
					->set($db->qn('profile_value') . ' = ' . $db->q($avatarFileName))
					->where($db->qn('user_id') . ' = ' . $db->q($userId))
					->where($db->qn('profile_key') . ' = ' . $db->quote($this->profileKey));
			}
			else
			{
				$query->insert($db->qn('#__user_profiles'))
					->columns(
						$db->qn(
							array(
								'user_id',
								'profile_key',
								'profile_value',
								'ordering'
							)
						)
					)
					->values(
						$db->q($userId) . ', ' .
						$db->q($this->profileKey) . ', ' .
						$db->q($avatarFileName) . ', ' .
						$db->q('1')
					);
			}

			$db->setQuery($query)->execute();

			// Check for a database error.
			if ($error = $db->getErrorMsg())
			{
				throw new RuntimeException($error);

				return false;
			}
		}

		return true;
	}

	/**
	 * Remove avatar for the given user ID.
	 *
	 * Method is called after user data is deleted from the database.
	 *
	 * @param   array    $user     Holds the user data
	 * @param   boolean  $success  True if user was succesfully stored in the database
	 * @param   string   $msg      Message
	 *
	 * @return  boolean
	 */
	public function onUserAfterDelete($user, $success, $msg)
	{
		if (!$success)
		{
			return false;
		}

		$userId = JArrayHelper::getValue($user, 'id', 0, 'int');

		if ($userId)
		{
			$this->deleteAvatar($userId);
		}

		return true;
	}

	/**
	 * Get current avatar.
	 *
	 * @param   integer  $userId  The ID of the user.
	 *
	 * @return  mixed
	 *
	 * @since   1.0.0
	 */
	protected function getAvatar($userId)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('profile_value'))
			->from($db->qn('#__user_profiles'))
			->where($db->qn('user_id') . ' = ' . $db->q((int) $userId))
			->where($db->qn('profile_key') . ' = ' . $db->quote($this->profileKey));
		$currentAvatar = $db->setQuery($query)->loadResult();

		// Check for a database error.
		if ($error = $db->getErrorMsg())
		{
			throw new RuntimeException($error);

			return false;
		}

		return $currentAvatar;
	}

	/**
	 * Delete avatar.
	 *
	 * @param   integer  $userId  The ID of the user.
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	protected function deleteAvatar($userId)
	{
		$folder = $this->params->get('folder', '');
		$avatarFolder = JPATH_ROOT . '/' . $folder;
		$currentAvatar = $this->getAvatar($userId);

		if (!empty($currentAvatar))
		{
			$currentAvatarPath = JPath::clean($avatarFolder . '/' . $currentAvatar . '.' . $this->extension);

			if (JFile::exists($currentAvatarPath))
			{
				JFile::delete($currentAvatarPath);
			}

			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->delete($db->qn('#__user_profiles'))
				->where($db->qn('user_id') . ' = ' . $db->q((int) $userId))
				->where($db->qn('profile_key') . ' = ' . $db->quote($this->profileKey));
			$db->setQuery($query)->execute();

			// Check for a database error.
			if ($error = $db->getErrorMsg())
			{
				throw new RuntimeException($error);

				return false;
			}
		}

		return true;
	}

	/**
	 * Small helper function that properly converts any
	 * configuration options to their byte representation.
	 * From libraries/cms/helper/media.php
	 *
	 * @param   string|integer  $val  The value to be converted to bytes.
	 *
	 * @return integer The calculated bytes value from the input.
	 *
	 * @since 1.0.0
	 */
	protected function toBytes($val)
	{
		switch ($val[strlen($val) - 1])
		{
			case 'M':
			case 'm':
				return (int) $val * 1048576;
			case 'K':
			case 'k':
				return (int) $val * 1024;
			case 'G':
			case 'g':
				return (int) $val * 1073741824;
			default:
				return $val;
		}
	}
}
