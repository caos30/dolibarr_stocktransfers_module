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
 *	\file       htdocs/stocktransfers/lib/views/pdf.php
 *      \defgroup   Stocktransfers Stock Transfers
 *      \brief      View for build de PDF content of the delivery note
 *      \version    v 1.0 2017/11/20
 */

    // == ACTIVATE the ERROR reporting
    //ini_set('display_errors',1);ini_set('display_startup_errors',1);error_reporting(-1);
    
/***************************************************
 * 
 *	Prepare data
 * 
****************************************************/

    // == misc
        $now=dol_now();
        $socid = $user->societe_id > 0 ? $user->societe_id : 0;
                
        $form = new Form($db);
        $formproduct = new FormProduct($db);
        if (!empty($conf->projet->enabled))  
            $formproject = new FormProjets($db);
        else 
            $formproject = null;
        $productstatic = new Product($db);
        //$transfer = new StockTransfer($db);

    // == set order and limit for queries
        $sortfield = GETPOST('sortfield','alpha');
        $sortorder = GETPOST('sortorder','alpha');
        $page = GETPOST('page','int');
        if (!$sortfield) {
            $sortfield = 'p.ref';
        }
        if (!$sortorder) {
            $sortorder = 'ASC';
        }
        $limit = GETPOST('limit') ? GETPOST('limit','int') : $conf->liste_limit;
        $offset = $limit * $page ;
        
        if (! empty($conf->global->STOCK_SUPPORTS_SERVICES)) $filtertype='';
        $limit = $conf->global->PRODUIT_LIMIT_SIZE <= 0 ? '' : $conf->global->PRODUIT_LIMIT_SIZE;

    // == load depots
        $depots = array();
        $resql = $db->query("SELECT rowid,label,town,address FROM ".MAIN_DB_PREFIX."entrepot");
        if ($resql) {
            while($row = $resql->fetch_assoc()) $depots[$row['rowid']] = $row;
        }
        
    // == load products
        $products = array();
        $resql = $db->query("SELECT rowid,label FROM ".MAIN_DB_PREFIX."product");
        if ($resql) {
            while($row = $resql->fetch_assoc()) $products[$row['rowid']] = $row;
        }
        
    // == load project
        $project = array();
        $resql = $db->query("SELECT rowid,title FROM ".MAIN_DB_PREFIX."projet WHERE rowid=".$transfer->fk_project);
        if ($resql) {
            $project = $resql->fetch_assoc();
            if (!is_array($project)) $project = array();
        }

    // == prepare logo
        if ( $mysoc->logo && file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small) ) {
            $logo64 = base64_encode(file_get_contents($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small));
        }else if (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png')){
            $logo64 = base64_encode(file_get_contents(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png'));
        }else{
            $logo64 = '';
        }

        
/***************************************************
 * 
 *	View
 * 
****************************************************/
        
?>

<table style="font-size:12px;" border="0" cellpadding="0">
    
    <!-- === HEADER === -->
    
    <tr>
        <td>
            <table border="0" cellpadding="5">
                <tr>
                    <td style="text-align:left;" rowspan="2" width="30%">
                        <?php if (!empty($logo64)){ ?>
                        <img height="60" src="data:image/jpg;base64,<?= $logo64 ?>//Z" />
                        <?php } ?>
                    </td>
                    <td width="70%">
                        <table border="0" cellpadding="0">
                            <tr>
                                <td width="30%">&nbsp;</td>
                                <td width="70%">
                                    <table border="1" cellpadding="5">
                                        <tr>
                                            <td style="text-align:center;font-size:18px;"><?= $langs->trans('stocktransfersPDF1') ?></td>
                                            <td style="text-align:center;font-size:20px;color:red;font-weight:bold;"># <?= substr('0000'.$transfer->rowid,-4) ?></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table border="0" width="100%">
                            <tr>
                                <td style="text-align:right;font-weight:bold;" width="25%"><?= mb_strtoupper(html_entity_decode($langs->trans('stocktransfersPDF2'))).': ' ?></td>
                                <td style="text-align:left;" width="75%">
                            <?php   if (isset($depots[$transfer->fk_depot1])){ 
                                        echo '['.$depots[$transfer->fk_depot1]['label'].'] '.$depots[$transfer->fk_depot1]['address'].' ('.$depots[$transfer->fk_depot1]['town'].')';
                                    }else{
                                        echo $langs->trans('Warehouse').' #'.$transfer->fk_depot1;
                                    }
                            ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align:right;font-weight:bold;" width="25%"><?= mb_strtoupper(html_entity_decode($langs->trans('stocktransfersPDF3'))).': ' ?></td>
                                <td style="text-align:left;" width="75%">
                            <?php   if (isset($depots[$transfer->fk_depot2])){ 
                                        echo '['.$depots[$transfer->fk_depot2]['label'].'] '.$depots[$transfer->fk_depot2]['address'].' ('.$depots[$transfer->fk_depot2]['town'].')';
                                    }else{
                                        echo $langs->trans('Warehouse').' #'.$transfer->fk_depot2;
                                    }
                            ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>        
        </td>
    </tr>
    
    <tr><td><p>&nbsp;</p></td></tr>
    
    <!-- === HEADER 2 === -->
    
    <tr>
        <td>
            <table border="1" cellpadding="3">
                <tr>
                    <td style="text-align:center;"><b><?= strtoupper(html_entity_decode($langs->trans('stocktransfersPDF4'))) ?></b></td>
                    <td style="text-align:center;"><b><?= strtoupper(html_entity_decode($langs->trans('stocktransfersDate1'))) ?></b></td>
                    <td style="text-align:center;"><b><?= strtoupper(html_entity_decode($langs->trans('stocktransfersShipper'))) ?></b></td>
                    <td style="text-align:center;"><b><?= strtoupper(html_entity_decode($langs->trans('stocktransfersNPackages'))) ?></b></td>
                </tr>
                <tr>
                    <td style="text-align:center;"><?= !empty($project['title']) ? $project['title'] : (!empty($transfer->fk_project) ? $langs->trans('stocktransfersProject').' #'.$transfer->fk_project : '') ?></td>
                    <td style="text-align:center;"><?= $transfer->date1 ?></td>
                    <td style="text-align:center;"><?= $transfer->shipper ?></td>
                    <td style="text-align:center;"><?= $transfer->n_package ?></td>
                </tr>
            </table>
        </td>
    </tr>

    <tr><td><p>&nbsp;<br />&nbsp;</p></td></tr>
    
    
    <!-- === PRODUCT LIST === -->
    
    <tr>
        <td>
            <table border="1" cellpadding="2">
                <tr>
                    <td width="15%" style="text-align:center;"><b><?= html_entity_decode($langs->trans('stocktransfersPDF5')) ?></b></td>
                    <td width="25%" style="text-align:center;"><b><?= html_entity_decode($langs->trans('stocktransfersPDF6')) ?></b></td>
                    <td width="60%" style="text-align:center;"><b><?= html_entity_decode($langs->trans('stocktransfersPDF7')) ?></b></td>
                </tr>
                <?php foreach($transfer->products as $p){ ?>
                <tr>
                    <td width="15%" style="text-align:center;"><?= intval($p['n']) ?></td>
                    <td width="25%" style="text-align:center;"><?= isset($p['b']) ? $p['b'] : '&nbsp;' ?></td>
                    <td width="60%" style="text-align:left;"><?= $products[$p['id']]['label'] ?></td>
                </tr>
                <?php } ?>
                
                <?php for($ii=0 ; $ii < (20 - count($transfer->products)); $ii++){ ?>
                <tr>
                    <td width="15%" style="text-align:center;">&nbsp;</td>
                    <td width="25%" style="text-align:center;">&nbsp;</td>
                    <td width="60%" style="text-align:center;">&nbsp;</td>
                </tr>
                <?php } ?>
            </table>
        </td>
    </tr>

    
    <!-- === HEADER 2 === -->
    
    <tr><td><p>&nbsp;<br />&nbsp;</p><p>&nbsp;<br />&nbsp;</p></td></tr>
    
    <tr>
        <td>
            <table border="0" cellpadding="5">
                <tr>
                    <td style="text-align:center;">
                        _____________________
                        <br /><b><?= html_entity_decode($langs->trans('stocktransfersPDF8')) ?></b></td>
                    <td style="text-align:center;">
                        _____________________
                        <br /><b><?= html_entity_decode($langs->trans('stocktransfersPDF9')) ?></b></td>
                    <td style="text-align:center;">
                        _____________________
                        <br /><b><?= html_entity_decode($langs->trans('stocktransfersPDF10')) ?></b></td>
                </tr>
            </table>
        </td>
    </tr>

    <tr><td><p>&nbsp;<br />&nbsp;</p></td></tr>
    
    
</table>


