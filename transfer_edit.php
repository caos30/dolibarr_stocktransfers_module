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
 *	\file       htdocs/stocktransfers/transfer_edit.php
 *      \defgroup   stocktransfers Module Stock transfers
 *      \brief      Edition of a transfer
 *      \version    v 1.0 2017/11/20
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once STOCKTRANSFERS_MODULE_DOCUMENT_ROOT.'/lib/stocktransfers_transfer.class.php';

if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

$langs->load("products");
$langs->load("stocks");
$langs->load("orders");
$langs->load("productbatch");
$langs->load("projects");
$langs->load("stocktransfers");

include_once("./lib/stocktransfers.lib.php");
include_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$user->getrights('stocktransfers');
$langs->load("stocktransfers@stocktransfers");

// == Security check
    $result = restrictedArea($user,'stock&produit');

// == Get parameters
    $action = GETPOST('action','alpha');
    $transfer_id = GETPOST('rowid', '0');

    $id_product = GETPOST('productid', 'int');
    $batch = GETPOST('batch');
    $qty = GETPOST('qty');

// == data object
    global $transfer;
    $transfer = new StockTransfer($db);
    if ($transfer_id > 0) {
        $ret = $transfer->fetch($transfer_id);
    }

// == other data
    $now = dol_now();
    $listofdata=array();
    if (! empty($_SESSION['massstockmove'])) $listofdata=json_decode($_SESSION['massstockmove'],true);

// == SESSION MESSAGES (this is from this module)
    if (!empty($_SESSION['EventMessages']) && is_array($_SESSION['EventMessages']) && count($_SESSION['EventMessages'])>0){
        foreach($_SESSION['EventMessages'] as $arr){
            setEventMessages($arr[0],$arr[1],$arr[2]);
        }
    }
    $_SESSION['EventMessages'] = array();

    //if (!empty($_POST)){     echo _var_export($_GET,'$_GET')._var_export($_POST,'$_POST')._var_export($_FILES,'$_FILES'); }

/***************************************************
 *
 *	Actions
 *
****************************************************/

if ($action == 'delete_transfer') {

    if (!$transfer->rowid || $transfer->status != '0'){
        $_SESSION['EventMessages'][] = array("stocktransfersErrorMsg04",null,'errors');
    }else{
        $transfer->delete($user);
        $_SESSION['EventMessages'][] = array("RecordDeleted",null,'mesgs');
    }

    // == redirect to list
    header("Location: transfer_list.php?mainmenu=products&leftmenu="); die();

}else if ($action == 'save_card') {

    // == prepare transfer card

        $transfer->fk_user_author = $user->id;
        if (!empty($_POST['label']))
            $transfer->label = $_POST['label'];
        else if (empty($transfer->label))
            $transfer->label = $langs->trans("StockTransfer").' '.dol_print_date($now,'%Y-%m-%d %H:%M');

        if (isset($_POST['fk_project']))
        $transfer->fk_project = intval($_POST['fk_project']);

        if (isset($_POST['inventorycode']))
        $transfer->inventorycode = $_POST['inventorycode'];

        if (isset($_POST['fk_depot1']))
        $transfer->fk_depot1 = intval($_POST['fk_depot1']);

        if (isset($_POST['fk_depot2']))
        $transfer->fk_depot2 = intval($_POST['fk_depot2']);

        if (!empty($_POST['date1year'])) // == d/m/Y -> %Y%m%d%H%M%S
        $transfer->date1 = $_POST['date1year'].substr('0'.$_POST['date1month'],-2).substr('0'.$_POST['date1day'],-2);

        if (!empty($_POST['date2year'])) // == d/m/Y -> %Y%m%d%H%M%S
        $transfer->date2 = $_POST['date2year'].substr('0'.$_POST['date2month'],-2).substr('0'.$_POST['date2day'],-2);

        if (isset($_POST['shipper']))
        $transfer->shipper = $_POST['shipper'];

        if (isset($_POST['n_package']))
        $transfer->n_package = $_POST['n_package'];

        if (isset($_POST['status']))
            $transfer->status = $_POST['status'];

    // == run query on database
        $new = $transfer->rowid > 0 ? false : true ;
        if ($new)
            $result = $transfer->create(NULL);
        else
            $result = $transfer->update();

    // == there is a change of status, then we must to add/remove records about stock movements

        // = the transfer is being stated as SENT
        if ($_POST['old_status']=='0' && $_POST['status']=='1'){

            $result = $transfer->create_stock_movements('1');

        // = the transfer is being stated as RECEIVED
        }else if ($_POST['old_status']=='1' && $_POST['status']=='2'){

            $result = $transfer->create_stock_movements('2');

        // = the transfer is being stated AGAIN as DRAFT, from RECEIVED
        }else if ($_POST['old_status']=='2' && $_POST['status']=='0'){

            $reverse = 1;
            $result = $transfer->create_stock_movements('2',$reverse);
            $result = $transfer->create_stock_movements('1',$reverse);

        // = the transfer is being stated AGAIN as DRAFT, from SENT
        }else if ($_POST['old_status']=='1' && $_POST['status']=='0'){

            $reverse = 1;
            $result = $transfer->create_stock_movements('1',$reverse);

        }

    // == response to user

        if ($result < 0){
            dol_print_error($db,$transfer->error);
        }else if ($new){
            $_SESSION['EventMessages'][] = array("RecordCreatedSuccessfully",null,'mesgs');
        }else{
            $_SESSION['EventMessages'][] = array("RecordModifiedSuccessfully",null,'mesgs');
        }

    // == redirect to list
        header("Location: transfer_edit.php?mainmenu=products&leftmenu=&rowid=".$transfer->rowid); die();


}else if ($action == 'add_line') {

    if (empty($_POST['add_pid'])){
        $_SESSION['EventMessages'][] = array($langs->trans("ErrorGlobalVariableUpdater2",'product'),null,'errors');
    }else if (empty($_POST['n'])){
        $_SESSION['EventMessages'][] = array($langs->trans("ErrorGlobalVariableUpdater2",'n'),null,'errors');
    }else{

        $transfer->products[$_POST['add_pid']] = array(
            'id'=>$_POST['add_pid'],
            'n'=>intval($_POST['n']),
            'b'=>isset($_POST['batch']) ? $_POST['batch'] : '');
        $transfer->n_prducts = count($transfer->products);
        $result = $transfer->update();

        if ($result < 0)
            dol_print_error($db,$transfer->error);
        else
            $_SESSION['EventMessages'][] = array("RecordModifiedSuccessfully",null,'mesgs');
    }

    // == redirect to list
        header("Location: transfer_edit.php?mainmenu=products&leftmenu=&rowid=".$transfer->rowid); die();

}else if ($action == 'del_line') {

    if (empty($_POST['del_pid'])){
        $_SESSION['EventMessages'][] = array($langs->trans("ErrorGlobalVariableUpdater2",'product'),null,'errors');
    }else{

        $transfer->products[$_POST['del_pid']];
        $new_products = array();
        foreach($transfer->products as $pid=>$p){
            if ($pid != trim($_POST['del_pid'])) $new_products[$pid] = $p;
        }
        $transfer->products = $new_products;

        $result = $transfer->update();

        if ($result < 0) {
            dol_print_error($db,$transfer->error);
        }else{
            $_SESSION['EventMessages'][] = array("DeleteLine",null,'mesgs');
        }
    }

    // == redirect to list
        header("Location: transfer_edit.php?mainmenu=products&leftmenu=&rowid=".$transfer->rowid); die();

}


/***************************************************
 *
 *	View
 *
****************************************************/

print _render_view('edit',array('transfer'=>$transfer));
