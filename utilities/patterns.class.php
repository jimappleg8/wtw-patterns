<?php

// To find incomplete items, search for TODO:

//
// This class is designed to work with Git as changes are made so that the repository
// maintenance isn't too crazy when the structure of the book changes significantly.
//
//

class PatternBook
{
   var $base_dir = "./";
   var $exclude_dir;
   
   var $old_filelist;
   var $old_dirlist;
   
   var $sections;
   var $groups;
   var $groups_rev;
   var $patterns;
   
   var $created_notes = array();
   var $deleted = array();
   var $deleted_notes = array();
   var $changed = 0;
   var $changed_notes = array();
   var $links_updated = 0;
   var $links_notes = array();
   var $warnings = array();
   
   var $log_messages = false;
   var $verbose = false;
   
   var $dry_run = false;
   
   // --------------------------------------------------------------------

   /**
    * Class constructor
    */
   function PatternBook()
   {
   }
   // END PatternBook()

   // --------------------------------------------------------------------

   /**
    * Runs all functions required to reconcile the book files with the 
    * supplied data structure.
    */
   function process_pattern_book()
   {
      $this->get_old_filelist();
      $this->get_old_dirlist();
      $this->build_directories();
      $this->build_groups_rev();
      $this->process_patterns();
      $this->update_pattern_links();
      $this->create_tocs();
      $this->display_summary();
   }
   // END process_pattern_book()

   // --------------------------------------------------------------------

   /**
    * Renames a pattern in all places the name occurs.
    */
   function rename_pattern($old_name, $new_name)
   {
      // process old and new names to get their base names
      $old_basename = $this->_build_basename($old_name);
      $new_basename = $this->_build_basename($new_name);
      
      // get list of existing pattern files and get full old name
      $this->get_old_filelist();
      $old_filename = $this->old_filelist[$old_basename];
      
      // assemble the new full filename
      $path = str_replace($this->base_dir, '', $old_filename);
      $path_array = explode ("/", $path);
      $prefix = substr($path_array[2], 0, 4);
      $new_filename = $this->base_dir.$path_array[0]."/". 
                      $path_array[1]."/".$prefix.$new_basename.".html";

      echo "old filename: ".$old_filename."\n";
      echo "new filename: ".$new_filename."\n";

      // change the filename to the new name
//      if ( ! $this->dry_run) exec('svn rename '.$old_filename.' '.$new_filename);
      if ( ! $this->dry_run) exec('git mv '.$old_filename.' '.$new_filename);
      $this->_log('Renamed file: '.$old_filename.' --> '.$new_filename);
      
      // get a list of all filenames, including the patterns.php 
      // file with the data structure defined.
      
      // change all references to page (and links) in all files
      // search for the string and filename separately I think.
      
   }
   // END rename_pattern()

   // --------------------------------------------------------------------

   /**
    * Loads all existing pattern files into a reverse-lookup array.
    */
   function get_old_filelist()
   {
      require_once 'directory-info/directoryinfo.inc.php';

      $dirobj = new directory_info( );
   
      $dirobj->get_ext_based_filelist( null, $this->base_dir, true, array('html') );

      for ($i=0; $i<count($dirobj->filelist_selection); $i++)
      {
         $path_array = explode("/", $dirobj->filelist_selection[$i]);
         if ( ! in_array($path_array[0], $this->exclude_dir) && $path_array[2] != "" && $path_array[2] != "index.html")
         {
            $basename = str_replace(".html","", substr($path_array[2], 4));
            $this->old_filelist[$basename] = $this->base_dir . $dirobj->filelist_selection[$i];
         }
      }
//      print_r($this->old_filelist);
      return $this->old_filelist;
   }
   // END get_old_filelist()

   // --------------------------------------------------------------------

   /**
    * Loads all existing directories into a reverse-lookup array.
    */
   function get_old_dirlist()
   {
      require_once 'directory-info/directoryinfo.inc.php';

      $dirobj = new directory_info( );
   
      $dirobj->get_dir_list($this->base_dir, true);

      for ($i=0; $i<count($dirobj->dirlist); $i++)
      {
         $path_array = explode("/", $dirobj->dirlist[$i]);
         if ( ! in_array($path_array[0], $this->exclude_dir))
         {
            $this->old_dirlist[$dirobj->dirlist[$i]] = $dirobj->dirlist[$i];
         }
      }
//      print_r($this->old_dirlist);
      return $this->old_dirlist;
   }
   // END get_old_dirlist()

