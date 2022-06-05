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

ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

ini_set('memory_limit', '5060M');
ini_set('max_execution_time', 3600);
ini_set("date.timezone", "America/Fortaleza");

jimport('joomla.filesystem.archive');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

//$site_dir = basename(JPATH_SITE);

// Backup do banco
$config = JFactory::getApplication(); 

$dbhost = $config->getCfg('host');
$dbuser = $config->getCfg('user');
$dbpass = $config->getCfg('password');
$database = $config->getCfg('db');

if(!defined('DS')){
	define('DS',DIRECTORY_SEPARATOR);
}

// backup all tables in db
function backup_tables($dbhost,$dbuser,$dbpass,$database,$date)
{
        //connect to db
        $link = mysqli_connect($dbhost,$dbuser,$dbpass);
        mysqli_set_charset($link,'utf8');
        mysqli_select_db($link,$database);

        //get all of the tables
        $tables = array();
        $result = mysqli_query($link, 'SHOW TABLES');
        while($row = mysqli_fetch_row($result))
        {
            $tables[] = $row[0];
        }

        //disable foreign keys (to avoid errors)
        $return = 'SET FOREIGN_KEY_CHECKS=0;' . "\r\n";
        $return.= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';" . "\r\n";
        $return.= 'SET AUTOCOMMIT=0;' . "\r\n";
        $return.= 'START TRANSACTION;' . "\r\n";

        //cycle through
        foreach($tables as $table)
        {
            $result = mysqli_query($link, 'SELECT * FROM '.$table);
            $num_fields = mysqli_num_fields($result);
            $num_rows = mysqli_num_rows($result);
            $i_row = 0;

            $row2 = mysqli_fetch_row(mysqli_query($link,'SHOW CREATE TABLE '.$table));
            $return.="\nDROP TABLE IF EXISTS `".$table.'`;';
            $return.= "\n".$row2[1].";\n\n"; 

            if ($num_rows !== 0) {
                $row3 = mysqli_fetch_fields($result);
                $return.= 'INSERT INTO '.$table.'( ';
                foreach ($row3 as $th) 
                { 
                    $return.= '`'.$th->name.'`, '; 
                }
                $return = substr($return, 0, -2);
                $return.= ' ) VALUES';

                for ($i = 0; $i < $num_fields; $i++) 
                {
                    while($row = mysqli_fetch_row($result))
                    {
                        $return.="\n(";
                        for($j=0; $j<$num_fields; $j++) 
                        {
                            //$row[$j] = addslashes($row[$j]);
                            $row[$j] = addslashes($row[$j] ?? '');// PHP 8.1				
                            $row[$j] = preg_replace("#\n#","\\n",$row[$j]);
                            if (isset($row[$j])) { $return.= "'".$row[$j]."'" ; } else { $return.= "''"; }
                            if ($j<($num_fields-1)) { $return.= ','; }
                        }
                        if (++$i_row == $num_rows) {
                            $return.= ");"; // last row
                        } else {
                            $return.= "),"; // not last row
                        }   
                    }
                }
            }
            $return.="\n\n\n";
        }

        // enable foreign keys
        $return .= 'SET FOREIGN_KEY_CHECKS=1;' . "\r\n";
        $return.= 'COMMIT;';

    	$date = date("Y-m-d_H-i");
    	//$site_dir = basename(JPATH_SITE);

    	//$db = JPATH_SITE.DS.'tmp'.DS.$site_dir.'_'.$date.'.sql';
        $db = JPATH_SITE.DS.$database.'_'.$date.'.sql';

        $handle = fopen($db,'w+');
        fwrite($handle,$return);
        fclose($handle);

}

// Créditos: http://stackoverflow.com/questions/81934/easy-way-to-export-a-sql-table-without-access-to-the-server-or-phpmyadmin

// \Backup dpo Banco
JToolBarHelper::title( JText::_( 'COM_SIMPLEBACKUP_FILES_DATABASE' ), 'addedit.png' );
?>

<form action="" method="post" name="adminForm" id="adminForm">
	<input type="submit" name="send" class="btn btn-primary" value="<?php print JText::_('COM_SIMPLEBACKUP_SEND');?>">
</form>

<?php

// Scan directory and delete all files with extension
// Example: remove_ext($file='/home/ribafs/teste.zip', $ext='zip')
function remove_ext($file, $ext){
	$handle = opendir($file);

	//$name = JFile::stripExt($portal2);
	// This is the correct way to loop over the directory.
	while (false !== ($file2 = readdir($handle))) {
		$ext2 =  JFile::getExt($file2);

		if($ext2 == $ext && $file2 != '.' && $file2 != '..') {
			unlink($file.DS.$file2);
		}
	}
	closedir($handle);
}

remove_ext(JPATH_ROOT, 'sql');
remove_ext(JPATH_ROOT.DS.'tmp', 'zip');

// Pre-Load configuration
require_once( JPATH_CONFIGURATION.DS.'configuration.php' );

$date = date("Y-m-d_H-i");
$config = JFactory::getApplication(); 
//$portal2 = '..'.DS.'tmp'.DS.$site_dir. '_'. $date . '.zip';
$portal2 = '..'.DS.'tmp'.DS.$database. '_'. $date . '.zip';

if(JFactory::getApplication()->input->post->get('send')){
//if(JRequest::getVar('send')){ // A classe JRequest não mais é suportada na versão 4 do Joomla

function Zip($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true){
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

      foreach ($files as $file){
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                continue;

            $file = realpath($file);

            if (is_dir($file) === true){					
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }else if (is_file($file) === true){
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
	// Crédito: http://stackoverflow.com/questions/1334613/how-to-recursively-zip-a-directory-in-php
}
$date = date("Y-m-d_H-i");
$db_file = JURI::root().'tmp'.DS.$database.'_'.$date.'.sql';						

backup_tables($dbhost,$dbuser,$dbpass,$database,$date);
Zip("..".DS, $portal2);

JFactory::getApplication()->enqueueMessage( JText::_('COM_SIMPLEBACKUP_SUCCESS'),'message');
?>
<h3>Downloads</h3>
<a href="<?php print $portal2;?>"> <?php print JText::_('COM_SIMPLEBACKUP_FILES');?></a><br>
<?php
}
?>
