<?php

class directory_info {

	var $pathtodir = './';
	var $safe_exts = true;
	var $exts = array( 'jpg', 'gif', 'jpeg', 'png' );
	var $mimetypes = array( 'image/jpeg', 'image/png', 'image/gif' );
	var $strict = false;
	var $datetime_format = 'Y-m-d H:i:s';
	var $byte_suffixes = array( 'b', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
	var $byte_suffix_count = 9;

	var $filelist = array();
	var $filelist_selection = array();
	var $dirlist = array();
	var $filecount = 0;
	var $fileselection_count = 0;
	var $dircount = 0;
	var $last_path;
	var $last_recursive;
	var $last_passed_exts;
	var $last_exts;
	var $last_passed_mimetypes;
	var $last_mimetypes;

	function set_default_path( $pathtodir ) {
		if( ( is_string( $pathtodir ) && $pathtodir !== '' ) && is_dir( $pathtodir ) ) {
			$this->pathtodir = $pathtodir;
			return true;
		}
		return false;
	}

	function set_safe_exts( $safe_exts ) {
		if( is_bool( $safe_exts ) ) {
			$this->safe_exts = $safe_exts;
			return true;
		}
		return false;
	}

	function set_default_exts( $exts ) {
		$exts = $this->validate_extension_list( $exts );
		if( $exts !== $this->exts ) {
			$this->exts = $exts;
			return true;
		}
		return false;

	}

	function set_default_mimetypes( $mimetypes ) {
		$mimetypes = validate_mime_types( $mimetypes );
		if( $mimetypes !== $this->mimetypes ) {
			$this->mimetypes = $mimetypes;
			return true;
		}
		return false;
	}

	function set_strict( $strict ) {
		if( is_bool( $strict ) ) {
			$this->strict = $strict;
			return true;
		}
		return false;
	}

	function set_datetime_format( $datetime_format ) {
		if( is_string( $datetime_format ) && $datetime_format !== '' ) {
			$this->datetime_format = $datetime_format;
			return true;
		}
		return false;
	}

	function validate_extension_list( $exts = null ) {

		if( $exts !== $this->last_passed_exts || is_null( $this->last_passed_exts ) ) {

			$this->last_passed_exts = $exts;

			// If it's a string, check for all, otherwise create an array containing 1 item
			if( is_string( $exts ) && $exts !== '' ) {
				if( strtolower( $exts ) === 'all' ) {
					$this->last_exts = strtolower( $exts );
				}
				else {
					$this->last_exts = ( array( strtolower( $exts ) ) );
				}
			}

			// If it's an array, make lowercase (which will cast the extension to string automatically)
			elseif( is_array( $exts ) && count( $exts ) > 0 ) {
				$ext_array = array();
				foreach( $exts as $ext ) {
					$ext_array[] = strtolower( $ext );
				}
				$this->last_exts = $ext_array;
			}

			// Otherwise return the default
			else {
				$this->last_exts = $this->exts;
			}
		}

		return $this->last_exts;
	}

	function validate_mime_types( $mimetypes = null ) {

		if( $mimetypes !== $this->last_passed_mimetypes || is_null( $this->last_passed_mimetypes ) ) {

			$this->last_passed_mimetypes = $mimetypes;

			if( is_string( $mimetypes ) && $mimetypes !== '' ) {
				// Cast to array and pass through
				$mimetypes = array( $mimetypes );
			}

			if( is_array( $mimetypes ) && count( $mimetypes ) > 0 ) {

				$mime_array = array();

				foreach( $mimetypes as $mimetype ) {

					if( is_string( $mimetype ) && $mimetype !== '' ) {
						if( strpos( $mimetype, '/') === false ) {
							if( isset( $this->valid_mime_types[$mimetype] ) ) {
								foreach( $this->valid_mime_types[$mimetype] as $subtype ) {
									$mime_array[] = $mimetype . '/' . $subtype;
								}
							}
						}
						else {
							$mimeparts = explode( '/', $mimetype, 2 );
							if( in_array( $mimeparts[1], $this->valid_mime_types[$mimeparts[0]], true) ) {
								$mime_array[] = $mimetype;
							}
						}
					}
				}

				if( count( $mime_array ) > 0 ) {
					$this->last_mimetypes = array_unique( $mime_array );
				}
			}
			else {
				$this->last_mimetypes = $this->mimetypes;
			}
		}

		return $this->last_mimetypes;
	}

	function valid_pathtofile( $pathtofile ) {
		clearstatcache();

		// Check if a non empty string has been passed as pathtofile
		return ( ( is_string( $pathtofile ) && $pathtofile !== '' ) && file_exists( $pathtofile ) );
	}

	function check_allowed_file( $pathtofile, $exts = null, $strict = null, $mimetypes = null ) {

		$strict = ( is_bool( $strict ) ) ? $strict : $this->strict;

		if( $strict ) {

			if( is_null( $mimetypes ) && !is_null( $exts ) ) {
				$exts = $this->validate_extension_list( $exts );

				if( is_string( $exts) && strtolower( $exts ) === 'all' ) {
					// Fall through - mimetype check superfluous
					return ( $this->check_file_extension( $pathtofile, $exts ) );
				}
				elseif( $exts === $this->exts && !is_null( $this->mimetypes ) ) {
					$mimetypes = $this->mimetypes;
				}
				else {
					$exts = $this->validate_extension_list( $exts );
					$mimetypes = array();
					foreach( $exts as $ext ) {
						if( isset( $this->mime_map[$ext] ) ) {
							$mimetypes[] = $this->mime_map[$ext];
						}
						else {
							trigger_error( 'The file extension <em>' . $ext . '</em> does not have a valid mime-type associated with it in the mime_map', E_USER_WARNING );
						}
					}
					$mimetypes = array_unique( $mimetypes );
				}
			}
			return ( $this->check_file_extension( $pathtofile, $exts ) && $this->check_file_mimetype( $pathtofile, $mimetypes ) );
		}
		else {
			return ( $this->check_file_extension( $pathtofile, $exts ) );
		}
	}

	function check_file_extension( $filename, $exts = null ) {

		// Check if a non empty string has been passed as filename
		if( !is_string( $filename ) || $filename === '' ) {
			return false;
		}

		// Validate the optional parameters and default to the class defaults if not passed or invalid
		$exts = $this->validate_extension_list( $exts );

		// If all extensions are allowed, return true
		if( $exts === 'all') {
			return true;
		}

		// If the function is still running, check the extension against the allowed extension list
		$pos = strrpos( $filename, '.' );
		if( $pos !== false ) {
			// Strip the everything before and including the '.'
			$file_ext = substr( $filename, ( $pos + 1 ) );
			return( in_array( $file_ext, $exts, true ) );
		}

		// No extension found
		return false;
	}

	function check_file_mimetype( $pathtofile, $mimetypes = null ) {
		if( !$this->valid_pathtofile( $pathtofile ) ) {
			return false;
		}
		$mimetypes = $this->validate_mime_types( $mimetypes );
		$file_mimetype = $this->get_mime_content_type( $pathtofile );
		return ( in_array( $file_mimetype, $mimetypes ) );
	}


	function get_filesize( $pathtofile ) {
		if( !directory_info::valid_pathtofile( $pathtofile ) ) {
			return false;
		}
		return filesize( $pathtofile );
	}

	function get_human_readable_filesize( $pathtofile ) {
		$filesize = $this->get_filesize( $pathtofile );
		return ( ( $filesize !== false ) ? $this->human_readable_filesize( $filesize ) : false );
	}


	function human_readable_filesize( $filesize ) {

		if( is_int( $filesize ) && $filesize > 0 ) {

			// Get the figure to use in the string
			for( $i = 0; ( $i < $this->byte_suffix_count && $filesize >= 1024 ); $i++ ) {
				$filesize = $filesize / 1024;
			}

			// Return the rounded figure with the appropriate suffix
			if( $this->byte_suffixes[$i] === 'b' || $this->byte_suffixes[$i] === 'kB' ) {
				return( round( $filesize, 0 ) . ' ' . $this->byte_suffixes[$i] );
			}
			else {
				return( round( $filesize, 1 ) . ' ' . $this->byte_suffixes[$i] );
			}
		}
		else {
			return false;
		}
	}


	function get_lastmod_unixts( $pathtofile ) {
		if( !directory_info::valid_pathtofile( $pathtofile ) ) {
			return false;
		}
		return filemtime( $pathtofile );
	}

	function get_human_readable_lastmod( $pathtofile, $datetime_format = null ) {
		if( !is_string( $datetime_format ) || $datetime_format === '' ) {
			$datetime_format = $this->datetime_format;
		}
		$uts = $this->get_lastmod_unixts( $pathtofile );
		return ( ( $datetime_format !== '' && $uts !== false ) ? date( $datetime_format, $uts ) : false );
	}


	function get_lastacc_unixts( $pathtofile ) {
		if( !directory_info::valid_pathtofile( $pathtofile ) ) {
			return false;
		}
		return fileatime( $pathtofile );
	}


	function get_human_readable_lastacc( $pathtofile, $datetime_format = null ) {
		if( !is_string( $datetime_format ) || $datetime_format === '' ) {
			$datetime_format = $this->datetime_format;
		}
		$uts = $this->get_lastacc_unixts( $pathtofile );
		return ( ( $datetime_format !== '' && $uts !== false ) ? date( $datetime_format, $uts ) : false );
	}


	function get_file_owner( $pathtofile ) {
		if( !directory_info::valid_pathtofile( $pathtofile ) ) {
			return false;
		}
		return fileowner( $pathtofile );
	}


	function get_mime_content_type( $pathtofile ) {
		if( !directory_info::valid_pathtofile( $pathtofile ) ) {
			return false;
		}

		if( function_exists( 'mime_content_type' ) ) {
			return mime_content_type( $pathtofile );
		}
		else {
			return exec( trim( 'file -bi ' . escapeshellarg( $pathtofile ) ) ) ;
		}
	}


	function get_human_readable_file_permissions( $pathtofile ) {

		if( !directory_info::valid_pathtofile( $pathtofile ) ) {
			return false;
		}

		$perms = fileperms( $pathtofile );

		if( ( $perms & 0xC000 ) == 0xC000 ) { $info = 's'; } // Socket
		elseif( ( $perms & 0xA000 ) == 0xA000 ) { $info = 'l'; } // Symbolic Link
		elseif( ( $perms & 0x8000 ) == 0x8000 ) { $info = '-'; } // Regular
		elseif( ( $perms & 0x6000 ) == 0x6000 ) { $info = 'b'; } // Block special
		elseif( ( $perms & 0x4000 ) == 0x4000 ) { $info = 'd'; } // Directory
		elseif( ( $perms & 0x2000 ) == 0x2000 ) { $info = 'c'; } // Character special
		elseif( ( $perms & 0x1000 ) == 0x1000 ) { $info = 'p'; } // FIFO pipe
		else { $info = 'u';	} // Unknown

		// Owner
		$info .= ( ( $perms & 0x0100 ) ? 'r' : '-' );
		$info .= ( ( $perms & 0x0080 ) ? 'w' : '-' );
		$info .= ( ( $perms & 0x0040 ) ?
					( ( $perms & 0x0800 ) ? 's' : 'x' ) :
           			( ( $perms & 0x0800 ) ? 'S' : '-' ) );

		// Group
		$info .= ( ( $perms & 0x0020 ) ? 'r' : '-' );
		$info .= ( ( $perms & 0x0010 ) ? 'w' : '-' );
		$info .= ( ( $perms & 0x0008 ) ?
					( ( $perms & 0x0400 ) ? 's' : 'x' ) :
					( ( $perms & 0x0400 ) ? 'S' : '-' ) );

		// World
		$info .= ( ( $perms & 0x0004 ) ? 'r' : '-' );
		$info .= ( ( $perms & 0x0002 ) ? 'w' : '-' );
		$info .= ( ( $perms & 0x0001 ) ?
					( ( $perms & 0x0200 ) ? 't' : 'x' ) :
					( ( $perms & 0x0200 ) ? 'T' : '-' ) );

		return $info;
	}


	function get_filelist( $use_selection = null, $pathtodir = null, $recursive = null ) {

		// If a pathtodir was passed and the path to dir was not the same as the last one used
		// to get a filelist, build a new filelist
		if( !is_null( $pathtodir ) && ( $pathtodir !== $this->last_path || $recursive !== $this->last_recursive ) ) {
			$this->traverse_directory( $pathtodir, $recursive );
			return $this->filelist;
		}
		elseif( is_null( $pathtodir ) && $this->filecount === 0 ) {
			$this->traverse_directory( $this->pathtodir, $recursive );
			return $this->filelist;
		}
		elseif( $use_selection === true && $this->fileselection_count > 0 ) {
			return $this->filelist_selection;
		}
		else {
			return $this->filelist;
		}
	}

	function get_dir_list( $pathtodir, $recursive = null ) {

		if( !is_null( $pathtodir ) && ( $pathtodir !== $this->last_path || $recursive !== $this->last_recursive ) ) {
			$this->traverse_directory( $pathtodir, $recursive );
		}
		elseif( is_null( $pathtodir ) && $this->dircount === 0 ) {
			$this->traverse_directory( $this->pathtodir, $recursive );
		}
		return $this->dirlist;
	}


	function traverse_directory( $pathtodir, $recursive = false, $prefix = '' ) {

		if( $prefix === '' ) {
			$this->last_path = $pathtodir;
			$this->last_recursive = $recursive;
			$this->filelist = array();
			$this->filelist_selection = array();
			$this->dirlist = array();
			$this->filecount = 0;
			$this->fileselection_count = 0;
			$this->dircount = 0;
		}

		if( $handle = @opendir( $pathtodir ) ) {

			while( ( $filename = readdir( $handle ) ) !== false ) {


				// Check if the file is an 'unsafe' one such as .htaccess or
				// higher directory references, if so, skip
				if( $this->safe_exts === true && strpos( $filename, '.' ) === 0 ) {
					// do nothing
				}
				else {
					// If it's a file, check against valid extensions and add to the list
					if( is_file( $pathtodir . $filename ) === true ) {
						$this->filelist[] = $prefix . $filename;
					}

					// If it's a directory and subdirectories should be listed,
					// add the subdirectory to the list.
					// If files from subdirs should be listed, run this function on the subdirectory
					elseif( is_dir( $pathtodir . $filename ) === true ) {
						$this->dirlist[] = $prefix . $filename . '/';
						if( $recursive === true) {
							$this->traverse_directory( $pathtodir . $filename . '/', $recursive, $prefix . $filename . '/' );
						}
					}
				}
				unset( $filename );
			}
			closedir( $handle );

			$this->filecount = count( $this->filelist );
			$this->dircount = count( $this->dirlist );


			if( $this->dircount > 1 ) {
				natcasesort( $this->dirlist );
				$this->dirlist = array_values( $this->dirlist );
			}
			if( $this->filecount > 1 ) {
				natcasesort( $this->filelist );
				$this->filelist = array_values( $this->filelist );
			}
		}
	}


	function get_ext_based_filelist( $use_selection = null, $pathtodir = null, $recursive = null, $exts = null, $strict = null, $mimetypes = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		$passed_files = array();

		foreach( $files as $filename ) {
			if( $this->check_allowed_file( $this->last_path . $filename, $exts, $strict, $mimetypes ) ) {
				$passed_files[] = $filename;
			}
		}

		$this->filelist_selection = $passed_files;
		$this->fileselection_count = count( $this->filelist_selection );
		return $this->filelist_selection;
	}


	function get_sorted_filelist( $sort_asc = null, $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		// Sort the resulting file list
		if( count( $files ) > 1 ) {
			natcasesort( $files );
			if( $sort_asc === false ) {
				$files = array_reverse( $files, true );
			}
			$files = array_values( $files );
		}
		return $files;
	}


	function get_sorted_dirlist( $sort_asc = null, $pathtodir = null, $recursive = null ) {

		$dirs = $this->get_dir_list( $pathtodir, $recursive );

		// Sort the resulting file list
		if( count( $dirs ) > 1 ) {
			natcasesort( $dirs );
			if( $sort_asc === false ) {
				$dirs = array_reverse( $dirs, true );
			}
			$dirs = array_values( $dirs );
		}
		return $dirs;
	}


	function get_most_recent_file( $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		// Initialize result
		$last_mod_ts = 0;
		$last_mod_file = '';

		foreach( $files as $filename ) {
			$file_mod_ts = $this->get_lastmod_unixts( $this->last_path . $filename);
			if( $file_mod_ts > $last_mod_ts ) {
				$last_mod_ts = $file_mod_ts;
				$last_mod_file = $filename;
			}
			unset( $file_mod_ts );
		}

		return array( 'filename' => $last_mod_file, 'last_modified' => $last_mod_ts );
	}


	function get_files_modified_since( $compare_ts, $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		foreach( $files as $key => $filename ) {
			$file_mod_ts = $this->get_lastmod_unixts( $this->last_path . $filename);
			if( $file_mod_ts < $compare_ts ) {
				unset( $files[$key] );
			}
		}

		$this->filelist_selection = $files;
		$this->fileselection_count = count( $this->filelist_selection );
		return $this->filelist_selection;
	}


	function get_files_modified_before( $compare_ts, $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		foreach( $files as $key => $filename ) {
			$file_mod_ts = $this->get_lastmod_unixts( $this->last_path . $filename);
			if( $file_mod_ts > $compare_ts ) {
				unset( $files[$key] );
			}
		}

		$this->filelist_selection = $files;
		$this->fileselection_count = count( $this->filelist_selection );
		return $this->filelist_selection;
	}


	function get_files_accessed_since( $compare_ts, $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		foreach( $files as $key => $filename ) {
			$file_mod_ts = $this->get_lastacc_unixts( $this->last_path . $filename);
			if( $file_mod_ts < $comparets ) {
				unset( $files[$key] );
			}
		}

		$this->filelist_selection = $files;
		$this->fileselection_count = count( $this->filelist_selection );
		return $this->filelist_selection;
	}

	function get_files_accessed_before( $compare_ts, $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		foreach( $files as $key => $filename ) {
			$file_mod_ts = $this->get_lastacc_unixts( $this->last_path . $filename);
			if( $file_mod_ts > $comparets ) {
				unset( $files[$key] );
			}
		}

		$this->filelist_selection = $files;
		$this->fileselection_count = count( $this->filelist_selection );
		return $this->filelist_selection;
	}


	function get_dirsize( $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		// Initialize result
		$dirsize = 0;

		foreach( $files as $filename ) {
			$dirsize += $this->get_filesize( $this->last_path . $filename );
		}

		return $dirsize;
	}

	function get_human_readable_dirsize( $use_selection = null, $pathtodir = null, $recursive = null ) {
		$dirsize = $this->get_dirsize( $use_selection, $pathtodir, $recursive );
		return $this->human_readable_filesize( $dirsize );
	}

	var $mime_map = array(
		'ai'	=>	'application/postscript',
		'aif'	=>	'audio/x-aiff',
		'aifc'	=>	'audio/x-aiff',
		'aiff'	=>	'audio/x-aiff',
		'asc'	=>	'text/plain',
		'au'	=>	'audio/basic',
		'avi'	=>	'video/x-msvideo',
		'bcpio'	=>	'application/x-bcpio',
		'bin'	=>	'application/octet-stream',
		'c'		=>	'text/plain',
		'cc'	=>	'text/plain',
		'ccad'	=>	'application/clariscad',
		'cdf'	=>	'application/x-netcdf',
		'class'	=>	'application/octet-stream',
		'cpio'	=>	'application/x-cpio',
		'cpt'	=>	'application/mac-compactpro',
		'csh'	=>	'application/x-csh',
		'css'	=>	'text/css',
		'dcr'	=>	'application/x-director',
		'dir'	=>	'application/x-director',
		'dms'	=>	'application/octet-stream',
		'doc'	=>	'application/msword',
		'drw'	=>	'application/drafting',
		'dvi'	=>	'application/x-dvi',
		'dwg'	=>	'application/acad',
		'dxf'	=>	'application/dxf',
		'dxr'	=>	'application/x-director',
		'eps'	=>	'application/postscript',
		'etx'	=>	'text/x-setext',
		'exe'	=>	'application/octet-stream',
		'ez'	=>	'application/andrew-inset',
		'f'		=>	'text/plain',
		'f90'	=>	'text/plain',
		'fli'	=>	'video/x-fli',
		'gif'	=>	'image/gif',
		'gtar'	=>	'application/x-gtar',
		'gz'	=>	'application/x-gzip',
		'h'		=>	'text/plain',
		'hdf'	=>	'application/x-hdf',
		'hh'	=>	'text/plain',
		'hqx'	=>	'application/mac-binhex40',
		'htm'	=>	'text/html',
		'html'	=>	'text/html',
		'ice'	=>	'x-conference/x-cooltalk',
		'ief'	=>	'image/ief',
		'iges'	=>	'model/iges',
		'igs'	=>	'model/iges',
		'ips'	=>	'application/x-ipscript',
		'ipx'	=>	'application/x-ipix',
		'jpe'	=>	'image/jpeg',
		'jpeg'	=>	'image/jpeg',
		'jpg'	=>	'image/jpeg',
		'js'	=>	'application/x-javascript',
		'kar'	=>	'audio/midi',
		'latex'	=>	'application/x-latex',
		'lha'	=>	'application/octet-stream',
		'lsp'	=>	'application/x-lisp',
		'lzh'	=>	'application/octet-stream',
		'm'		=>	'text/plain',
		'man'	=>	'application/x-troff-man',
		'me'	=>	'application/x-troff-me',
		'mesh'	=>	'model/mesh',
		'mid'	=>	'audio/midi',
		'midi'	=>	'audio/midi',
		'mif'	=>	'application/vnd.mif',
		'mime'	=>	'www/mime',
		'mov'	=>	'video/quicktime',
		'movie'	=>	'video/x-sgi-movie',
		'mp2'	=>	'audio/mpeg',
		'mp3'	=>	'audio/mpeg',
		'mpe'	=>	'video/mpeg',
		'mpeg'	=>	'video/mpeg',
		'mpg'	=>	'video/mpeg',
		'mpga'	=>	'audio/mpeg',
		'ms'	=>	'application/x-troff-ms',
		'msh'	=>	'model/mesh',
		'nc'	=>	'application/x-netcdf',
		'oda'	=>	'application/oda',
		'pbm'	=>	'image/x-portable-bitmap',
		'pdb'	=>	'chemical/x-pdb',
		'pdf'	=>	'application/pdf',
		'pgm'	=>	'image/x-portable-graymap',
		'pgn'	=>	'application/x-chess-pgn',
		'php'	=>	'text/plain',
		'php3'	=>	'text/plain',
		'png'	=>	'image/png',
		'pnm'	=>	'image/x-portable-anymap',
		'pot'	=>	'application/mspowerpoint',
		'ppm'	=>	'image/x-portable-pixmap',
		'pps'	=>	'application/mspowerpoint',
		'ppt'	=>	'application/mspowerpoint',
		'ppz'	=>	'application/mspowerpoint',
		'pre'	=>	'application/x-freelance',
		'prt'	=>	'application/pro_eng',
		'ps'	=>	'application/postscript',
		'qt'	=>	'video/quicktime',
		'ra'	=>	'audio/x-realaudio',
		'ram'	=>	'audio/x-pn-realaudio',
		'ras'	=>	'image/cmu-raster',
		'rgb'	=>	'image/x-rgb',
		'rm'	=>	'audio/x-pn-realaudio',
		'roff'	=>	'application/x-troff',
		'rpm'	=>	'audio/x-pn-realaudio-plugin',
		'rtf'	=>	'text/rtf',
		'rtx'	=>	'text/richtext',
		'scm'	=>	'application/x-lotusscreencam',
		'set'	=>	'application/set',
		'sgm'	=>	'text/sgml',
		'sgml'	=>	'text/sgml',
		'sh'	=>	'application/x-sh',
		'shar'	=>	'application/x-shar',
		'silo'	=>	'model/mesh',
		'sit'	=>	'application/x-stuffit',
		'skd'	=>	'application/x-koan',
		'skm'	=>	'application/x-koan',
		'skp'	=>	'application/x-koan',
		'skt'	=>	'application/x-koan',
		'smi'	=>	'application/smil',
		'smil'	=>	'application/smil',
		'snd'	=>	'audio/basic',
		'sol'	=>	'application/solids',
		'spl'	=>	'application/x-futuresplash',
		'src'	=>	'application/x-wais-source',
		'step'	=>	'application/STEP',
		'stl'	=>	'application/SLA',
		'stp'	=>	'application/STEP',
		'sv4cpio'	=>	'application/x-sv4cpio',
		'sv4crc'	=>	'application/x-sv4crc',
		'swf'	=>	'application/x-shockwave-flash',
		't'		=>	'application/x-troff',
		'tar'	=>	'application/x-tar',
		'tcl'	=>	'application/x-tcl',
		'tex'	=>	'application/x-tex',
		'texi'	=>	'application/x-texinfo',
		'texinfo'	=>	'application/x-texinfo',
		'tif'	=>	'image/tiff',
		'tiff'	=>	'image/tiff',
		'tr'	=>	'application/x-troff',
		'tsi'	=>	'audio/TSP-audio',
		'tsp'	=>	'application/dsptype',
		'tsv'	=>	'text/tab-separated-values',
		'txt'	=>	'text/plain',
		'unv'	=>	'application/i-deas',
		'ustar'	=>	'application/x-ustar',
		'vcd'	=>	'application/x-cdlink',
		'vda'	=>	'application/vda',
		'viv'	=>	'video/vnd.vivo',
		'vivo'	=>	'video/vnd.vivo',
		'vrml'	=>	'model/vrml',
		'wav'	=>	'audio/x-wav',
		'wrl'	=>	'model/vrml',
		'xbm'	=>	'image/x-xbitmap',
		'xlc'	=>	'application/vnd.ms-excel',
		'xll'	=>	'application/vnd.ms-excel',
		'xlm'	=>	'application/vnd.ms-excel',
		'xls'	=>	'application/vnd.ms-excel',
		'xlw'	=>	'application/vnd.ms-excel',
		'xml'	=>	'text/xml',
		'xpm'	=>	'image/x-xpixmap',
		'xwd'	=>	'image/x-xwindowdump',
		'xyz'	=>	'chemical/x-pdb',
		'zip'	=>	'application/zip'
	);

	var $valid_mime_types = array(
		'application'	=>	array( 'activemessage', 'andrew-inset', 'applefile', 'atom+xml',
				'atomicmail', 'batch-SMTP', 'beep+xml', 'cals-1840', 'ccxml+xml', 'cnrp+xml',
				'commonground', 'conference-info+xml', 'cpl+xml', 'csta+xml', 'CSTAdata+xml',
				'cybercash', 'dca-rft', 'dec-dx', 'dialog-info+xml', 'dicom', 'dns', 'dvcs',
				'ecmascript', 'EDI-Consent', 'EDIFACT', 'EDI-X12', 'epp+xml', 'eshop', 'example',
				'fastinfoset', 'fastsoap', 'fits', 'font-tdpfr', 'H224', 'http', 'hyperstudio',
				'iges', 'im-iscomposing+xml', 'index', 'index.cmd', 'index.obj', 'index.response',
				'index.vnd', 'iotp', 'ipp', 'isup', 'javascript', 'json', 'kpml-request+xml',
				'kpml-response+xml', 'mac-binhex40', 'macwriteii', 'marc', 'mathematica', 'mbox',
				'mikey', 'mpeg4-generic', 'mpeg4-iod', 'mpeg4-iod-xmt', 'mp4', 'msword', 'mxf',
				'nasdata', 'news-message-id', 'news-transmission', 'nss', 'ocsp-request',
				'ocsp-response', 'octet-stream', 'oda', 'ogg', 'parityfec', 'pdf', 'pgp-encrypted',
				'pgp-keys', 'pgp-signature', 'pidf+xml', 'pkcs10', 'pkcs7-mime', 'pkcs7-signature',
				'pkix-cert', 'pkixcmp', 'pkix-crl', 'pkix-pkipath', 'pls+xml', 'poc-settings+xml',
				'postscript', 'prs.alvestrand.titrax-sheet', 'prs.cww', 'prs.nprend', 'prs.plucker',
				'rdf+xml', 'qsig', 'reginfo+xml', 'relax-ng-compact-syntax', 'remote-printing',
				'resource-lists+xml', 'riscos', 'rlmi+xml', 'rls-services+xml', 'rtf', 'rtx',
				'samlassertion+xml', 'samlmetadata+xml', 'sbml+xml', 'sdp', 'set-payment',
				'set-payment-initiation', 'set-registration', 'set-registration-initiation',
				'sgml', 'sgml-open-catalog', 'shf+xml', 'sieve', 'simple-filter+xml',
				'simple-message-summary', 'slate', 'smil', //OBSOLETE
				'smil+xml', 'soap+fastinfoset', 'soap+xml', 'spirits-event+xml', 'srgs',
				'srgs+xml', 'ssml+xml', 'timestamp-query', 'timestamp-reply', 'tve-trigger', 'vemmi',
				'vnd.3gpp.bsf+xml', 'vnd.3gpp.pic-bw-large', 'vnd.3gpp.pic-bw-small',
				'vnd.3gpp.pic-bw-var', 'vnd.3gpp.sms', 'vnd.3gpp2.bcmcsinfo+xml', 'vnd.3gpp2.sms',
				'vnd.3M.Post-it-Notes', 'vnd.accpac.simply.aso', 'vnd.accpac.simply.imp',
				'vnd.acucobol', 'vnd.acucorp', 'vnd.adobe.xfdf', 'vnd.aether.imp', 'vnd.amiga.ami',
				'vnd.anser-web-certificate-issue-initiation', 'vnd.apple.installer+xml',
				'vnd.audiograph', 'vnd.autopackage', 'vnd.blueice.multipass', 'vnd.bmi',
				'vnd.businessobjects', 'vnd.canon-cpdl', 'vnd.canon-lips', 'vnd.cinderella',
				'vnd.chipnuts.karaoke-mmd', 'vnd.claymore', 'vnd.commerce-battelle',
				'vnd.commonspace', 'vnd.cosmocaller', 'vnd.contact.cmsg', 'vnd.crick.clicker',
				'vnd.crick.clicker.keyboard', 'vnd.crick.clicker.palette',
				'vnd.crick.clicker.template', 'vnd.crick.clicker.wordbank',
				'vnd.criticaltools.wbs+xml', 'vnd.ctc-posml', 'vnd.cups-pdf', 'vnd.cups-postscript',
				'vnd.cups-ppd', 'vnd.cups-raster', 'vnd.cups-raw', 'vnd.curl', 'vnd.cybank',
				'vnd.data-vision.rdz', 'vnd.dna', 'vnd.dpgraph', 'vnd.dreamfactory',
				'vnd.dvb.esgcontainer', 'vnd.dvb.ipdcesgaccess', 'vnd.dxr', 'vnd.ecdis-update',
				'vnd.ecowin.chart', 'vnd.ecowin.filerequest', 'vnd.ecowin.fileupdate',
				'vnd.ecowin.series', 'vnd.ecowin.seriesrequest', 'vnd.ecowin.seriesupdate',
				'vnd.enliven', 'vnd.epson.esf', 'vnd.epson.msf', 'vnd.epson.quickanime',
				'vnd.epson.salt', 'vnd.epson.ssf', 'vnd.ericsson.quickcall', 'vnd.eudora.data',
				'vnd.ezpix-album', 'vnd.ezpix-package', 'vnd.fdf', 'vnd.ffsns', 'vnd.fints',
				'vnd.FloGraphIt', 'vnd.fluxtime.clip', 'vnd.framemaker', 'vnd.frogans.fnc',
				'vnd.frogans.ltf', 'vnd.fsc.weblaunch', 'vnd.fujitsu.oasys', 'vnd.fujitsu.oasys2',
				'vnd.fujitsu.oasys3', 'vnd.fujitsu.oasysgp', 'vnd.fujitsu.oasysprs',
				'vnd.fujixerox.ART4', 'vnd.fujixerox.ART-EX', 'vnd.fujixerox.ddd',
				'vnd.fujixerox.docuworks', 'vnd.fujixerox.docuworks.binder', 'vnd.fujixerox.HBPL',
				'vnd.fut-misnet', 'vnd.genomatix.tuxedo', 'vnd.grafeq', 'vnd.groove-account',
				'vnd.groove-help', 'vnd.groove-identity-message', 'vnd.groove-injector',
				'vnd.groove-tool-message', 'vnd.groove-tool-template', 'vnd.groove-vcard',
				'vnd.HandHeld-Entertainment+xml', 'vnd.hbci', 'vnd.hcl-bireports',
				'vnd.hhe.lesson-player', 'vnd.hp-HPGL', 'vnd.hp-hpid', 'vnd.hp-hps',
				'vnd.hp-jlyt', 'vnd.hp-PCL', 'vnd.hp-PCLXL', 'vnd.httphone', 'vnd.hzn-3d-crossword',
				'vnd.ibm.afplinedata', 'vnd.ibm.electronic-media', 'vnd.ibm.MiniPay',
				'vnd.ibm.modcap', 'vnd.ibm.rights-management', 'vnd.ibm.secure-container',
				'vnd.igloader', 'vnd.informix-visionary', 'vnd.intercon.formnet',
				'vnd.intertrust.digibox', 'vnd.intertrust.nncp', 'vnd.intu.qbo', 'vnd.intu.qfx',
				'vnd.ipunplugged.rcprofile', 'vnd.irepository.package+xml', 'vnd.is-xpr',
				'vnd.japannet-directory-service', 'vnd.japannet-jpnstore-wakeup',
				'vnd.japannet-payment-wakeup', 'vnd.japannet-registration',
				'vnd.japannet-registration-wakeup', 'vnd.japannet-setstore-wakeup',
				'vnd.japannet-verification', 'vnd.japannet-verification-wakeup',
				'vnd.jisp', 'vnd.kahootz', 'vnd.kde.karbon', 'vnd.kde.kchart', 'vnd.kde.kformula',
				'vnd.kde.kivio', 'vnd.kde.kontour', 'vnd.kde.kpresenter', 'vnd.kde.kspread',
				'vnd.kde.kword', 'vnd.kenameaapp', 'vnd.kidspiration', 'vnd.Kinar',
				'vnd.koan', 'vnd.liberty-request+xml', 'vnd.llamagraphics.life-balance.desktop',
				'vnd.llamagraphics.life-balance.exchange+xml', 'vnd.lotus-1-2-3',
				'vnd.lotus-approach', 'vnd.lotus-freelance', 'vnd.lotus-notes',
				'vnd.lotus-organizer', 'vnd.lotus-screencam', 'vnd.lotus-wordpro',
				'vnd.marlin.drm.mdcf', 'vnd.mcd', 'vnd.medcalcdata', 'vnd.mediastation.cdkey',
				'vnd.meridian-slingshot', 'vnd.mfmp', 'vnd.micrografx.flo', 'vnd.micrografx.igx',
				'vnd.mif', 'vnd.minisoft-hp3000-save', 'vnd.mitsubishi.misty-guard.trustweb',
				'vnd.Mobius.DAF', 'vnd.Mobius.DIS', 'vnd.Mobius.MBK', 'vnd.Mobius.MQY',
				'vnd.Mobius.MSL', 'vnd.Mobius.PLC', 'vnd.Mobius.TXF', 'vnd.mophun.application',
				'vnd.mophun.certificate', 'vnd.motorola.flexsuite', 'vnd.motorola.flexsuite.adsi',
				'vnd.motorola.flexsuite.fis', 'vnd.motorola.flexsuite.gotap',
				'vnd.motorola.flexsuite.kmr', 'vnd.motorola.flexsuite.ttc',
				'vnd.motorola.flexsuite.wem', 'vnd.mozilla.xul+xml', 'vnd.ms-artgalry',
				'vnd.ms-asf', 'vnd.ms-cab-compressed', 'vnd.mseq', 'vnd.ms-excel',
				'vnd.ms-fontobject', 'vnd.ms-htmlhelp', 'vnd.msign', 'vnd.ms-ims', 'vnd.ms-lrm',
				'vnd.ms-powerpoint', 'vnd.ms-project', 'vnd.ms-tnef', 'vnd.ms-wmdrm.lic-chlg-req',
				'vnd.ms-wmdrm.lic-resp', 'vnd.ms-works', 'vnd.ms-wpl', 'vnd.ms-xpsdocument',
				'vnd.musician', 'vnd.music-niff', 'vnd.nervana', 'vnd.netfpx',
				'vnd.noblenet-directory', 'vnd.noblenet-sealer', 'vnd.noblenet-web',
				'vnd.nokia.catalogs', 'vnd.nokia.conml+wbxml', 'vnd.nokia.conml+xml',
				'vnd.nokia.iptv.config+xml', 'vnd.nokia.landmark+wbxml', 'vnd.nokia.landmark+xml',
				'vnd.nokia.landmarkcollection+xml', 'vnd.nokia.pcd+wbxml', 'vnd.nokia.pcd+xml',
				'vnd.nokia.radio-preset', 'vnd.nokia.radio-presets', 'vnd.novadigm.EDM',
				'vnd.novadigm.EDX', 'vnd.novadigm.EXT', 'vnd.oasis.opendocument.chart',
				'vnd.oasis.opendocument.chart-template', 'vnd.oasis.opendocument.formula',
				'vnd.oasis.opendocument.formula-template', 'vnd.oasis.opendocument.graphics',
				'vnd.oasis.opendocument.graphics-template', 'vnd.oasis.opendocument.image',
				'vnd.oasis.opendocument.image-template', 'vnd.oasis.opendocument.presentation',
				'vnd.oasis.opendocument.presentation-template', 'vnd.oasis.opendocument.spreadsheet',
				'vnd.oasis.opendocument.spreadsheet-template', 'vnd.oasis.opendocument.text',
				'vnd.oasis.opendocument.text-master', 'vnd.oasis.opendocument.text-template',
				'vnd.oasis.opendocument.text-web', 'vnd.obn', 'vnd.oma.dd2+xml',
				'vnd.omads-email+xml', 'vnd.omads-file+xml', 'vnd.omads-folder+xml',
				'vnd.omaloc-supl-init', 'vnd.osa.netdeploy', 'vnd.osgi.dp', 'vnd.otps.ct-kip+xml',
				'vnd.palm', 'vnd.paos.xml', 'vnd.pg.format', 'vnd.pg.osasli',
				'vnd.piaccess.application-licence', 'vnd.picsel', 'vnd.pocketlearn',
				'vnd.powerbuilder6', 'vnd.powerbuilder6-s', 'vnd.powerbuilder7',
				'vnd.powerbuilder75', 'vnd.powerbuilder75-s', 'vnd.powerbuilder7-s',
				'vnd.preminet', 'vnd.previewsystems.box', 'vnd.proteus.magazine',
				'vnd.publishare-delta-tree', 'vnd.pvi.ptid1', 'vnd.pwg-multiplexed',
				'vnd.pwg-xhtml-print+xml', 'vnd.qualcomm.brew-app-res', 'vnd.Quark.QuarkXPress',
				'vnd.rapid', 'vnd.RenLearn.rlprint', 'vnd.ruckus.download', 'vnd.s3sms',
				'vnd.scribus', 'vnd.sealed.3df', 'vnd.sealed.csf', 'vnd.sealed.doc',
				'vnd.sealed.eml', 'vnd.sealed.mht', 'vnd.sealed.net', 'vnd.sealed.ppt',
				'vnd.sealed.tiff', 'vnd.sealed.xls', 'vnd.sealedmedia.softseal.html',
				'vnd.sealedmedia.softseal.pdf', 'vnd.seemail', 'vnd.sema',
				'vnd.shana.informed.formdata', 'vnd.shana.informed.formtemplate',
				'vnd.shana.informed.interchange', 'vnd.shana.informed.package',
				'vnd.smaf', 'vnd.solent.sdkm+xml', 'vnd.sss-cod', 'vnd.sss-dtf', 'vnd.sss-ntf',
				'vnd.street-stream', 'vnd.sun.wadl+xml', 'vnd.sus-calendar', 'vnd.svd',
				'vnd.swiftview-ics', 'vnd.syncml.dm+wbxml', 'vnd.syncml.ds.notification',
				'vnd.syncml.+xml', 'vnd.triscape.mxs', 'vnd.trueapp', 'vnd.truedoc',
				'vnd.ufdl', 'vnd.uiq.theme', 'vnd.umajin', 'vnd.uoml+xml', 'vnd.uplanet.alert',
				'vnd.uplanet.alert-wbxml', 'vnd.uplanet.bearer-choice',
				'vnd.uplanet.bearer-choice-wbxml', 'vnd.uplanet.cacheop', 'vnd.uplanet.cacheop-wbxml',
				'vnd.uplanet.channel', 'vnd.uplanet.channel-wbxml', 'vnd.uplanet.list',
				'vnd.uplanet.listcmd', 'vnd.uplanet.listcmd-wbxml', 'vnd.uplanet.list-wbxml',
				'vnd.uplanet.signal', 'vnd.vcx', 'vnd.vectorworks', 'vnd.vd-study',
				'vnd.vidsoft.vidconference', 'vnd.visio', 'vnd.visionary',
				'vnd.vividence.scriptfile', 'vnd.vsf', 'vnd.wap.sic', 'vnd.wap.slc', 'vnd.wap.wbxml',
				'vnd.wap.wmlc', 'vnd.wap.wmlscriptc', 'vnd.webturbo', 'vnd.wfa.wsc',
				'vnd.wordperfect', 'vnd.wqd', 'vnd.wrq-hp3000-labelled', 'vnd.wt.stf',
				'vnd.wv.csp+xml', 'vnd.wv.csp+wbxml', 'vnd.wv.ssp+xml', 'vnd.xara', 'vnd.xfdl',
				'vnd.yamaha.hv-dic', 'vnd.yamaha.hv-script', 'vnd.yamaha.hv-voice',
				'vnd.yamaha.smaf-audio', 'vnd.yamaha.smaf-phrase', 'vnd.yellowriver-custom-menu',
				'vnd.zzazz.deck+xml', 'voicexml+xml', 'watcherinfo+xml', 'whoispp-query',
				'whoispp-response', 'wita', 'wordperfect5.1', 'x400-bp', 'xcap-att+xml',
				'xcap-caps+xml', 'xcap-el+xml', 'xcap-error+xml', 'xenc+xml',
				'xhtml-voice+xml', // OBSOLETE
				'xhtml+xml', 'xml', 'xml-dtd', 'xml-external-parsed-entity', 'xmpp+xml',
				'xop+xml', 'xv+xml', 'zip',
		),
		'audio'	=>	array(
				'32kadpcm', '3gpp', '3gpp2', 'ac3', 'AMR', 'AMR-WB', 'amr-wb+', 'asc',
				'basic', 'BV16', 'BV32', 'clearmode', 'CN', 'DAT12', 'dls', 'dsr-es201108',
				'dsr-es202050', 'dsr-es202211', 'dsr-es202212', 'eac3', 'DVI4', 'EVRC',
				'EVRC0', 'EVRC-QCP', 'example', 'G722', 'G7221', 'G723', 'G726-16',
				'G726-24', 'G726-32', 'G726-40', 'G728', 'G729', 'G729D', 'G729E', 'GSM',
				'GSM-EFR', 'iLBC', 'L8', 'L16', 'L20', 'L24', 'LPC', 'MPA', 'mp4', 'MP4A-LATM',
				'mpa-robust', 'mpeg', 'mpeg4-generic', 'parityfec', 'PCMA', 'PCMU', 'prs.sid',
				'QCELP', 'RED', 'rtp-midi', 'rtx', 'SMV', 'SMV0', 'SMV-QCP', 't140c', 't38',
				'telephone-event', 'tone', 'VDVI', 'VMR-WB', 'vnd.3gpp.iufp', 'vnd.4SB',
				'vnd.audiokoz', 'vnd.CELP', 'vnd.cisco.nse', 'vnd.cmles.radio-events',
				'vnd.cns.anp1', 'vnd.cns.inf1', 'vnd.digital-winds', 'vnd.dlna.adts',
				'vnd.everad.plj', 'vnd.hns.audio', 'vnd.lucent.voice', 'vnd.nokia.mobile-xmf',
				'vnd.nortel.vbk', 'vnd.nuera.ecelp4800', 'vnd.nuera.ecelp7470',
				'vnd.nuera.ecelp9600', 'vnd.octel.sbc',
				'vnd.qcelp', // DEPRECATED - Please use audio/qcelp
				'vnd.rhetorex.32kadpcm', 'vnd.sealedmedia.softseal.mpeg', 'vnd.vmx.cvsd'
		),
		'example'	=>	array(),
		'image '	=>	array(
				'cgm', 'example', 'fits', 'g3fax', 'gif', 'ief', 'jp2', 'jpeg', 'jpm', 'jpx',
				'naplps', 'png', 'prs.btif', 'prs.pti', 't38', 'tiff', 'tiff-fx',
				'vnd.adobe.photoshop', 'vnd.cns.inf2', 'vnd.djvu', 'vnd.dwg', 'vnd.dxf',
				'vnd.fastbidsheet', 'vnd.fpx', 'vnd.fst', 'vnd.fujixerox.edmics-mmr',
				'vnd.fujixerox.edmics-rlc', 'vnd.globalgraphics.pgb', 'vnd.microsoft.icon',
				'vnd.mix', 'vnd.ms-modi', 'vnd.net-fpx', 'vnd.sealed.png',
				'vnd.sealedmedia.softseal.gif', 'vnd.sealedmedia.softseal.jpg', 'vnd.svf',
				'vnd.wap.wbmp', 'vnd.xiff'
		),
		'message'	=>	array(
				'CPIM', 'delivery-status', 'disposition-notification', 'example',
				'external-body', 'http', 'news', 'partial', 'rfc822', 's-http', 'sip',
				'sipfrag', 'tracking-status'
		),
		'model'	=>	array(
				'example', 'iges', 'mesh', 'vnd.dwf', 'vnd.flatland.3dml', 'vnd.gdl',
				'vnd.gs-gdl', 'vnd.gtw', 'vnd.moml+xml', 'vnd.mts', 'vnd.parasolid.transmit.binary',
				'vnd.parasolid.transmit.text', 'vnd.vtu', 'vrml'
		),
		'multipart'	=>	array(
				'alternative', 'appledouble', 'byteranges', 'digest', 'encrypted', 'example',
				'form-data', 'header-set', 'mixed', 'parallel', 'related', 'report', 'signed',
				'voice-message'
		),
		'text'	=>	array(
				'calendar', 'css', 'csv', 'directory', 'dns', 'ecmascript', // OBSOLETE
				'enriched', 'example', 'html', 'javascript', // OBSOLETE
				'parityfec', 'plain', 'prs.fallenstein.rst', 'prs.lines.tag', 'RED',
				'rfc822-headers', 'richtext', 'rtf', 'rtx', 'sgml', 't140',
				'tab-separated-values', 'troff', 'uri-list', 'vnd.abc', 'vnd.curl',
				'vnd.DMClientScript', 'vnd.esmertec.theme-descriptor', 'vnd.fly',
				'vnd.fmi.flexstor', 'vnd.in3d.3dml', 'vnd.in3d.spot', 'vnd.IPTC.NewsML',
				'vnd.IPTC.NITF', 'vnd.latex-z', 'vnd.motorola.reflex', 'vnd.ms-mediapackage',
				'vnd.net2phone.commcenter.command', 'vnd.sun.j2me.app-descriptor', 'vnd.wap.si',
				'vnd.wap.sl', 'vnd.wap.wml', 'vnd.wap.wmlscript', 'xml', 'xml-external-parsed-entity'
		),
		'video'	=>	array(
				'3gpp', '3gpp2', '3gpp-tt', 'BMPEG', 'BT656', 'CelB', 'DV', 'example', 'H261',
				'H263', 'H263-1998', 'H263-2000', 'H264', 'JPEG', 'MJ2', 'MP1S', 'MP2P', 'MP2T',
				'mp4', 'MP4V-ES', 'MPV', 'mpeg', 'mpeg4-generic', 'nv', 'parityfec', 'pointer',
				'quicktime', 'raw', 'rtx', 'SMPTE292M', 'vc1', 'vnd.dlna.mpeg-tts', 'vnd.fvt',
				'vnd.hns.video', 'vnd.motorola.video', 'vnd.motorola.videop', 'vnd.mpegurl',
				'vnd.nokia.interleaved-multimedia', 'vnd.objectvideo', 'vnd.sealed.mpeg1',
				'vnd.sealed.mpeg4', 'vnd.sealed.swf', 'vnd.sealedmedia.softseal.mov',
				'vnd.vivo'
		)
	);


}// End of class

//****** END OF FILE ******/
?>