   // --------------------------------------------------------------------

   /**
    * Check if group and section directories exist and create if needed.
    */
   function build_directories()
   {
      for ($i=0; $i<count($this->sections); $i++)
      {
         $section_dir = $this->base_dir . $i . "-" . $this->sections[$i]['name'] . "/";
         $this->sections[$i]['dir'] = $section_dir;
         $this->sections[$i]['title'] = $this->_build_title($this->sections[$i]['name']);
         if ( ! file_exists($section_dir))
         {
//            if ( ! $this->dry_run) exec('svn mkdir '.$section_dir);
            if ( ! $this->dry_run) mkdir($section_dir);
            $this->created_notes[] = "The section directory ".$section_dir." was created.";
         }
         else
         {
            unset($this->old_dirlist[substr($section_dir, 3)]);
         }
      }
      for ($i=0; $i<count($this->groups); $i++)
      {
         $formatted = str_pad($i, 2, 0, STR_PAD_LEFT);
         $group_dir = $this->sections[$this->groups[$i]['section']]['dir'] . 
                $formatted . "-" . $this->groups[$i]['name'] ."/";
         $this->groups[$i]['dir'] = $group_dir;
         if ( ! file_exists($group_dir))
         {
//            if ( ! $this->dry_run) exec('svn mkdir '.$group_dir);
            if ( ! $this->dry_run) mkdir($group_dir);
            $this->created_notes[] = "The group directory ".$group_dir." was created.";
         }
         else
         {
            unset($this->old_dirlist[substr($group_dir, 3)]);
         }
      }

      return true;
   }
   // END build_directories()

   // --------------------------------------------------------------------

   /**
    * Builds a groups reverse lookup array
    */
   function build_groups_rev()
   {
      for ($i=0; $i<count($this->groups); $i++)
      {
         $name = $this->groups[$i]['name'];
         $this->groups_rev[$name] = $this->groups[$i];
         $this->groups_rev[$name]['id'] = $i;
         $this->groups_rev[$name]['title'] = $this->_build_title($name);
         $this->groups[$i]['title'] = $this->_build_title($name);
      }
//      print_r($this->groups_rev);
      return $this->groups_rev;
   }
   // END build_groups_rev()

   // --------------------------------------------------------------------

