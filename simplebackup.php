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

use Joomla\CMS\Filesystem\File;

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

// Scan directory and delete all files with extension
// Example: remove_ext($file='/home/ribafs/teste.zip', $ext='zip')
function remove_ext($file, $ext){
	$handle = opendir($file);

	while (false !== ($file2 = readdir($handle))) {
		$ext2 =  JFile::getExt($file2);

		if($ext2 == $ext && $file2 != '.' && $file2 != '..') {
			unlink($file.DS.$file2);
		}
	}
	closedir($handle);
}

remove_ext(JPATH_ROOT, 'sql');
remove_ext(JPATH_ROOT.DS, 'zip');

// Pre-Load configuration
require_once( JPATH_CONFIGURATION.DS.'configuration.php' );

$date = date("Y_m_d");
$config = JFactory::getApplication(); 

if(JFactory::getApplication()->input->post->get('send')){

    $db = JPATH_SITE.DS.$database.'_'.$date.'.sql';

    system("mysqldump -u$dbuser -p$dbpass $database > $db");

   $zip = JPATH_SITE.DS.$database.'_'.$date.'.zip';

File::delete($db);

$zipw = basename(JPATH_SITE);
$jp=JPATH_SITE;

//print $site_dir;exit;
$zipw=basename($zipw);

$zipw = '..'.DS.'..'.DS.$zipw.DS.$database.'_'.$date.'.zip';

    system("cd $jp ; cd .. ; zip -rq $zip $site_dir");

    JFactory::getApplication()->enqueueMessage( JText::_('COM_SIMPLEBACKUP_SUCCESS'),'message');

?>
<br>
<h3>Downloads</h3>
<br>
<a href="<?php print $zipw;?>"> <?php print JText::_('COM_SIMPLEBACKUP_FILES');?></a><br>
<?php
}
?>
