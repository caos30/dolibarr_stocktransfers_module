<?php
/* Copyright (C) 2017 Sergi Rodrigues <proyectos@imasdeweb.com>
 *
 * Licensed under the GNU GPL v3 or higher (See file gpl-3.0.html)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/stocktransfers/core/modules/modStockTransfers.class.php
 *      \defgroup   stocktransfers Stock transfers of products between company stores
 *      \brief      Description and activation file for stocktransfers module
 *      \version    v 1.0 2017/11/20
 */

//ini_set('display_errors',1);ini_set('display_startup_errors',1);error_reporting(-1); // == ACTIVATE the ERROR reporting

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 *      Description and activation class for module stocktransfers
 */
class modStockTransfers extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param		DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
	        global $langs,$conf;
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used module id).
		$this->numero = 570002;
		// Key text used to identify module (for permission, menus, etc...)
		$this->rights_class = 'stocktransfers';

		// Family can be 'crm','financial','hr','projects','products','technic','other'
		// It is used to group modules in module setup page
		$this->family = "products";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',str_replace('_',' ',get_class($this)));
		// Module description used if translation string 'ModuleXXXDesc' not found (XXX is value MyModule)
		$this->description = 'stocktransfersDescription';
	        $this->editor_name = 'Imasdeweb';
	        $this->editor_url = 'https://imasdeweb.com';
		$this->editor_web = 'imasdeweb.com';
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.20 [ Dolibarr 5 - 14.0.x ]';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='dolly';//'stocktransfers@stocktransfers';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /mymodule/core/modules/barcode)
		// for specific css file (eg: /mymodule/css/mymodule.css.php)
		$this->module_parts = array(
		                      'triggers' => 1,                                 // Set this to 1 if module has its own trigger directory
		//			'login' => 0,                                    // Set this to 1 if module has its own login method directory
		//			'substitutions' => 0,                            // Set this to 1 if module has its own substitution function file
		//			'menus' => 0,                                    // Set this to 1 if module has its own menus handler directory
		//			'barcode' => 0,                                  // Set this to 1 if module has its own barcode directory
		//			'models' => 0,                                   // Set this to 1 if module has its own models directory
		//			'css' => '/stocktransfers/css/stocktransfers.css.php',       // Set this to relative path of css if module has its own css file
		//			'hooks' => array('hookcontext1','hookcontext2')  // Set here all hooks context managed by module
        );

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config pages. Put here list of php page names stored in admmin directory used to setup module
		$this->config_page_url = array('config.php@stocktransfers');

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,1);	// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(5,0,-2);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("stocktransfers@stocktransfers");

		// Constants
		$this->const = array();			// List of parameters

		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',  					// To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
        //                              'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		// where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view
            //$this->tabs = array('product:+stocktransfers:stocktransfersBriefTitle:stocktransfers@stocktransfers:true:stocktransfers/index.php');

            if (!isset($conf->stocktransfers) || !isset($conf->stocktransfers->enabled))
            {
                    $conf->stocktransfers=new stdClass();
                    $conf->stocktransfers->enabled=0;
            }

            // Dictionaries
                    $this->dictionaries=array();

            /* Example:
            $this->dictionaries=array(
                'langs'=>'mylangfile@mymodule',
                'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
                'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
                'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
                'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
                'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionary)
                'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
                'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
                'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
                'tabcond'=>array($conf->mymodule->enabled,$conf->mymodule->enabled,$conf->mymodule->enabled)												// Condition to show each dictionary
            );
            */

		// Boxes
		$this->boxes = array(
		    0 => array('file'=>'box_stocktransfers.php@stocktransfers','note'=>'', 'enabledbydefaulton'=>'Home'),
		);

		// Cronjobs
		$this->cronjobs = array();			// List of cron jobs entries to add
		// Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'test'=>true),
		//                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'test'=>true)
		// );

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
		// Example:
		// $this->rights[$r][0] = 2000; 				// Permission id (must not be already used)
		// $this->rights[$r][1] = 'Permision label';	// Permission label
		// $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		// $this->rights[$r][4] = 'level1';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $this->rights[$r][5] = 'level2';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $r++;

		// = Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;

            // = Left menu entrie uder 'mainmenu'
            // = doc: https://wiki.dolibarr.org/index.php/Module_development#Define_your_entries_in_menu_.28optional.29

            $this->menu[$r]=array(
                                'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=stock', // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode of parent menu
                                'type'=>'left', // This is a Left menu entry
                                'titre'=>'stocktransfersMenuTitle2',
                                'mainmenu'=>'products',
                                'leftmenu'=>'stock',
                                'url'=>'/stocktransfers/transfer_list.php',
                                'langs'=>'stocktransfers@stocktransfers',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>4,
                                'enabled'=>'1', // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                                'perms'=>'($user->rights->stock->mouvement->lire&&$user->rights->produit)', // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>0);

		$r++;

	        $this->menu[$r]=array(
	                            'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=stock', // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode of parent menu
	                            'type'=>'left', // This is a Left menu entry
	                            'titre'=>'stocktransfersMenuTitle1',
	                            'mainmenu'=>'products',
	                            'leftmenu'=>'stock',
	                            'url'=>'/stocktransfers/transfer_edit.php',
	                            'langs'=>'stocktransfers@stocktransfers',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
	                            'position'=>5,
	                            'enabled'=>'1', // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
	                            'perms'=>'($user->rights->stock->mouvement->creer&&$user->rights->produit)', // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
	                            'target'=>'',
	                            'user'=>0);

		$r++;

	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{

    		$sql = array();

		$result = $this->_load_tables('/stocktransfers/sql/');

		return $this->_init($sql, $options);
	}

	/**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function remove($options='')
	{
		$sql = array();

		return $this->_remove($sql,$options);
	}

}
