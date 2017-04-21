#!/usr/bin/php

<?php

require_once 'patterns.class.php';

$p = new PatternBook();

$p->base_dir = "../";
$p->exclude_dir = array('images', 'utilities', 'templates', 'deleted', 'pathways', 'notes');

//$p->sections = $sections;
//$p->groups = $groups;
//$p->patterns = $patterns;

$p->log_messages = true;
$p->verbose = false;

$p->dry_run = false;

if ($_SERVER['argc'] != 3)
{
   echo "Usage: rename.php \"old name\" \"new name\"\n";
   exit;
}

$old_name = $_SERVER['argv'][1];
$new_name = $_SERVER['argv'][2];

$p->rename_pattern($old_name, $new_name);

exit;

// **********************************************************************
// End Script

?>


