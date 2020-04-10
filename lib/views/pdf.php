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
        if (empty($page)) $page = 0;

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
        $resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."entrepot");
        if ($resql) {
            while($row = $resql->fetch_assoc()) $depots[$row['rowid']] = $row;
        }
        //echo _var_export($depots,'$depots');die();

    // == load products
        $products = array();
        $resql = $db->query("SELECT rowid,label,price,price_ttc,barcode FROM ".MAIN_DB_PREFIX."product");
        //$resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."product");
        if ($resql) {
            while($row = $resql->fetch_assoc()) $products[$row['rowid']] = $row;
        }
        //echo _var_export($products,'$products');die();

    // == load project
        $project = array();
        $resql = $db->query("SELECT rowid,title FROM ".MAIN_DB_PREFIX."projet WHERE rowid=".$transfer->fk_project);
        if ($resql) {
            $project = $resql->fetch_assoc();
            if (!is_array($project)) $project = array();
        }

    // == prepare logo
        if ( $mysoc->logo && file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small) ) {
            $logo_path = $conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small;
        }else if (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png')){
            $logo_path = DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png';
        }else{
            $logo_path = '';
        }

/***************************************************
 *
 *	View
 *
****************************************************/

?>

<table style="font-size:11px;" border="0">

    <!-- === HEADER === -->

    <tr>
        <td>
            <table border="0">
                <tr>
                    <td style="text-align:left;">
                        <?php if ($logo_path!=''){  ?>
                        <img height="60" src="<?= $logo_path ?>" />
                        <?php } ?>
                    </td>
                    <td><p style="text-align:right;">
                        <?php if (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_01)){ ?>
                            <span style="font-weight:bold;font-size:14px;"><?= $conf->global->STOCKTRANSFERS_MODULE_SETT_01 ?></span>
                            <br />
                        <?php } ?>
                            <span style="font-size:12px;">
                                <?= !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_02) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_02 : $langs->trans('stocktransfersPDF1') ?>:
                                <span style="color:red;font-weight:bold;">#<?= substr('0000'.$transfer->rowid,-4) ?></span>
                            </span>
                            <br />
                            <span style=""><?= html_entity_decode($langs->trans('stocktransfersDate1')).': '. dol_print_date($transfer->date1) ?></span>
                            <br />
                        <?php if ((!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_03) && $conf->global->STOCKTRANSFERS_MODULE_SETT_03=='Y')
                                    || (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_03) && $conf->global->STOCKTRANSFERS_MODULE_SETT_03=='M' && !empty($transfer->shipper))){ ?>
                            <span style=""><?= html_entity_decode($langs->trans('stocktransfersShipper')).': '. $transfer->shipper ?></span>
                            <br />
                        <?php } ?>
                        <?php if ((!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_04) && $conf->global->STOCKTRANSFERS_MODULE_SETT_04=='Y')
                                    || (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_04) && $conf->global->STOCKTRANSFERS_MODULE_SETT_04=='M' && !empty($transfer->n_package))){ ?>
                            <span style=""><?= html_entity_decode($langs->trans('stocktransfersNPackages')).': '. $transfer->n_package ?></span>
                            <br />
                        <?php } ?>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr><td><p>&nbsp;</p></td></tr>

    <!-- === HEADER 2 === -->

    <tr>
        <td>
            <table border="0" cellpadding="7" style="border:none;">
                <tr>
                    <td width="45%" style="border:0.5px #000000 solid;"
                        ><span style="font-size:13px;font-weight:bold;"><?= strtoupper(html_entity_decode($langs->trans('stocktransfersPDF2'))) ?></span><br />
                        <?php
                            if (isset($depots[$transfer->fk_depot1])){
                                if (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_07) && $conf->global->STOCKTRANSFERS_MODULE_SETT_07=='R'){
                                    $depot_label = !empty($depots[$transfer->fk_depot1]['ref']) ? $depots[$transfer->fk_depot1]['ref']
                                        : (!empty($depots[$transfer->fk_depot1]['lieu']) ? $depots[$transfer->fk_depot1]['lieu']
                                        : (!empty($depots[$transfer->fk_depot1]['label']) ? $depots[$transfer->fk_depot1]['label']
                                        : '#'.$depots[$transfer->fk_depot1]['rowid'] ));
                                }else{
                                    $depot_label = !empty($depots[$transfer->fk_depot1]['lieu']) ? $depots[$transfer->fk_depot1]['lieu']
                                        : (!empty($depots[$transfer->fk_depot1]['label']) ? $depots[$transfer->fk_depot1]['label']
                                        : (!empty($depots[$transfer->fk_depot1]['ref']) ? $depots[$transfer->fk_depot1]['ref']
                                        : '#'.$depots[$transfer->fk_depot1]['rowid'] ));
                                }
                                echo $depot_label.'<br />';
                                if (!empty($depots[$transfer->fk_depot1]['address'])){
                                        echo html_entity_decode(nl2br($depots[$transfer->fk_depot1]['address'])).'<br />';
                                }
                                if (!empty($depots[$transfer->fk_depot1]['zip'])){
                                        echo html_entity_decode($depots[$transfer->fk_depot1]['zip']). ' ';
                                }
                                if (!empty($depots[$transfer->fk_depot1]['town'])){
                                        echo html_entity_decode($depots[$transfer->fk_depot1]['town']);
                                }
                            }else{
                                echo html_entity_decode($langs->trans('STwarehouse')).' #'.$transfer->fk_depot1;
                            }
                        ?>
                    </td>
                    <td width="10%" style="text-align:center;">
                        <img src="images/right_grey_arrow.png" />
                    </td>
                    <td width="45%" style="border:0.5px #000000 solid;"
                        ><span style="font-size:13px;font-weight:bold;"><?= strtoupper(html_entity_decode($langs->trans('stocktransfersPDF3'))) ?></span><br />
                        <?php
                            if (isset($depots[$transfer->fk_depot2])){
                                if (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_07) && $conf->global->STOCKTRANSFERS_MODULE_SETT_07=='R'){
                                    $depot_label = !empty($depots[$transfer->fk_depot2]['ref']) ? $depots[$transfer->fk_depot2]['ref']
                                        : (!empty($depots[$transfer->fk_depot2]['lieu']) ? $depots[$transfer->fk_depot2]['lieu']
                                        : (!empty($depots[$transfer->fk_depot2]['label']) ? $depots[$transfer->fk_depot2]['label']
                                        : '#'.$depots[$transfer->fk_depot2]['rowid'] ));
                                }else{
                                    $depot_label = !empty($depots[$transfer->fk_depot2]['lieu']) ? $depots[$transfer->fk_depot2]['lieu']
                                        : (!empty($depots[$transfer->fk_depot2]['label']) ? $depots[$transfer->fk_depot2]['label']
                                        : (!empty($depots[$transfer->fk_depot2]['ref']) ? $depots[$transfer->fk_depot2]['ref']
                                        : '#'.$depots[$transfer->fk_depot2]['rowid'] ));
                                }
                                echo $depot_label.'<br />';
                                if (!empty($depots[$transfer->fk_depot2]['address'])){
                                        echo html_entity_decode(nl2br($depots[$transfer->fk_depot2]['address'])).'<br />';
                                }
                                if (!empty($depots[$transfer->fk_depot2]['zip'])){
                                        echo html_entity_decode($depots[$transfer->fk_depot2]['zip']). ' ';
                                }
                                if (!empty($depots[$transfer->fk_depot2]['town'])){
                                        echo html_entity_decode($depots[$transfer->fk_depot2]['town']);
                                }
                            }else{
                                echo html_entity_decode($langs->trans('STwarehouse')).' #'.$transfer->fk_depot2;
                            }
                        ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr><td><p>&nbsp;<br />&nbsp;</p></td></tr>


    <!-- === PRODUCT LIST === -->

    <tr>
        <td>
            <table border="0" cellpadding="2">

                <!-- TABLE HEADER -->

                <tr>
                    <?php if (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_05) && $conf->global->STOCKTRANSFERS_MODULE_SETT_05!='N'){ ?>
                        <td width="59%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:left;"><b><?= html_entity_decode($langs->trans('stocktransfersPDF7')) ?></b></td>
                        <td width="14%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:center;"><b><?= mb_strtoupper(html_entity_decode($langs->trans('STprice'))) ?></b></td>
                        <td width="10%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:center;"><b><?= substr(html_entity_decode($langs->trans('stocktransfersPDF5')),0,5).'.' ?></b></td>
                        <td width="17%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:center;"><b><?= mb_strtoupper(html_entity_decode($langs->trans('STtotal'))) ?></b></td>
                    <?php }else{ ?>
                        <td width="80%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:left;"><b><?= html_entity_decode($langs->trans('stocktransfersPDF7')) ?></b></td>
                        <td width="20%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:center;"><b><?= html_entity_decode($langs->trans('stocktransfersPDF5')) ?></b></td>
                    <?php } ?>
                </tr>

                <!-- TABLE BODY -->

                <?php
                    $total = 0; $n_rows=0;
                    foreach($transfer->products as $p){
                        $n_rows++;
                        $label = !empty($products[$p['id']]) && !empty($products[$p['id']]['label']) ? $products[$p['id']]['label'] : '#'.$p['id'];

                        // = part number / serial code
                            if (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_08) && $conf->global->STOCKTRANSFERS_MODULE_SETT_08!='N'){
                                if ($conf->global->STOCKTRANSFERS_MODULE_SETT_08=='Y' || (!empty($p['b']) && mb_strlen($p['b'])>1)){
                                        $label = '('.$p['b'].') '.$label;
                                }
                            }

                        // = barcode
                            if (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_09) && $conf->global->STOCKTRANSFERS_MODULE_SETT_09!='N'){
                                if ($conf->global->STOCKTRANSFERS_MODULE_SETT_08=='Y' ||
                                        (!empty($products[$p['id']]) && !empty($products[$p['id']]['barcode']) && mb_strlen($products[$p['id']]['barcode'])>1)){
                                        $label = '['.$products[$p['id']]['barcode'].'] '.$label;
                                }
                            }

                        $n = !empty($p['n']) ? floatval($p['n']) : '';
                ?>
                <tr>
                    <?php if (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_05) && $conf->global->STOCKTRANSFERS_MODULE_SETT_05!='N'){
                        $price = ''; $subtotal = '';
                        if ($conf->global->STOCKTRANSFERS_MODULE_SETT_05=='Y'){
                            $price = !empty($products[$p['id']]) && !empty($products[$p['id']]['price']) ? floatval($products[$p['id']]['price']) : '';
                        }else if ($conf->global->STOCKTRANSFERS_MODULE_SETT_05=='T'){
                            $price = !empty($products[$p['id']]) && !empty($products[$p['id']]['price_ttc']) ? floatval($products[$p['id']]['price_ttc']) : '';
                        }
                        $subtotal = $price!='' && $n!='' ? $price * $n : '';
                        if ($subtotal!='') $total += $subtotal; else $total = '';
                    ?>
                        <td width="59%" style="border:0.5px #000000 solid;text-align:left;"><?= $label ?></td>
                        <td width="14%" style="border:0.5px #000000 solid;text-align:right;"><?= $price!='' ? _price($price) : '&nbsp;' ?></td>
                        <td width="10%" style="border:0.5px #000000 solid;text-align:right;"><?= $n!='' ? $n : '&nbsp;' ?></td>
                        <td width="17%" style="border:0.5px #000000 solid;text-align:right;"><?= $subtotal!='' ? _price($subtotal) : '&nbsp;' ?></td>
                    <?php }else{ ?>
                        <td width="80%" style="border:0.5px #000000 solid;text-align:left;"><?= $label ?></td>
                        <td width="20%" style="border:0.5px #000000 solid;text-align:center;"><?= $n!='' ? $n : '&nbsp;' ?></td>
                    <?php } ?>
                </tr>
                <?php } ?>

                <!-- EMPTY LINES -->

                <?php
                    if ($n_rows<20){
                    for($ii=0 ; $ii < (20 - count($transfer->products)); $ii++){ ?>

                <tr>
                    <?php if (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_05) && $conf->global->STOCKTRANSFERS_MODULE_SETT_05!='N'){ ?>
                        <td width="59%" style="border:0.5px #000000 solid;text-align:left;">&nbsp;</td>
                        <td width="14%" style="border:0.5px #000000 solid;text-align:center;">&nbsp;</td>
                        <td width="10%" style="border:0.5px #000000 solid;text-align:center;">&nbsp;</td>
                        <td width="17%" style="border:0.5px #000000 solid;text-align:center;">&nbsp;</td>
                    <?php }else{ ?>
                        <td width="80%" style="border:0.5px #000000 solid;text-align:center;">&nbsp;</td>
                        <td width="20%" style="border:0.5px #000000 solid;text-align:center;">&nbsp;</td>
                    <?php } ?>
                </tr>

                <?php }} ?>

                <!-- TABLE FOOTER -->

                <?php if (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_05) && $conf->global->STOCKTRANSFERS_MODULE_SETT_05!='N'){ ?>
                <tr>
                    <td width="59%" style="border-left:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:left;"
                        ><b><?= html_entity_decode($langs->trans('STtotal')) ?></b></td>
                    <td width="41%" style="border-right:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:right;"
                        ><?= $total!='' ? _price($total) : '&nbsp;' ?></td>
                </tr>
                <?php } ?>
            </table>
        </td>
    </tr>


    <!-- === SIGNATURES === -->

    <tr><td><p>&nbsp;<br />&nbsp;</p><p>&nbsp;<br />&nbsp;</p></td></tr>

    <tr>
        <td>
            <table border="0" cellpadding="5">
                <tr>
                    <td style="text-align:center;">
                        <?php if (!empty($conf->global->{'STOCKTRANSFERS_MODULE_SETT_061'})){ ?>
                        _____________________
                        <br /><b><?= html_entity_decode($conf->global->{'STOCKTRANSFERS_MODULE_SETT_061'}) ?></b>
                        <?php } ?>
                    </td>
                    <td style="text-align:center;">
                        <?php if (!empty($conf->global->{'STOCKTRANSFERS_MODULE_SETT_062'})){ ?>
                        _____________________
                        <br /><b><?= html_entity_decode($conf->global->{'STOCKTRANSFERS_MODULE_SETT_062'}) ?></b>
                        <?php } ?>
                    </td>
                    <td style="text-align:center;">
                        <?php if (!empty($conf->global->{'STOCKTRANSFERS_MODULE_SETT_063'})){ ?>
                        _____________________
                        <br /><b><?= html_entity_decode($conf->global->{'STOCKTRANSFERS_MODULE_SETT_063'}) ?></b>
                        <?php } ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr><td><p>&nbsp;<br />&nbsp;</p></td></tr>


</table>
