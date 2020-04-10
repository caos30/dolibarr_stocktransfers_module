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
 *  \file       htdocs/stocktransfers/core/triggers/interface_90_modProduct_Movements.class.php
 *  \ingroup    stocktransfers
 *  \brief      Update table 'stock_transfers' when there are changes on stock movements
 *  \version    v 1.0 2017/11/20
 */

// == STOCKTRANSFERS_MODULE DOCUMENT_ROOT & URL_ROOT
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/stocktransfers/core/modules/modStocktransfers.class.php')){
        define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/custom/stocktransfers');
        define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/custom/stocktransfers');
    }else{
        define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/stocktransfers');
        define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/stocktransfers');
    }

dol_include_once('/core/triggers/dolibarrtriggers.class.php');
dol_include_once('/stocktransfers/lib/stocktransfers_transfer.class.php');

//ini_set('display_errors',1);ini_set('display_startup_errors',1);error_reporting(-1); // == ACTIVATE the ERROR reporting

/**
 *  Class of triggers for demo module
 *
 *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *
 *  IT REALLY DON'T WORK YET !! i unknow what's wrong with it :(
 *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *
 *
 */
class InterfaceStockTransfers extends DolibarrTriggers
{

	public $family = 'products';
	public $picto = 'stocktransfers';
	public $description = "Update table 'stock_transfers' when there are changes on stock movements";
	public $version = self::VERSION_DOLIBARR;

    /**
     * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
     *
     * @param string		$action		Event action code
     * @param Object		$object     Object concerned. Some context information may also be provided into array property object->context.
     * @param User		    $user       Object user
     * @param Translate 	$langs      Object langs
     * @param conf		    $conf       Object conf
     * @return int         	0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
            global $db;
		// Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action

	    switch ($action) {

                case 'STOCK_MOVEMENT':

                    /* this event is triggered when a movement is added during
                     * a massive stock movement insertion
                     * so we take measures to avoid add 2 the same transfer: $_POST['token']
                     */
                    if (!isset($_SESSION['last_stocktransfer_token'])
                            || $_SESSION['last_stocktransfer_token']!=$_POST['token']){

                        if (empty($_SESSION['massstockmove'])) break;
                        $listofdata = json_decode($_SESSION['massstockmove'],true);
                        dol_include_once('/stocktransfers/lib/stocktransfers_transfer.class.php');
                        $transfer = new StockTransfer($db);
                        $transfer->label = 'Alert: massive stock movement';
                        $transfer->s = 'GET= '.var_export($_GET,true).' | POST= '.var_export($_POST,true).' | _SESSION[massstockmove]= '.var_export($listofdata,true);
                        $result = $transfer->create(NULL);
                        if ($result < 0) dol_print_error($db,$transfer->error);

                        $_SESSION['last_stocktransfer_token'] = $_POST['token'];
                    }
                    break;

	    }

        return 0;
	}

}