   /**
    *
    */
   function process_patterns()
   {
      for ($i=0; $i<count($this->patterns); $i++)
      {
         $formatted = str_pad($i+1, 3, 0, STR_PAD_LEFT);
         $basename = $this->_build_basename($this->patterns[$i]['name']);
         
         // check that the specified group exists and set path
         if ( ! empty($this->groups_rev[$this->patterns[$i]['group']]))
         {
            $new_path = $this->groups_rev[$this->patterns[$i]['group']]['dir'] .
                        $formatted . "-" . $basename . ".html";
         }
         else
         {
            $new_path = $this->base_dir . "deleted/" . 
                        $formatted . "-" . $basename . ".html";
            $this->warnings[] = "WARNING: ".$formatted . "-" . $basename . ".html was moved to deleted folder: group doesn't exist.";
         }

         $this->patterns[$i]['path'] = $new_path;
         $this->patterns[$i]['id'] = $i + 1;
         
         // rename/move file if changed
         if (isset($this->old_filelist[$basename]))
         {
            if ($new_path != $this->old_filelist[$basename])
            {
//               if ( ! $this->dry_run) rename($this->old_filelist[$basename], $new_path);
//               if ( ! $this->dry_run) exec('svn rename '.$this->old_filelist[$basename].' '.$new_path);
               if ( ! $this->dry_run) exec('git mv '.$this->old_filelist[$basename].' '.$new_path);
               $this->changed++;
               $this->changed_notes[] = "The file ".$this->old_filelist[$basename]." was moved to ".$new_path.".";
               
            } // else leave it alone - it hasn't changed

            unset($this->old_filelist[$basename]);

         } else {
            // create any new patterns
            $this->create_pattern($i);
            $this->created_notes[] = "The new pattern ".$this->patterns[$i]['name']." (".($i+1).") was created.";
         }
      }
//      print_r($this->patterns);

      // move any deleted files into a "deleted" folder
      foreach ($this->old_filelist as $key => $file)
      {
         // move file to "deleted folder.
         $new_path = $this->base_dir . "deleted/".$key.".html";
         $num = 0;
         while (file_exists($new_path))
         {
            $num++;
            $new_path = $this->base_dir . "deleted/".$key."-".$num.".html";
         }
//         if ( ! $this->dry_run) rename($file, $new_path);
//         if ( ! $this->dry_run) exec('svn rename '.$file.' '.$new_path);
         if ( ! $this->dry_run) exec('git mv '.$file.' '.$new_path);
         $this->warnings[] = "WARNING: ".$file." was deleted, but there may still be links to the file.";
         $this->deleted_notes[] = "The file ".$file." was moved to the deleted folder:".$new_path;
      }

      // delete any directories where the order or name changed.
      // delete group directories first
      // NOTE: it would probably be better if we MOVED these directories rather than
      //    deleting them so Subversion can track their history.
      foreach ($this->old_dirlist as $dir)
      {
         $path_array = explode("/", $dir);
         if ($path_array[1] != "")
         {
//            if ( ! $this->dry_run) exec('svn delete ' . $this->base_dir . $dir);
            if ( ! $this->dry_run) exec('git rm ' . $this->base_dir . $dir);
            $this->deleted_notes[] = "The directory ".$this->base_dir . $dir." was removed. Note: it will not go away until the changes are committed to the repository.";
            unset($this->old_dirlist[$dir]);
         }
      }
      // then delete the remaining section directories
      foreach ($this->old_dirlist as $dir)
      {
         // move index.php to deleted folder
         $index = $this->base_dir . $dir . "index.php";
         $new_index = $this->base_dir . "deleted/" . rtrim($dir, "/") . "-index.php";
         if (file_exists($index))
         {
//            if ( ! $this->dry_run) rename($index, $new_index);
//            if ( ! $this->dry_run) exec('svn move '.$index.' '.$new_index);
            if ( ! $this->dry_run) exec('git mv '.$index.' '.$new_index);
            $this->warnings[] = "Moved ".$index." to deleted folder: ".$new_index;
            $this->deleted_notes[] = "The file ".$index." was moved to the deleted folder and renamed it ".$new_index.".";
         }
//         if ( ! $this->dry_run) exec('svn delete ' . $this->base_dir . $dir);
         if ( ! $this->dry_run) exec('git rm ' . $this->base_dir . $dir);
         $this->deleted_notes[] = "The directory ".$this->base_dir . $dir." was removed. Note: it will not go away until the changes are committed to the repository.";
      }

      return true;
   }
   // END process_patterns

   // --------------------------------------------------------------------

