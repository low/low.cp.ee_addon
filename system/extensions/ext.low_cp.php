<?php
/*
=====================================================
 This extension was created by Lodewijk Schutte
 - freelance@loweblog.com
 - http://loweblog.com/freelance/
=====================================================
 File: ext.low_cp.php
-----------------------------------------------------
 Purpose: Control Panel according to Low
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

class low_cp
{
	var $settings			= array();

	var $name				= 'Low CP';
	var $version			= '1.0.2';
	var $description		= 'Control Panel according to Low';
	var $settings_exist		= 'n';
	var $docs_url			= '';
			
	// -------------------------------
	// Constructor
	// -------------------------------
	function low_cp($settings = '')
	{
		$this->settings = $settings;
	}
	// END low_cp
	
	
	// --------------------------------
	//	Settings
	// --------------------------------	
	function settings()
	{
		return array();
	}
	// END settings


	// --------------------------------
	//	Global CP fixes
	// -------------------------------- 
	function global_cp_fixes($out)
	{
		// get last call
		$out = $this->_get_last_call($out);

		// output check
		if (REQ != 'CP') return $out;
	
		global $IN;
	
		$C = $IN->GBL('C','GET');
		$M = $IN->GBL('M','GET');
	
		if ( ($C == 'publish' && $M == 'entry_form') || ($C == 'edit' && $M == 'edit_entry') )
		{
			$out = $this->fix_link_button($out); 
			$out = $this->fix_date_fields($out);
			$out = $this->fix_textarea_helpers($out);
		}

		return $out;
	}
	// END global_cp_fixes()

	
	// --------------------------------
	//	No title attribute for links
	// -------------------------------- 
	function fix_link_button($out)
	{
		$find = array(
			"var Title = prompt(title_text, theSelection);",
			"var Link = '<a href=\"' + URL + '\" title=\"' + Title + '\">' + Name + '<'+'/a>'"
		);
	
		$replace = array(
			"/* var Title = prompt(title_text, theSelection);",
			"*/ var Link = '<a href=\"' + URL + '\">' + Name + '<'+'/a>'"
		);

		return str_replace($find, $replace, $out);
	}
	// END fix_link_button()


	// --------------------------------
	//	No localisation for custom date fields
	// -------------------------------- 
	function fix_date_fields($out)
	{
		return preg_replace('/<select name=\'(field_offset_\d+)\'.*?<\/select>/is','<input type="hidden" name="$1" value="n" />', $out);
	}
	// END fix_date_fields()


	// --------------------------------
	//	No spellcheck, no smilies, no glossary
	// -------------------------------- 
	function fix_textarea_helpers($out)
	{
		$out = str_replace('&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;','',$out);
		$out = preg_replace('/<div id="smileys.*?(<div id="spellcheck_field)/is','$1',$out);

		// keeping things light-weight, using str_replace instead of preg_replace
		$bin = array('spellcheck', 'glossary', 'smileys');
		foreach ($bin AS $trash)
		{
			$out = str_replace('onclick="showhide_'.$trash, 'style="display:none" onclick="showhide_'.$trash, $out);
		}
		
		return $out;
	}
	// END fix_textarea_helpers()


	// --------------------------------
	//	No trackbacks in the edit list header
	// -------------------------------- 
	function fix_edit_list_thead($thead)
	{
		// get last call
		$thead = $this->_get_last_call($thead);

		global $LANG;
		return preg_replace('/(.*)<td.*'.$LANG->line('trackbacks').'.*?<\/td>/is','$1',$thead);
	}
	// END fix_edit_list_thead()


	// --------------------------------
	//	No trackbacks in the edit list body
	// -------------------------------- 
	function fix_edit_list_tbody($row)
	{
		// get last call
		$row = $this->_get_last_call($row);

		return preg_replace('/(.*)<td.*?view_trackbacks.*?<\/td>/is', '$1', $row);
	}
	// END fix_edit_list_tbody()

	
	// --------------------------------
	//	No limit to appending numbers to the url-title
	// -------------------------------- 
	function fix_unique_url_title()
	{
		global $IN, $DB, $REGX;

		$entry_id  = $IN->GBL('entry_id');
		$weblog_id = $IN->GBL('weblog_id');
		$url_title = $IN->GBL('url_title');
		$sql_entry = '';

		if (!$url_title) return;

		if ($entry_id != '')
		{
			$sql_entry = "AND entry_id != '{$entry_id}'";
			$url_query = $DB->query("SELECT url_title FROM exp_weblog_titles WHERE entry_id = '{$entry_id}'");

			if ($url_query->row['url_title'] != $url_title)
			{
				$url_title = $REGX->create_url_title($url_title);
				// and remove possible digit from the end
				$url_title = preg_replace('/([^_\-])(\d+)?$/','$1',$url_title);
			}

		}
		else
		{
			$url_title = $REGX->create_url_title($url_title);
		}

		// Is the url_title a pure number? Let the main code handle it.
		if (is_numeric($url_title)) return;

		// Is the URL Title empty? Let the main code handle it.
		if (trim($url_title) == '') return;
	
		/** ---------------------------------
		/**	Is URL title unique?
		/** ---------------------------------*/

		$sql = "SELECT url_title FROM exp_weblog_titles WHERE url_title REGEXP '^{$url_title}[[:digit:]]+$' AND weblog_id = '{$weblog_id}' {$sql_entry} ORDER BY entry_id ASC";
		$query = $DB->query($sql);
		$titles = array();
		foreach($query->result AS $row)
		{
			$titles[] = $row['url_title'];
		}
	
		// nothing found? No problem!
		if (!count($titles)) return;

		$unique = FALSE;
		$i = count($titles);

		while ($unique == FALSE)
		{
			$temp = ($i == 0) ? $url_title : $url_title.$i;
			$i++;
		
			if (!in_array($temp, $titles)) // BINGO!
			{
				$_POST['url_title'] = $temp;
				$unique = TRUE;
			}
		}
	}
	// END fix_unique_url_title()



	/**
	 * Get Last Call
	 *
	 * @param  mixed  $param  Parameter sent by extension hook
	 * @return mixed  Return value of last extension call if any, or $param
	 * @access private
	 * @author Brandon Kelly <me@brandon-kelly.com>
	 */
	function _get_last_call($param=FALSE)
	{
		global $EXT;
		return $EXT->last_call !== FALSE ? $EXT->last_call : $param;
	}


	
	// --------------------------------
	//	Activate Extension
	// --------------------------------
	function activate_extension()
	{
		global $DB, $PREFS;
		
		$DB->query(
			$DB->insert_string(
				$PREFS->ini('db_prefix').'_extensions',
				array(
					'extension_id'	=> '',
					'class'			=> __CLASS__,
					'method'		=> "global_cp_fixes",
					'hook'			=> "show_full_control_panel_end",
					'settings'		=> '',
					'priority'		=> 5,
					'version'		=> $this->version,
					'enabled'		=> "y"
				)
			)
		); // end db->query

	$DB->query(
		$DB->insert_string(
			$PREFS->ini('db_prefix').'_extensions',
			array(
				'extension_id'	=> '',
				'class'			=> __CLASS__,
				'method'		=> "fix_edit_list_thead",
				'hook'			=> "edit_entries_modify_tableheader",
				'settings'		=> '',
				'priority'		=> 5,
				'version'		=> $this->version,
				'enabled'		=> "y"
			)
		)
	); // end db->query
	
	$DB->query(
		$DB->insert_string(
			$PREFS->ini('db_prefix').'_extensions',
			array(
				'extension_id'	=> '',
				'class'			=> __CLASS__,
				'method'		=> "fix_edit_list_tbody",
				'hook'			=> "edit_entries_modify_tablerow",
				'settings'		=> '',
				'priority'		=> 5,
				'version'		=> $this->version,
				'enabled'		=> "y"
			)
		)
	); // end db->query
	
	$DB->query(
		$DB->insert_string(
			$PREFS->ini('db_prefix').'_extensions',
			array(
				'extension_id'	=> '',
				'class'			=> __CLASS__,
				'method'		=> "fix_unique_url_title",
				'hook'			=> "submit_new_entry_start",
				'settings'		=> '',
				'priority'		=> 5,
				'version'		=> $this->version,
				'enabled'		=> "y"
			)
		)
	); // end db->query

	}
	// END activate_extension
	 
	 
	// --------------------------------
	//	Update Extension
	// --------------------------------	
	function update_extension($current='')
	{
		global $DB, $PREFS;
		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$DB->query("UPDATE ".$PREFS->ini('db_prefix')."_extensions 
								SET version = '".$DB->escape_str($this->version)."' 
								WHERE class = '".__CLASS__."'");
	}
	// END update_extension

	// --------------------------------
	//	Disable Extension
	// --------------------------------
	function disable_extension()
	{
		global $DB, $PREFS;
		
		$DB->query("DELETE FROM ".$PREFS->ini('db_prefix')."_extensions WHERE class = '".__CLASS__."'");
	}
	// END disable_extension
	 
}
// END CLASS
?>