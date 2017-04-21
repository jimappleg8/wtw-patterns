<?php

/**
 * Example file to demonstrate use of directory_info class
 *
 * File: example/example.php
 * @package directory_info
 */

	include( '../directoryinfo.inc.php' );

	// Directory path relative to the location of this file
	$pathtodir = '';

	if( $pathtodir === '' ) {
		trigger_error( 'Test directory not set by user - the class will use the current directory (which should yield mostly empty results).', E_USER_WARNING );
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html dir="ltr" lang="en-UK">
<head>

<title>Example file for directory_info class</title>

  <meta name="title" content="Example file for directory_info class :: Advies en zo - Meedenken en -doen" />
  <meta name="author" content="Juliette Reinders Folmer, Advies en zo, http://www.adviesenzo.nl/" />
  <meta name="owner" content="Advies en zo, http://www.adviesenzo.nl/" />
  <meta name="design" content="Advies en zo, http://www.adviesenzo.nl/" />
  <meta name="publisher" content="Advies en zo, http://www.adviesenzo.nl/" />
  <meta name="copyright" content="Copyright Advies en zo 2006 - all rights reserved" />
  <meta name="language" content="en-UK" />
  <meta name="distribution" content="global" />
  <meta name="rating" content="General" />
  <meta http-equiv="Charset" content="ISO-8859-1" />

  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
  <meta http-equiv="Content-Language" content="English" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Script-type" content="text/javascript" />
  <meta http-equiv="window-target" content="_top" />
  <meta http-equiv="imagetoolbar" content="no" />
  <meta name="MSSmartTagsPreventParsing" content="true" />
  <meta name="robots" content="index,follow" />
  <meta name="revisit-after" content="28 days" />

<link rel="shortcut icon" href="http://www.adviesenzo.nl/favicon.ico" />
<link rel="bookmark icon" href="http://www.adviesenzo.nl/favicon.ico" />
<link rel="icon" href="http://www.adviesenzo.nl/favicon.ico" type="image/ico" />

<link type="text/css" rel="stylesheet" href="http://www.adviesenzo.nl/stijl.css" />

<script type="text/javascript" src="../../js/tabber.js" />

<script type="text/javascript">

   var disqus_developer = 1; 

</script> 

</head>
<body>

<div id="primary">
<ul class="links"><li class="menu-285 active-trail first active"><a href="/" title="" class="active">Home</a></li>
<li class="menu-782"><a href="/content/about-jim-applegate" title="">About Me</a></li>
<li class="menu-446 last"><a href="/content/celestial-seasonings" title="Celestial Seasonings">Portfolio</a></li>
</ul>
</div>

<div id="page"><div id="page-inner">

   <a name="top" id="navigation-top"></a>

   <div id="header"><div id="header-inner" class="clear-block">

      
      <h1 id="site-name"><a href="/" title="Home" rel="home">World that Works                </a></h1>
                      
      <div id="site-slogan">A world view from the mind of Jim Applegate</div>  
      
   </div></div> <!-- /#header-inner, /#header -->

<div class="head">
	<a href="http://www.adviesenzo.nl/index.html" title="Visit the homepage of Advies en zo"><img src="http://www.adviesenzo.nl/images/logo_dpi120.gif" width="411" height="80" alt="Logo Advies en zo, Meedenken en -doen" /></a>
</div>

<div class="inhoud">
	<h1>Examples of using the directory_info class</h1>
	<p>
		<strong>Before you start, please point this example script to a test directory.
		If you haven't done so already, you should see a warning displayed above ;-)</strong>
	</p>
	<p>
		The directory variable for this example file should be a relative path based on the current directory this file is in.
	</p>
	<p>
		When using the script IRL (in real life), it should be a path relative to the initial calling file<br />
		For instance if you have an index.php file in which you include the function file, then the (image) directory would have to be indicated relative to where the index.php file is located. If you call the script directly, then the (image) directory would have to be indicated relative to where the dirlist script is located.<br />
		So let's presume you have your main calling script in "httpdocs" and the images in the directory "site", the directory name in the script would just be "site/".
	</p>
	<p>
		To get a good idea of what this class can do, the directory you point the script to will need to contain some files.
	</p>
	<p>
		A good testdirectory should look something like:
	</p>
	<pre>
	dirname/
		imagefile.jpg
		imagefile.gif
		imagefile.png
		textfile.txt
		phpfile.php
		.htaccess
		subdirectory/
			imagefile.jpg
			imagefile.png
			textfile.txt
			anotherfile.tmp
</pre>

	<p>
		If files/directories within the criteria given were found, the class will return an array with all the information. This allows you to format the results any way you like (i.e. in a select list or displayed on a page or.... )
	</p>
	<p>
		For lots more information on the available methods and variables in this class, have a look at the extensive <a href="../documentation/index.html" title="Go to the documentation">documentation</a> which was provided with this class.
	</p>


	<h2>Instantiating the class</h2>
	<p>To start: let's instantiate the class so it will remember results</p>
	<code>$dirobj = new directory_info();</code>

<?php
	$dirobj = new directory_info();
?>


	<h2>Example 1</h2>
	<p>A list of all image files with jpeg, jpg, gif or png extention in your directory with no information on files in subdirectories</p>
	<code>$myList = $dirobj->get_ext_based_filelist( null, $pathtodir, true );</code>
	<h4>Results in:</h4>

<?php
	$myList = $dirobj->get_ext_based_filelist( null, $pathtodir, true );
	print '<pre>filecount is : ' . $dirobj->fileselection_count . '<br /><br />';
	print_r( $myList );
//	print_r( $dirobj->filelist_selection ); // does the same
	print '</pre>';

	print '<p>which is a selection against the (larger) total filelist:</p>';
	print '<pre>filecount is : ' . $dirobj->filecount . '<br /><br />';
	print_r( $dirobj->filelist );
	print '</pre>';
?>


	<h2>Example 2</h2>
	<p>Let's make the current selection (image files) even more specific by saying we only want to see files modified in the last 4 weeks.</p>
	<code>$myList = $dirobj->get_files_modified_since( ( time() - (4 * 7 * 24 * 60 * 60 ) ), true, $pathtodir, true );</code>
	<h4>Results in:</h4>

<?php
	$comparets = ( time() - (4 * 7 * 24 * 60 * 60 ) );
	$myList = $dirobj->get_files_modified_since( $comparets, true, $pathtodir, true );
	print '<pre>filecount is : ' . $dirobj->fileselection_count . '<br /><br />';
	print_r( $myList );
	print '</pre>';
?>

	<h2>Example 3</h2>
	<p>Let's use the original list to select the most recent file.</p>
	<code>$myfile = $dirobj->get_most_recent_file( false );</code>
	<h4>Results in:</h4>

<?php
	$myfile = $dirobj->get_most_recent_file( false );
	print '<pre>';
	print_r( $myfile );
	print '</pre>';
?>

	<p>Hmm... that date doesn't look very good, let's make it a bit more readable using the class default datetimeformat:</p>
	<code>date( $dirobj->datetime_format, $myfile['last_modified'])</code>
	<p>which gives us:</p>

<?php
	print '<pre>' . date( $dirobj->datetime_format, $myfile['last_modified']) . '</pre>';
?>

	<h2>Example 4</h2>
	<p>Let's get some more information on the first 2 files (if we have 2) within the selection</p>
	<pre>for( $i=0; $i<2; $++ ) {
	$file = $dirobj->filelist_selection[$i];
	print '&lt;p&gt;filename is : ' . $file . '&lt;br&gt;';
	print 'filesize is ' . $dirobj->get_human_readable_filesize( $pathtodir . $file ) . '&lt;br&gt;';
	print 'last modified date is ' . $dirobj->get_human_readable_lastmod( $dirobj->last_path . $file ) . '&lt;br&gt;';
	print 'last access date is ' . $dirobj->get_human_readable_lastacc( $pathtodir . $file, 'l j F Y \a\t g:i a' ) . '&lt;br&gt;';
	print 'file permissions are ' . $dirobj->get_human_readable_file_permissions( $pathtodir . $file ) . '&lt;br&gt;';
	print 'file owner is ' . $dirobj->get_file_owner( $pathtodir . $file ) . '&lt;/p&gt;';
}</pre>
	<h4>Results in:</h4>

<?php
	$max = ( $dirobj->fileselection_count > 2 ) ? 2 : $dirobj->fileselection_count;
	for( $i=0; $i < $max; $i++ ) {
	 	$file = $dirobj->filelist_selection[$i];
		print '<pre>filename is : ' . $file . "\n";
		print 'file size is : ' . $dirobj->get_human_readable_filesize( $pathtodir . $file ) . "\n";
		print 'file was last modified on : ' . $dirobj->get_human_readable_lastmod( $dirobj->last_path . $file ) . "\n";
		print 'file was last accessed on : ' . $dirobj->get_human_readable_lastacc( $pathtodir . $file, 'l j F Y \a\t g:i a' ) . "\n";
		print 'file permissions are : ' . $dirobj->get_human_readable_file_permissions( $pathtodir . $file ) . "\n";
		print 'file owner is : ' . $dirobj->get_file_owner( $pathtodir . $file ) . '</pre>';
	}
?>


	<h2>Example 5</h2>
	<p>Ok, so maybe I want to know the total filesize of these files (based on the selection)</p>
	<code>$dirsize = $dirobj->get_dirsize( true );</code>
	<h4>Results in:</h4>

<?php
	$dirsize = $dirobj->get_dirsize( true );
	print '<pre>' . $dirsize . '</pre>';
?>


	<p>We can do better... we want that in a human readable format:</p>
	<pre>method 1:
$rdb_dirsize = $dirobj->get_human_readable_dirsize( true );

method 2:
$rdb_dirsize = $dirobj->human_readable_filesize( $dirsize );
	</pre>

<?php
	print '<pre>method 1: ' . $dirobj->get_human_readable_dirsize( true ) . "\n";
	print 'method 2: ' . $dirobj->human_readable_filesize( $dirsize ) . '(quicker if you already have a size)</pre>';
?>

	<p>Oh and while we are at it, let's also get the directory size of the total list:</p>
	<code>$rdb_dirsize = $dirobj->get_human_readable_dirsize();</code>
	<p>Only method 1 applies as we haven't got a size yet</p>

<?php
	print '<pre>' . $dirobj->get_human_readable_dirsize() . '</pre>';
?>

	<h2>Example 6</h2>
	<p>Ok, now I want to show ONE sorted list of all directories and the selected files with directories at the top, sorted in reverse order</p>
	<code>$myList = array_merge( $dirobj->get_sorted_dirlist( false), $dirobj->get_sorted_filelist( false, true ) );</code>

<?php
	print '<pre>';
	print_r ( array_merge( $dirobj->get_sorted_dirlist( false), $dirobj->get_sorted_filelist( false, true ) ) );
	print '</pre>';
?>


	<h2>Example 7</h2>
	<p>But heck.. this class can do more, you can just pass it a path to a file and get information about the file (independently of getting the file through a directory listing using this class)</p>
	<p>For the purpose of this example, I use the first file in your filelist - but you can just point the class to any file you like.</p>

<?php
	$pathtofile = $dirobj->filelist[0];
	//$pathtofile = $pathtodir . 'example/example.php';
	print '<pre>filename is : <i>' . $pathtofile . '</i></pre>';

	print '<pre>$dirobj->get_mime_content_type( $pathtofile )' . "\n\n";
	print 'mimetype is : <i>' . $dirobj->get_mime_content_type( $pathtofile ) . '</i></pre>';

	print '<pre>$dirobj->check_file_extension( $pathtofile, \'jpg\' )' . "\n\n";
	print 'File extension check gives : <i>' . ( $dirobj->check_file_extension( $pathtofile, 'php' ) ? 'true' : 'false' ) . '</i></pre>';

	print '<pre>$dirobj->check_file_mimetype( $pathtofile )' . "\n\n";
	print 'File mimetype check against image files (default) gives : <i>' . ( $dirobj->check_file_mimetype( $pathtofile ) ? 'true' : 'false' ) . '</i></pre>';

	print '<pre>$dirobj->check_allowed_file( $pathtofile, \jpg\', true )' . "\n\n";
	print 'Combined check gives : <i>' . ( $dirobj->check_allowed_file( $pathtofile, 'php', true ) ? 'true' : 'false' ) . '</i></pre>';

	print '<pre>$dirobj->get_filesize( $pathtofile )' . "\n\n";
	print 'file size is : <i>' . $dirobj->get_filesize( $pathtofile ) . '</i></pre>';

	print '<pre>$dirobj->get_human_readable_filesize( $pathtofile )' . "\n\n";
	print 'file size is : <i>' . $dirobj->get_human_readable_filesize( $pathtofile ) . '</i></pre>';

	print '<pre>$dirobj->get_lastmod_unixts( $pathtofile )' . "\n\n";
	print 'file was last modified on : <i>' . $dirobj->get_lastmod_unixts( $pathtofile ) . '</i></pre>';

	print '<pre>$dirobj->get_human_readable_lastmod( $pathtofile )' . "\n\n";
	print 'file was last modified on : <i>' . $dirobj->get_human_readable_lastmod( $pathtofile ) . '</i></pre>';

	print '<pre>$dirobj->get_lastacc_unixts( $pathtofile )' . "\n\n";
	print 'file was last accessed on : <i>' . $dirobj->get_lastacc_unixts( $pathtofile ) . '</i></pre>';

	print '<pre>$dirobj->get_human_readable_lastacc( $pathtofile, \'l j F Y \a\t g:i a\' )' . "\n\n";
	print 'file was last accessed on : <i>' . $dirobj->get_human_readable_lastacc( $pathtofile, 'l j F Y \a\t g:i a' ) . '</i></pre>';

	print '<pre>$dirobj->get_human_readable_file_permissions( $pathtofile )' . "\n\n";
	print 'file permissions are : <i>' . $dirobj->get_human_readable_file_permissions( $pathtofile ) . '</i></pre>';

	print '<pre>$dirobj->get_file_owner( $pathtofile )' . "\n\n";
	print 'file owner is : <i>' . $dirobj->get_file_owner( $pathtofile ) . '</i></pre>';




	print '</div></body></html>';

?>