   /**
    * Searches for all pattern links in all files and updates them. It
    *   looks for pattern links that do not have a matching pattern and
    *   reports them as warnings.
    */
   function update_pattern_links()
   {
      $files = $this->patterns;
      
      for ($i=0; $i<count($files); $i++)
      {
         if (file_exists($files[$i]['path']))
         {
            $subject = file_get_contents($files[$i]['path']);
   
            // replace number in the main heading
            $search = '/<span class="num">(.*)<\/span> (.*)\**<\/h1>/U';
            $replace = '<span class="num">'.$files[$i]['id'].'</span> '. 
                       $files[$i]['name'].$files[$i]['certainty'].'</h1>';
            $subject = $this->search_file($files[$i]['path'], $subject, $search, $replace, "heading");

            // replace number & group in the menu
            $search =  '#<nav aria-label="breadcrumb">\s*'.
                       '<ol class="breadcrumb">\s*'.
                       '<li class="breadcrumb-item"><a href="\.\./\.\./index.html">Cover Page</a></li>\s*'.
                       '<li class="breadcrumb-item"><a href="\.\./\.\./patterns.html">Patterns</a></li>\s*'.
                       '<li class="breadcrumb-item"><a href="\.\./index.html">(.*)</a></li>\s*'.
                       '<li class="breadcrumb-item"><a href="index.html">(.*)</a></li>\s*'.
                       '<li class="breadcrumb-item active" aria-current="page">(.*)\((.*)\)</li>\s*'.
                       '</ol>\s*'.
                       '</nav>#Us';                      
            $replace = '<nav aria-label="breadcrumb">'."\n".
                       '  <ol class="breadcrumb">'."\n".
                       '    <li class="breadcrumb-item"><a href="../../index.html">Cover Page</a></li>'."\n".
                       '    <li class="breadcrumb-item"><a href="../../patterns.html">Patterns</a></li>'."\n".
                       '    <li class="breadcrumb-item"><a href="../index.html">' . $this->sections[$this->groups_rev[$files[$i]['group']]['section']]['title'] . '</a></li>'."\n".
                       '    <li class="breadcrumb-item"><a href="index.html">' . $this->groups_rev[$files[$i]['group']]['title'] . '</a></li>'."\n".
                       '    <li class="breadcrumb-item active" aria-current="page">$3(' . $files[$i]['id'] . ')</li>'."\n".
                       '  </ol>'."\n".
                       '</nav>'."\n";
            $subject = $this->search_file($files[$i]['path'], $subject, $search, $replace, "menu");
            
            // replace previous and next links
            if (isset($files[$i-1])) {
              $previous = '    <a class="btn btn-outline-dark btn-sm float-left" href="'.$files[$i-1]['path'].'">&lt; '.$files[$i-1]['name'].' (' . $files[$i-1]['id'] . ')</a>';
            }
            else
            {
              $previous = '    ';
            }
            if (isset($files[$i+1])) {
              $next = '    <a class="btn btn-outline-dark btn-sm float-right" href="'.$files[$i+1]['path'].'">&lt; '.$files[$i+1]['name'].' (' . $files[$i+1]['id'] . ')</a>';
            }
            else
            {
              $next = '    ';
            }
            $search =  '#<nav class="pager">\s*'.
                       '<div>\s*'.
                       '<a class="btn btn-outline-dark btn-sm float-left" href="(.*)">(.*)</a>\s*'.
                       '<a class="btn btn-outline-dark btn-sm float-right" href="(.*)">(.*)</a>\s*'.
                       '</div>\s*'.
                       '</nav>#Us';                      
            $replace = '<nav class="pager">'."\n".
                       '  <div>'."\n".
                       $previous."\n".
                       $next."\n".
                       '  </div>'."\n".
                       '</nav>'."\n";
            $subject = $this->search_file($files[$i]['path'], $subject, $search, $replace, "pager");
            
            // update pattern links in pattern files

            // first, get a list of all pattern links in the file
            $search = '/<a href="([a-zA-Z0-9\/\.\-]*)" class="pattern-name">'. 
                      '(.*) \((.*)\)<\/a>/U';
            preg_match_all($search, $subject, $matches, PREG_PATTERN_ORDER);

            foreach ($matches[2] AS $pname)
            {
               $found_match = FALSE;
               for ($j=0; $j<count($this->patterns); $j++)
               {
                  if ($pname == $this->patterns[$j]['name'])
                  {
                     $found_match = TRUE;
                     $search = '/<a href="([a-zA-Z0-9\/\.\-]*)" '.
                               'class="pattern-name">'. 
                               $this->patterns[$j]['name'].
                               ' \((.*)\)<\/a>/U';
                     $replace = '<a href="../'.
                                $this->patterns[$j]['path']. 
                                '" class="pattern-name">'.
                                $this->patterns[$j]['name'].' (' . 
                                $this->patterns[$j]['id'] . ')</a>';
                     $subject = $this->search_file($files[$i]['path'], $subject, $search, $replace);
                  }
               }
               if ( ! $found_match)
               {
                  $this->warnings[] = 'The pattern link "'.$pname.'" does not match an existing pattern'."\n".'      (in '.$files[$i]['path'].').';
               }
               else
               {
                  $this->links_notes[] = 'The pattern link "'.$pname.'" was updated'."\n".'      (in '.$files[$i]['path'].').';
               }
            }
            if ( ! $this->dry_run) $this->write_file($files[$i]['path'], $subject);
         }
         else
         {
            $this->warnings[] = "update_pattern_links(): file ".$files[$i]['path']." does not exist.";
         }
      }

      // not sure how to check links in the other files
      // use file search and exclude patterns?
      $dirobj = new directory_info( );
      
      $others = array();
      // check each section for index.php
      foreach ($this->sections as $section)
      {
         $pathtoindex = $section['dir'] . "index.php";
         if (file_exists($pathtoindex))
         {
            $others[] = $pathtoindex;
         }
      }
      // get all other files at the root directory (front matter)
      // this will also update the TOC that will later get overwritten
      $some = $dirobj->get_ext_based_filelist( null, $this->base_dir, false, array('html') );
      for ($i=0; $i<count($some); $i++)
      {
         $some[$i] = '../'.$some[$i];
      }
      $others = array_merge($others, $some);

      for ($i=0; $i<count($others); $i++)
      {
         $subject = file_get_contents($others[$i]);

         // update pattern links

         // first, get a list of all pattern links in the file
         $search = '/<a href="[a-zA-Z0-9\/\.\-]*" class="pattern-name">'. 
                   '(.*) \(.*\)<\/a>/U';
         preg_match_all($search, $subject, $matches, PREG_PATTERN_ORDER);

         foreach ($matches[1] AS $pname)
         {
            $found_match = FALSE;
            for ($j=0; $j<count($this->patterns); $j++)
            {
               if ($pname == $this->patterns[$j]['name'])
               {
                  $found_match = TRUE;
                  $search = '/<a href="([a-zA-Z0-9\/\.\-]*)" '.
                            'class="pattern-name">'. 
                            $this->patterns[$j]['name'].
                            ' \((.*)\)<\/a>/U';
                  $replace = '<a href="../'.
                             $this->patterns[$j]['path']. 
                             '" class="pattern-name">'.
                             $this->patterns[$j]['name'].' (' . 
                             $this->patterns[$j]['id'] . ')</a>';
                  $subject = $this->search_file($others[$i], $subject, $search, $replace);
               }
            }
            if ( ! $found_match)
            {
               $this->warnings[] = 'The pattern link "'.$pname.'" does not match an existing pattern'."\n".'      (in '.$others[$i].').';
            }
            else
            {
               $this->links_notes[] = 'The pattern link "'.$pname.'" was updated'."\n".'      (in '.$others[$i].').';
            }
         }
         if ( ! $this->dry_run) $this->write_file($others[$i], $subject);
      }

      return true;
   }
   // END update_pattern_links()
   
