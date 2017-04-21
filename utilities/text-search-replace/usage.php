<?php
/**
* Class : TextSearch
*
* @author  :  MA Razzaque Rupom <rupom_315@yahoo.com>, <rupom.bd@gmail.com>
*             Moderator, phpResource Group(http://groups.yahoo.com/group/phpresource/)
*             URL: http://www.rupom.info  
*        
* @version :  1.0
* Date     :  06/25/2006
* Purpose  :  Searching and replacing text within files of specified path
*/

require_once('TextSearch.class.php');

$path = "/projects/rupom/Recent_FDD/Very_Imps"; //setting search path
$logFile = "/projects/rupom/test_search/searchResult.txt"; //setting log file
 
$obj = new TextSearch();
$obj->setExtensions(array('html','txt')); //setting extensions to search files within
$obj->addExtension('php');//adding an extension to search within
$obj->setSearchKey('PHP');
$obj->setReplacementKey('phpResource');//setting replacement text if you want to replace matches with that
$obj->startSearching($path);//starting search
$obj->showLog();//showing log
$obj->writeLogToFile($logFile); //writting result to log file

?>