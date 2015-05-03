<?php
/**
 * @package    PlgUserCMAvatar
 * @copyright  Copyright (C) 2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

$currentAvatar = $displayData['current_avatar'];
$uploadField = $displayData['upload_field'];
$deleteField = $displayData['delete_field'];
?>
<div class="cmavatar">
	<?php if (!empty($currentAvatar)) : ?>
	<div class="control-group">
		<?php echo $currentAvatar; ?>
	</div>
	<?php endif; ?>

	<?php if (!empty($uploadField)) : ?>
	<div class="control-group">
		<?php echo $uploadField; ?>
	</div>
	<?php endif; ?>

	<?php if (!empty($deleteField)) : ?>
	<div class="control-group checkbox">
		<?php echo JText::_('PLG_USER_CMAVATAR_DELETE_AVATAR'); ?> <?php echo $deleteField; ?>
	</div>
	<?php endif; ?>
</div>