   // --------------------------------------------------------------------

   /**
    * Searches the given file
    */
   function search_file($file, $subject, $search, $replace, $search_name = '')
   {
//      echo "file: ".$file."\n";
//      echo "subject: ".$subject."\n";
//      echo "search: ".$search."\n";
//      echo "replace: ".$replace."\n\n";
      preg_match_all($search, $subject, $matches, PREG_PATTERN_ORDER);
      
      if ($search_name == "")
      {
         $found = count($matches[0]);
         if ($found > 0) 
         {
            $subject = preg_replace($search, $replace, $subject);
            $this->links_updated = $this->links_updated + $found;
         }
      }
      else
      {
         $found = count($matches);
         if ($found > 0) 
         {
            $subject = preg_replace($search, $replace, $subject);
         }
         else
         {
            $this->warnings[] = "search_file(): no matches found for search \"".$search_name."\" in file ".$file;         
         }
      }
      return $subject;
   }
   // END search_file()
   
   // --------------------------------------------------------------------

   function write_file($file, $data)
   {           
      if (is_writable($file))
      {
         $fp = fopen($file, "w");
         fwrite($fp, $data);
         fclose($fp);	
      }
      else
      {
         echo "Can not replace text. File $file is not writable. \nPlease make it writable\n";	
      }
   }
   // END write_file

   // --------------------------------------------------------------------

   /**
    * Creates pattern file from a template if it doesn't exist.
    */
   function create_pattern($id)
   {
      $tpl = $this->base_dir . "templates/pattern.tpl";
      $file = $this->patterns[$id]['path'];

      $section = $this->sections[$this->groups_rev[$this->patterns[$id]['group']]['section']]['title'];
      $section_url = "../index.html";
      $group = $this->groups_rev[$this->patterns[$id]['group']]['title'];
      $group_url = "index.html";
      $title = $this->patterns[$id]['name'];
      $certainty = $this->patterns[$id]['certainty'];
      $number = $id + 1;
      
      // read in template file
      $fh = fopen($tpl, 'r') or die($php_errormsg);
      $template = fread($fh, filesize($tpl));
      fclose($fh) or die($php_errormsg);
      
      $template = str_replace('{section}', $section, $template);
      $template = str_replace('{section_url}', $section_url, $template);
      $template = str_replace('{group}', $group, $template);
      $template = str_replace('{group_url}', $group_url, $template);
      $template = str_replace('{title}', $title, $template);
      $template = str_replace('{certainty}', $certainty, $template);
      $template = str_replace('{number}', $number, $template);

      if ( ! $this->dry_run) 
      {
         $fh = fopen($file, 'w') or die($php_errormsg);
         fwrite($fh, $template);
         fclose($fh) or die($php_errormsg);
//         exec('svn add ' . $file);
         exec('git add ' . $file);
      }
      
      $this->created_notes[] = "The pattern file ".$file." was created.";
      
      return true;
   }
   // END create_pattern()

