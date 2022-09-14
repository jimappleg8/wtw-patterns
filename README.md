# WTW Patterns — Pattern Scripts User Manual

<p>This is a set of scripts that are designed to help build the pattern language book that I am working on. The scripts are designed to manage the most challenging aspect of writing a book like this: the numbering system.</p>


<h2>Getting a copy of the book</h2>

<p>I have decided to store the book files in a Github repository as a way of simplifying the management of the files. Before I did this, I was always having to make sure that I had the most recent copy of the book in hand before I made changes, and there were often multiple copies that it was difficult to keep track of.</p>

<p>To check out a copy of the book, clone this repository.</p>

<p>Once you have a working copy, you can make type edits and run <samp>pattern.php</samp> and other scripts to update the overall structure of the book. The scripts are designed to work with Subversion, so they will add, move, and delete files using Subversion commands. The instructions below indicate how to add, delete and rename patterns, groups and sections so that the book will retain its numeric integrity.</p>


<h2>Adding a new pattern</h2>

<p>Adding a new pattern is simple. You just add a record to the patterns array in the <samp>patterns.php</samp> file. A record looks like this</p>

<pre>
$patterns[] = array('name'      => 'First Things First',
                    'certainty' => '',
                    'group'     => 'planning');
</pre>

<p>The elements are pretty self-explanatory. The <var>name</var> is the official pattern name as you would like it to appear everywhere. This text is used everywhere to create links and the table of contents. The <var>certainty</var> is an indicator of how confident that I am that the pattern is valid. No asterisks indicates little confidence, one asterisk indicates a fair amount of confidence, and two asterisks indicate a strong confidence. And finally, the <var>group</var> indicates the group name this pattern is part of.</p>

<p>Once you add the record, running the <samp>patterns.php</samp> script will automatically generate an HTML file to which you can add all the text that you want.</p>


<!-- ********************************************************** -->

<h2>Removing a pattern</h2>

<p>Removing a pattern is also easy. You just delete it's record from the patterns array. The <samp>patterns.php</samp> script will detect that the existing pattern file is no longer listed in the patterns array and will move the file into the <samp>/deleted/</samp> folder. That way the contents of the file will not be lost in case you want to add it again later or use the text in another pattern.</p>

<p class="important"><strong>NOTE:</strong> The script does not remove any references to the pattern that was removed. You will need to look at the warnings presented in the summary to find the patterns that have links to the deleted pattern and edit those manually.</p>


<!-- ********************************************************** -->

<h2>Renaming a pattern</h2>

<p>There is a renaming script in the works, but for now, renaming a pattern must be done manually. The main patterns script will only work correctly if the pattern names are consistent across the book, so it is important that the renaming be done correctly.</p>

<p>There are three places the name must be changed:</p>

<ol>
<li><strong>The <samp>patterns.php</samp> file</strong> - This file contains the main structure of the book in the form of a PHP array. It is in this file that you add new patterns, delete obsolete patterns, and if you want to rename a pattern, you must change it here.</li>
<li><strong>The pattern filename</strong> - The patterns script automatically generates filenames based on the name of the pattern. If you want to rename the pattern, the file must also be renamed or it will not be properly recognized by the script.</li>
<li><strong>Links in the pattern files</strong> - any links that were created to point to the pattern that is being renamed must be renamed as well or they will be ignored. This can be done fairly easily with a site-wide search and replace using BBEdit. You should only need to change the pattern name (the linked text) and not the URL, though if you go ahead and change the URL as well, you won't need to rerun the <samp>patterns.php</samp> script to have a book with valid links.</li>
</ol>


<!-- ********************************************************** -->

<h2>Renaming a pattern group</h2>

<p>To rename a pattern group you simply change the name in the <var>groups</var> array in <samp>patterns.php</samp> and it will be created correctly when you run the main script.</p>

<pre>
$groups[] =   array('name' => 'reduce-to-practice',  <span style="color:#F00;">&lt;-- CHANGE HERE</span>
                    'section' => '2',
                    'desc' => 'build your idea in the real world so you can find out if it actually works;');
</pre>

<p>You must also change the group name in all the entries in the <var>patterns</var> array that are in that group. Otherwise, you will get errors indicating that the specified group was not found.</p>

<pre>
$patterns[] = array('name'      => 'Prototyped Capabilities',
                    'certainty' => '',
                    'group'     => 'reduce-to-practice');  <span style="color:#F00;">&lt;-- CHANGE HERE AS WELL</span>
</pre>

<p class="important"><strong>NOTE:</strong> After the <samp>patterns.php</samp> script runs, it will appear that the old directory was not deleted properly, but it will be deleted on the next commit by Subversion. Do not delete it manually.</p>


<!-- ********************************************************** -->

<h2>Creating an internal link</h2>

<p>To create a link to another page, you need to know the name of the pattern. The name is used to identify which pattern you are linking to. Since the URL and the pattern number may change, you can leave those out of the link you create; they will be ignored anyway. Just write the link according to this pattern and the script will recognize it as an internal link and fill in the appropriate URL and pattern number.</p>

<pre>
&lt;a href="" class="pattern-name"&gt;Everyone Lifted Up ()&lt;/a&gt;
</pre>


<!-- ********************************************************** -->

<h2>Running the patterns script</h2>

<p>This script is the heart of the system and deals with most of the general maintenance of the book.</p>

<pre>
cd utilities/
php ./patterns.php
</pre>

<p>In some cases, you may need to specify which PHP application should be used. I have this issue on my work computer, for instance, where the PHP used by MAMP overrides the one installed on my Mac.</p>

<pre>
cd utilities/
/Applications/MAMP/bin/php/php5.6.40/bin/php ./patterns.php
</pre>

<p>After running the script, you will get a summary of what was done. Those results may show a list of warnings that you should note. Specifically, it will tell you if there are any pattern links that don't match up with an existing pattern, either because the pattern has been deleted or because it hasn't been created yet.</p>

<p class="important"><strong>NOTE:</strong> You can run patterns.php without it actually making any changes to the book by changing the value of the <var>$dry_run</var> variable in the patterns.php file to TRUE.</p>


<!-- ********************************************************** -->

<h2>About pattern templates</h2>

<p>When a new pattern is created, a new file is generated using the <samp>pattern.tpl</samp> template found in the <samp>/templates/</samp> folder. Once the file is generated, however, the template is no longer used. If you want to change the look of the book, then you will need to search and replace sections of all the pages to change the HTML. Make sure you also change the <samp>pattern.tpl</samp> file so that new patterns will have the new look.</p>

<p>The section listings and the main Table of Contents are all generated from scratch each time you run the <samp>patterns.php</samp> script, so you can change the look of those pages by modifying the template and running the script.</p>


