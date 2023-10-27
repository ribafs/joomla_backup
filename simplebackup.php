<?php
/**
* @author-name Ribamar FS
* @copyright	Copyright (C) 2010 Ribamar FS.
* @license		GNU/GPL, see http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
* simplebackupfiles is free and open source software. This version may have been modified 
* pursuant to the GNU General Public License, and as distributed it includes or is 
* derivative of works licensed under the GNU General Public License or other free or 
* open source software licenses. 
*/

defined('_JEXEC') or die('Restricted access');

$site_dir = basename(JPATH_SITE);

//print $site_dir;exit;

// Backup do banco
$config = JFactory::getApplication(); 

$dbhost = $config->getCfg('host');
$dbuser = $config->getCfg('user');
$dbpass = $config->getCfg('password');
$database = $config->getCfg('db');

if(!defined('DS')){
	define('DS',DIRECTORY_SEPARATOR);
}

// Backup do Banco
JToolBarHelper::title( JText::_( 'COM_SIMPLEBACKUP_FILES_DATABASE' ), 'addedit.png' );
?>

<form action="" method="post" name="adminForm" id="adminForm">
<input type="submit" name="send" class="btn btn-primary" value="<?php print JText::_('COM_SIMPLEBACKUP_START');?>">
</form>

<?php
// Pre-Load configuration
require_once( JPATH_CONFIGURATION.DS.'configuration.php' );

$date = date("Y_m_d-H_i");
$config = JFactory::getApplication(); 

if(JFactory::getApplication()->input->post->get('send')){
	
//	$backup=basename(JPATH_ROOT).DS.basename(JPATH_ADMINISTRATOR).DS.'components'.DS.'com_simplebackup'.DS.'backups';
	$backup='components'.DS.'com_simplebackup'.DS.'backups';
	$backupmv=basename(JPATH_ADMINISTRATOR).DS.'components'.DS.'com_simplebackup'.DS.'backups';
	$backupdel=JPATH_ADMINISTRATOR.DS.'components'.DS.'com_simplebackup'.DS.'backups'.DS.'*';

	system("rm $backupdel");

	$db = JPATH_SITE.DS.$database.'_'.$date.'.sql';
	$jp=JPATH_SITE;
	
	system("mysqldump -u$dbuser -p$dbpass $database > $db");
	system("cd $jp ; mv $db $backupmv");

	$zip = JPATH_SITE.DS.$database.'_'.$date.'.zip';

	system("cd $jp ; cd .. ; zip -rq $zip $site_dir");

	system("cd $jp ; mv $zip $backupmv");

	$zipw = $backup.DS.$database.'_'.$date.'.zip';
	$sqlw = $backup.DS.$database.'_'.$date.'.sql';
//print $sqlw;exit;
	JFactory::getApplication()->enqueueMessage( JText::_('COM_SIMPLEBACKUP_SUCCESS'),'message');
	
	?>
	<br>
	<a href="<?php print $zipw;?>"> <?php print JText::_('ZIP');?></a><br><br>
	<a href="<?php print $sqlw;?>"> <?php print JText::_('SQL');?></a><br>
	<?php
}
?>