   // --------------------------------------------------------------------

   /**
    * Generates the Table of Contents file and section and group TOCs.
    */
   function create_tocs()
   {
      // read in template file
      $toc_tpl = file_get_contents($this->base_dir."templates/patterns.tpl");
      $toc_index_tpl = file_get_contents($this->base_dir."templates/patterns-index.tpl");
      
      // create main TOC
      $main_toc = $toc_tpl;
      $main_txt = "";
      
      for ($i=0; $i<count($this->sections); $i++)
      {
         // append section description to $main_toc
         if ($this->sections[$i]['desc'] != "")
         {
            $main_txt .= "<p class=\"section\">".$this->sections[$i]['desc']."</p>\n\n";
         }
         
         // create section TOC
         $section_toc = $toc_index_tpl;
         // append section description to section toc
         $section_txt = "<h1>".$this->sections[$i]['title']."</h1>\n\n";
         if ($this->sections[$i]['desc'] != "")
         {
            $section_txt .= "<p class=\"section\">".$this->sections[$i]['desc']."</p>\n\n";
         }
         if (file_exists($this->sections[$i]['dir']."index.php"))
         {
            $section_txt .= file_get_contents($this->sections[$i]['dir']."index.php");
         }
         $masthead = "<div id=\"masthead\">\n".
                     "<span> World That Works: A Pattern Language</span> &mdash; ".
                     "<span style=\"color:red; font-weight:normal;\">Working DRAFT</span>\n".
                     "<br /><a href=\"../index.html\">Cover Page</a> ". 
                     "&nbsp;&#8250;&nbsp; ". 
                     "<a href=\"../patterns.html\">Patterns</a> ". 
                     "&nbsp;&#8250;&nbsp; ". 
                     $this->sections[$i]['title']."\n</div>";
         $section_toc = str_replace('{style}', "../patterns.css", $section_toc);
         $section_toc = str_replace('{masthead}', $masthead, $section_toc);
         $section_toc = str_replace('{text}', $section_txt, $section_toc);
         if ( ! $this->dry_run) 
         {
            $fh = fopen($this->sections[$i]['dir']."index.html", 'w') or die($php_errormsg);
            fwrite($fh, $section_toc);
            fclose($fh) or die($php_errormsg);
         }

         for ($j=0; $j<count($this->groups); $j++)
         {
            if ($this->groups[$j]['section'] == $i)
            {
               // append group description to $main_toc
               $main_txt .= "<p class=\"group\">(".$this->groups[$j]['name'].") ".$this->groups[$j]['desc']."</p>\n\n";
         
               // create group TOC
               $group_toc = $toc_index_tpl;
               $group_txt = "<p class=\"group\">".$this->groups[$j]['desc']."</p>\n\n";
            
               $main_txt .= "<ul>\n\n";
               $group_txt .= "<ul>\n\n";
               for ($k=0; $k<count($this->patterns); $k++)
               {
                  if ($this->patterns[$k]['group'] == $this->groups[$j]['name'])
                  {
                     $main_txt .= "<li><span class=\"pattern-name\">".
                                  "<a href=\"".
                                  ltrim($this->patterns[$k]['path'], "./") .
                                  "\">" . $this->patterns[$k]['id'] . " &nbsp;" .
                                  $this->patterns[$k]['name'] .
                                  "</a></span></li>\n";
                     $group_txt .= "<li><span class=\"pattern-name\">".
                                   "<a href=\"../../".
                                   ltrim($this->patterns[$k]['path'], "./") .
                                   "\">" . $this->patterns[$k]['id'] . " &nbsp;" .
                                   $this->patterns[$k]['name'] .
                                   "</a></span></li>\n";
                  }
               }
               $main_txt .= "</ul>\n";
               $group_txt .= "</ul>\n";

               $masthead = "<div id=\"masthead\">" .
                           "<a href=\"../../index.html\">World That Works</a> " . 
                           "&nbsp;&#8250;&nbsp; <a href=\"../../patterns.html\">Patterns</a> " . 
                           "&nbsp;&#8250;&nbsp; <a href=\"../index.html\">" . 
                           $this->sections[$i]['title'] .
                           "</a> &nbsp;&#8250;&nbsp; " . 
                           $this->groups[$j]['title'] .
                           "</div>";
            
               $group_toc = str_replace('{style}', "../../patterns.css", $group_toc);
               $group_toc = str_replace('{masthead}', $masthead, $group_toc);
               $group_toc = str_replace('{text}', $group_txt, $group_toc);

               if ( ! $this->dry_run) 
               {
                  $exists = file_exists($this->groups[$j]['dir']."index.html");
                  $fh = fopen($this->groups[$j]['dir']."index.html", 'w') or die($php_errormsg);
                  fwrite($fh, $group_toc);
                  fclose($fh) or die($php_errormsg);
                  if ( ! $exists ) {
//                     exec('svn add '.$this->groups[$j]['dir']."index.html");
                     exec('git add '.$this->groups[$j]['dir']."index.html");
                  }
               }
            }
         }
      }

      $main_toc = str_replace('{text}', $main_txt, $main_toc);

      if ( ! $this->dry_run) 
      {
         $fh = fopen($this->base_dir."patterns.html", 'w') or die($php_errormsg);
         fwrite($fh, $main_toc);
         fclose($fh) or die($php_errormsg);
      }

      return true;
   }
   // END create_tocs()

