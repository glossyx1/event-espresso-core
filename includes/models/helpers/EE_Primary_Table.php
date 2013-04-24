<?php

/**
 * For defining the main table of the model. This is the table with the model's primary key's field. So for CPT models, this will
 * probably be the wp_posts table (so the table name supplied will be 'posts', as the 'wp_' varies). For models only using one table, this will be that table.
 */
require_once('helpers/EE_Table_Base.php');
class EE_Primary_Table extends EE_Table_Base{
	
	function __construct($table_name, $pk_column = null){
		parent::__construct($table_name, $pk_column);
	}
	
}