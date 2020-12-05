<?php
/* Copyright (C) 2020 Sergi Rodrigues <proyectos@imasdeweb.com>
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
 *	\file       htdocs/stocktransfers/json.php
 *      \defgroup   stocktransfers Module Stock transfers
 *      \brief      to make JSON calls from frontend to server
 *      \version    v 1.0 2020/04/28
 */

    // == ACTIVATE the ERROR reporting
    ini_set('display_errors',1);ini_set('display_startup_errors',1);error_reporting(-1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php");
if (! $res && preg_match('/\/imasdeweb([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res) die("Include of main fails");

// == STOCKTRANSFERS_MODULE DOCUMENT_ROOT & URL_ROOT
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/stocktransfers/core/modules/modStocktransfers.class.php')){
        define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/custom/stocktransfers');
        define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/custom/stocktransfers');
    }else{
        define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/stocktransfers');
        define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/stocktransfers');
    }

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once STOCKTRANSFERS_MODULE_DOCUMENT_ROOT.'/lib/stocktransfers_transfer.class.php';

include_once("./lib/stocktransfers.lib.php");

$user->getrights('stocktransfers');
$langs->load("stocktransfers@stocktransfers");

// == Security check
    $result = restrictedArea($user,'stock&produit');

// == Get parameters
    $action = GETPOST('action','alpha');

    switch ($action){
        case 'get_stock':
            $product_id = GETPOST('pid', '0');
            $warehouse_id1 = GETPOST('wid1', '0');
            $warehouse_id2 = GETPOST('wid2', '0');
            $transfer = new StockTransfer($db);
            $stock = $transfer->getStockOneProduct($product_id,$warehouse_id1,$warehouse_id2);
            echo json_encode(array('ok'=>'1','stock'=>$stock));
            break;
    }
    die();