   // --------------------------------------------------------------------

   /**
    * Displays a summary of changes.
    */
   function display_summary()
   {      
      $this->_log("\n===========================================", true);
      $this->_log(" SUMMARY", true);
      if ($this->dry_run)
      {
         $this->_log("===========================================", true);
         $this->_log(" The script is in DRY RUN mode.", true);
         $this->_log(" These changes were not saved.", true);
      }
      $this->_log("===========================================", true);
      if ( ! empty($this->changed_notes))
      {
         $this->_log(" Changed files or folders:", true);
         foreach($this->changed_notes as $changed)
         {
            $this->_log("   ".$changed, true);
         }
      }
      else
      {
         $this->_log(" No files or folders were changed.", true);
      }
      $this->_log("-------------------------------------------", true);
      if ( ! empty($this->created_notes))
      {
         $this->_log(" Created files or folders:", true);
         foreach($this->created_notes as $created)
         {
            $this->_log("   ".$created, true);
         }
      }
      else
      {
         $this->_log(" No files or folders were created.", true);
      }
      $this->_log("-------------------------------------------", true);
      if ( ! empty($this->deleted_notes))
      {
         $this->_log(" Deleted files or folders:", true);
         foreach($this->deleted_notes as $deleted)
         {
            $this->_log("   ".$deleted, true);
         }
      }
      else
      {
         $this->_log(" No files or folders were deleted.", true);
      }
      $this->_log("-------------------------------------------", true);
      $this->_log(" ".$this->links_updated." links were updated.", true);
      if ( ! empty($this->links_notes))
      {
         $this->_log(" Links Notes:", false);
         foreach($this->links_notes as $notes)
         {
            $this->_log("   ".$notes, false);
         }
      }
      $this->_log("-------------------------------------------", true);
      if ( ! empty($this->warnings))
      {
         $this->_log(" Warnings:", true);
         foreach($this->warnings as $warnings)
         {
            $this->_log("   ".$warnings, true);
         }
      }
      else
      {
         $this->_log(" No warnings.", true);
      }
      $this->_log("===========================================\n\n", true);
      return true;
   }
   // END display_summary()

   // --------------------------------------------------------------------

   /**
    * Builds a basename from a pattern title.
    */
   function _build_basename($name)
   {
      $name = strtolower($name);
      $name = str_replace(" ", "-", $name);
      $name = str_replace("'", "", $name);
      return $name;
   }
   // END _build_basename()

   // --------------------------------------------------------------------

   /**
    * Builds a group or section title from the name.
    */
   function _build_title($name)
   {
      $name = str_replace("-", " ", $name);
      $name = ucwords($name);
      return $name;
   }
   // END _build_title()

   // --------------------------------------------------------------------

   /**
    * Write messages to log and/or screen
    */
   function _log($message, $display=false)
   {
      if ($this->log_messages)
      {
         $log_file = $this->base_dir . "log.txt";
         $fp = fopen($log_file, "a") or die($php_errormsg);
         fwrite($fp, $message."\n");
         fclose($fp);	
      }
      if ($this->verbose || $display == true)
      {
         echo $message."\n";
      }
   }
   // END _log()


} // PatternBook class

?>