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

    // == load module settings
        $fontsize      = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_10) ? intval($conf->global->STOCKTRANSFERS_MODULE_SETT_10) : 10;
        $fontfamily    = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_11) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_11 : 'serif';
        $warehouses_AB = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_13) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_13 : 'A-B';
		$show_logo     = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_15) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_15 : 'Y';

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

    // == load products
        $products = array();
        $resql = $db->query("SELECT rowid,ref,label,price,price_ttc,barcode FROM ".MAIN_DB_PREFIX."product");
        //$resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."product");
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
		if ($show_logo == 'N'){
			$logo_path = '';
		}else if ($mysoc->logo && file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small) ) {
			$logo_path = $conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small;
        }else if (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png')){
            $logo_path = DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png';
        }else{
            $logo_path = '';
        }

	// == prepare languages translated for this module and get the translation for some keys
		
		$PDF_langcode = $langs->getDefaultLang();
		$ex_lang = explode('_',$PDF_langcode);
		$PDFlang = $ex_lang[0];
		
		$languages = scandir(STOCKTRANSFERS_MODULE_DOCUMENT_ROOT.'/langs');
		$multi_translations = _multi_translation(array('STsettLab01def','stocktransfersPDF1','stocktransfersPDF8','stocktransfersPDF9','stocktransfersPDF10'),$languages);
		//echo var_export($multi_translations,true);

	// == preferences to render: reference, serial code, barcode
		$show_prices     = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_05) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_05 : 'N';
		$show_reference  = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_18) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_18 : 'Y';
		$show_serialcode = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_08) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_08 : 'N';
		$show_barcode    = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_09) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_09 : 'N';
		
		// deprecated (to keep compatibility with old settings "if empty value then show on DESC column"=M)
		if ($show_serialcode=='M') $show_serialcode = 'Y';
		if ($show_barcode   =='M') $show_barcode    = 'Y';
		
		// calculate the % occupied by the visible columns, because this % of space must be substracted to the DESCRIPTION COLUMN
		$description_col_subtract = 0;
		if ($show_reference =='Y2') $description_col_subtract += 15;
		if ($show_serialcode=='Y2') $description_col_subtract += 15;
		if ($show_barcode   =='Y2') $description_col_subtract += 15;
		$description_col = $show_prices=='N' ? (80 - $description_col_subtract) : (59 - $description_col_subtract);
	

/***************************************************
 *
 *	View
 *
****************************************************/

?>

<table style="font-size:<?= $fontsize ?>px;font-family:<?= $fontfamily ?>;" border="0">

    <!-- === HEADER === -->

    <tr>
        <td>
            <table border="0">
                <tr>
                    <td style="text-align:left;">
                        <?php if ($logo_path!=''){  ?>
                        <img height="60" src="@<?= base64_encode(file_get_contents($logo_path))?>" />
                        <?php } ?>
                    </td>
                    <td><p style="text-align:right;">
                        <?php 
							$s_translations = _json_decode_translation('STOCKTRANSFERS_MODULE_SETT_01',$defaultLang);
							if (!empty($s_translations[$PDFlang])){ ?>
                            <span style="font-weight:bold;font-size:<?= $fontsize + 3 ?>px;"><?= $s_translations[$PDFlang] ?></span>
                            <br />
                        <?php } ?>
							<?php $s_translations = _json_decode_translation('STOCKTRANSFERS_MODULE_SETT_02',$defaultLang); ?>
                            <span style="font-size:<?= $fontsize + 1 ?>px;">
                                <?= !empty($s_translations[$PDFlang]) ? $s_translations[$PDFlang] : ( !empty($multi_translations['stocktransfersPDF1'][$PDFlang]) ? $multi_translations['stocktransfersPDF1'][$PDFlang] : $langs->trans('stocktransfersPDF1')) ?>:
                                <span style="color:red;font-weight:bold;">#<?= substr('0000'.$transfer->rowid,-4) ?></span>
                            </span>
                            <?php 
								  $s_translations = _json_decode_translation('STOCKTRANSFERS_MODULE_SETT_17',$defaultLang);
								  $date_format = isset($s_translations[$PDFlang]) ? $s_translations[$PDFlang] : (isset($s_translations[$defaultLang]) ? $s_translations[$defaultLang] : '');
                            ?>
                            <br />
                            <span style=""><?= $langs->trans('stocktransfersDate1').': &nbsp;'. dol_print_date($transfer->date1 .' 13:01',$date_format) ?></span> 
                            <br />
                        <?php if ((!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_03) && $conf->global->STOCKTRANSFERS_MODULE_SETT_03=='Y')
                                    || (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_03) && $conf->global->STOCKTRANSFERS_MODULE_SETT_03=='M' && !empty($transfer->shipper))){ ?>
                            <span style=""><?= $langs->trans('stocktransfersShipper').': '. $transfer->shipper ?></span>
                            <br />
                        <?php } ?>
                        <?php if ((!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_04) && $conf->global->STOCKTRANSFERS_MODULE_SETT_04=='Y')
                                    || (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_04) && $conf->global->STOCKTRANSFERS_MODULE_SETT_04=='M' && !empty($transfer->n_package))){ ?>
                            <span style=""><?= $langs->trans('stocktransfersNPackages').': '. $transfer->n_package ?></span>
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

    <?php
        if ($warehouses_AB == 'A-B'){
            $depot1 = $transfer->fk_depot1;
            $depot2 = $transfer->fk_depot2;
            $tit1 = 'stocktransfersPDF2';
            $tit2 = 'stocktransfersPDF3';
        }else{
            $depot1 = $transfer->fk_depot2;
            $depot2 = $transfer->fk_depot1;
            $tit1 = 'stocktransfersPDF3';
            $tit2 = 'stocktransfersPDF2';
        }
    ?>

    <tr>
        <td>
            <table border="0" cellpadding="7" style="border:none;">
                <tr>
                    <td width="45%" style="padding:0px;"
                        ><?= ucfirst(mb_strtolower($langs->trans($tit1))) ?>:</td>
                    <td width="10%" style="text-align:center;">&nbsp;</td>
                    <td width="45%" style="padding:0px;"
                        ><?= ucfirst(mb_strtolower($langs->trans($tit2))) ?>:</td>
                </tr>
                <tr>
                    <td width="45%" style="border:0.5px #000000 solid;" bgcolor="#e6e6e6"
                        ><?php
                            if (isset($depots[$depot1])){
                                if (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_07) && $conf->global->STOCKTRANSFERS_MODULE_SETT_07=='R'){
                                    $depot_label = !empty($depots[$depot1]['ref']) ? $depots[$depot1]['ref']
                                        : (!empty($depots[$depot1]['lieu']) ? $depots[$depot1]['lieu']
                                        : (!empty($depots[$depot1]['label']) ? $depots[$depot1]['label']
                                        : '#'.$depots[$depot1]['rowid'] ));
                                }else{
                                    $depot_label = !empty($depots[$depot1]['lieu']) ? $depots[$depot1]['lieu']
                                        : (!empty($depots[$depot1]['label']) ? $depots[$depot1]['label']
                                        : (!empty($depots[$depot1]['ref']) ? $depots[$depot1]['ref']
                                        : '#'.$depots[$depot1]['rowid'] ));
                                }
                                echo '<b>'.$depot_label.'</b><br />';
                                if (!empty($depots[$depot1]['address'])){
                                        echo nl2br($depots[$depot1]['address']).'<br />';
                                }
                                if (!empty($depots[$transfer->fk_depot1]['zip'])){
                                        echo $depots[$depot1]['zip']. ' ';
                                }
                                if (!empty($depots[$transfer->fk_depot1]['town'])){
                                        echo $depots[$depot1]['town'];
                                }
                            }else{
                                echo $langs->trans('STwarehouse').' #'.$depot1;
                            }
                        ?>
                    </td>
                    <td width="10%" style="text-align:center;">
                        &nbsp;<img src="images/right_grey_arrow_<?= $warehouses_AB ?>.png" />
                    </td>
                    <td width="45%" style="border:0.5px #000000 solid;" bgcolor="#e6e6e6"
                        ><?php
                            if (isset($depots[$depot2])){
                                if (!empty($conf->global->STOCKTRANSFERS_MODULE_SETT_07) && $conf->global->STOCKTRANSFERS_MODULE_SETT_07=='R'){
                                    $depot_label = !empty($depots[$depot2]['ref']) ? $depots[$depot2]['ref']
                                        : (!empty($depots[$depot2]['lieu']) ? $depots[$depot2]['lieu']
                                        : (!empty($depots[$depot2]['label']) ? $depots[$depot2]['label']
                                        : '#'.$depots[$depot2]['rowid'] ));
                                }else{
                                    $depot_label = !empty($depots[$depot2]['lieu']) ? $depots[$depot2]['lieu']
                                        : (!empty($depots[$depot2]['label']) ? $depots[$depot2]['label']
                                        : (!empty($depots[$depot2]['ref']) ? $depots[$depot2]['ref']
                                        : '#'.$depots[$depot2]['rowid'] ));
                                }
                                echo '<b>'.$depot_label.'</b><br />';
                                if (!empty($depots[$depot2]['address'])){
                                        echo nl2br($depots[$depot2]['address']).'<br />';
                                }
                                if (!empty($depots[$depot2]['zip'])){
                                        echo $depots[$depot2]['zip']. ' ';
                                }
                                if (!empty($depots[$depot2]['town'])){
                                        echo $depots[$depot2]['town'];
                                }
                            }else{
                                echo $langs->trans('STwarehouse').' #'.$depot2;
                            }
                        ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- === NOTE === -->

    <?php if (isset($transfer->pdf_note) && !empty($transfer->pdf_note) && trim(strip_tags($transfer->pdf_note))!=''){ ?>

    <tr>
        <td>
            <table border="0" cellpadding="7" style="border:none;">
                <tr><td><?= $langs->trans('STnote') ?>:</td></tr>
                <tr><td style="border:0.2px #000000 solid;"><?= nl2br($transfer->pdf_note) ?></td></tr>
            </table>
        </td>
    </tr>

    <?php } ?>

    <tr><td><p>&nbsp;<br />&nbsp;</p></td></tr>

    <!-- === PRODUCT LIST === -->

    <tr>
        <td>
            <table border="0" cellpadding="2">

                <!-- TABLE HEADER -->

                <tr>
					<?php if ($show_reference=='Y2'){ ?>
                        <td width="15%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:center;"
                            ><b><?= $langs->trans('STsettLab07opt2') ?></b></td>
					<?php } ?>
					<?php if ($show_serialcode=='Y2'){ ?>
                        <td width="15%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:center;"
                            ><b><?= $langs->trans('STBatch') ?></b></td>
					<?php } ?>
					<?php if ($show_barcode=='Y2'){ ?>
                        <td width="15%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:center;"
                            ><b><?= $langs->trans('STpdfBarCode') ?></b></td>
					<?php } ?>
                    <?php if ($show_prices!='N'){ ?>
                        <td width="<?= $description_col ?>%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:left;"
                            ><b><?= ucfirst(mb_strtolower($langs->trans('stocktransfersPDF7'))) ?></b></td>
                        <td width="14%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:center;"
                            ><b><?= ucfirst(mb_strtolower(mb_strtoupper($langs->trans('STprice')))) ?></b></td>
                        <td width="10%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:center;"
                            ><b><?= ucfirst(mb_strtolower(substr($langs->trans('stocktransfersPDF5'),0,5))).'.' ?></b></td>
                        <td width="17%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:center;"
                            ><b><?= ucfirst(mb_strtolower(mb_strtoupper($langs->trans('STtotal')))) ?></b></td>
                    <?php }else{ ?>
                        <td width="<?= $description_col ?>%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:left;"
                            ><b><?= ucfirst(mb_strtolower($langs->trans('stocktransfersPDF7'))) ?></b></td>
                        <td width="20%" style="border:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:center;"
                            ><b><?= ucfirst(mb_strtolower($langs->trans('stocktransfersPDF5'))) ?></b></td>
                    <?php } ?>
                </tr>

                <!-- TABLE BODY -->

                <?php
                    $total = 0; $n_rows=0; $n_units=0;
                    foreach($transfer->products as $p){
                        $n_rows++;
                        $label = ''; 
                        
						// = product reference, serial number, barcode
							$p_reference  = strip_tags(isset($products[$p['id']]) && !empty($products[$p['id']]['ref']) && mb_strlen($products[$p['id']]['ref'])>1 ? $products[$p['id']]['ref'] : '');
							$p_serialcode = strip_tags(!empty($p['b']) && mb_strlen($p['b'])>1 ? $p['b'] : '');
							$p_barcode    = strip_tags(isset($products[$p['id']]) && !empty($products[$p['id']]['barcode']) && mb_strlen($products[$p['id']]['barcode'])>1 ? $products[$p['id']]['barcode'] : '');
                        
                        // = label
						// _ ex: [100010011101010] (lot-x) PROD-01 - Product 01  // <--- [SERIAL CODE] (LOT) REF - LABEL

							$a_label = array();
							if ($show_barcode   =='Y' && $p_barcode!='')    $a_label[] = '['.$p_barcode.']';
							if ($show_serialcode=='Y' && $p_serialcode!='') $a_label[] = '('.$p_serialcode.')';
							if ($show_reference =='Y' && $p_reference!='')  $a_label[] = $p_reference;
							if (!empty($products[$p['id']]['label']))        $a_label[] = $products[$p['id']]['label'];
							$label = trim(implode(' - ',$a_label));
							if ($label=='') $label =  '#'.$p['id'];

						// = units
							$n = !empty($p['n']) ? floatval($p['n']) : '';
							if ($n!='') $n_units += $n;
                ?>
                <tr>
					<?php if ($show_reference=='Y2'){ ?>
                        <td width="15%" style="border:0.5px #000000 solid;text-align:center;"><?= $p_reference ?></td>
					<?php } ?>
					<?php if ($show_serialcode=='Y2'){ ?>
                        <td width="15%" style="border:0.5px #000000 solid;text-align:center;"><?= $p_serialcode ?></td>
					<?php } ?>
					<?php if ($show_barcode=='Y2'){ ?>
                        <td width="15%" style="border:0.5px #000000 solid;text-align:center;"><?= $p_barcode ?></td>
					<?php } ?>
                    <?php 
						if ($show_prices!='N'){
							$price = ''; $subtotal = '';
							if ($conf->global->STOCKTRANSFERS_MODULE_SETT_05=='Y'){
								$price = !empty($products[$p['id']]) && !empty($products[$p['id']]['price']) ? floatval($products[$p['id']]['price']) : '';
							}else if ($conf->global->STOCKTRANSFERS_MODULE_SETT_05=='T'){
								$price = !empty($products[$p['id']]) && !empty($products[$p['id']]['price_ttc']) ? floatval($products[$p['id']]['price_ttc']) : '';
							}
                        $subtotal = $price!='' && $n!='' ? $price * $n : '';
                        if ($subtotal!='') $total += $subtotal;
                    ?>
                        <td width="<?= $description_col ?>%" style="border:0.5px #000000 solid;text-align:left;"><?php
                                echo "<p style=\"padding:0;margin:0;\">".$label;
                                if (!empty($p['m']) && trim(strip_tags($p['m']))!=''){
                                    echo "<br /><span style=\"font-size:0.9em;font-family:monospace;\">".nl2br($p['m'])."</span>";
                                }
                                echo "</p>";
					  ?></td>
                        <td width="14%" style="border:0.5px #000000 solid;text-align:right;"><?= $price!='' ? _price($price) : '&nbsp;' ?></td>
                        <td width="10%" style="border:0.5px #000000 solid;text-align:right;"><?= $n!='' ? _qty($n) : '&nbsp;' ?></td>
                        <td width="17%" style="border:0.5px #000000 solid;text-align:right;"><?= $subtotal!='' ? _price($subtotal) : '&nbsp;' ?></td>
                    <?php }else{ ?>
                        <td width="<?= $description_col ?>%" style="border:0.5px #000000 solid;text-align:left;"><?php
                                echo "<p style=\"padding:0;margin:0;\">".$label;
                                if (!empty($p['m'])){
                                    echo "<br /><span style=\"font-size:0.9em;font-family:monospace;\">".nl2br($p['m'])."</span>";
                                }
                                echo "</p>";
                            ?></td>
                        <td width="20%" style="border:0.5px #000000 solid;text-align:center;"><?= $n!='' ? $n : '&nbsp;' ?></td>
                    <?php } ?>
                </tr>
                <?php } ?>

                <!-- EMPTY LINES -->

                <?php
                    if ($n_rows<20){
                    for($ii=0 ; $ii < (20 - count($transfer->products)); $ii++){ ?>

                <tr>
					<?php if ($show_reference=='Y2'){ ?>
                        <td width="15%" style="border:0.5px #000000 solid;text-align:center;">&nbsp;</td>
					<?php } ?>
					<?php if ($show_serialcode=='Y2'){ ?>
                        <td width="15%" style="border:0.5px #000000 solid;text-align:center;">&nbsp;</td>
					<?php } ?>
					<?php if ($show_barcode=='Y2'){ ?>
                        <td width="15%" style="border:0.5px #000000 solid;text-align:center;">&nbsp;</td>
					<?php } ?>
                    <?php if ($show_prices!='N'){ ?>
                        <td width="<?= $description_col ?>%" style="border:0.5px #000000 solid;text-align:left;">&nbsp;</td>
                        <td width="14%" style="border:0.5px #000000 solid;text-align:center;">&nbsp;</td>
                        <td width="10%" style="border:0.5px #000000 solid;text-align:center;">&nbsp;</td>
                        <td width="17%" style="border:0.5px #000000 solid;text-align:center;">&nbsp;</td>
                    <?php }else{ ?>
                        <td width="<?= $description_col ?>%" style="border:0.5px #000000 solid;text-align:center;">&nbsp;</td>
                        <td width="20%" style="border:0.5px #000000 solid;text-align:center;">&nbsp;</td>
                    <?php } ?>
                </tr>

                <?php }} ?>

                <!-- TABLE FOOTER -->

                <?php if ($show_prices!='N'){ ?>
                    <tr>
                        <td width="64%" style="border-left:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:left;"
                            ><b><?= $langs->trans('STtotal')
                                    ."</b> ".str_replace(array('{n1}','{n2}'),array($n_rows,_qty($n_units)),$langs->trans('STtotalProductsUnits')) ?></td>
                        <td width="36%" style="border-right:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:right;"
                            ><?= $total!='' ? _price($total) : '&nbsp;' ?></td>
                    </tr>
                <?php }else{ ?>
                    <tr>
                        <td width="80%" style="border-left:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:left;"
                            ><b><?= $langs->trans('STtotal')
                                    ."</b> ".str_replace('{n1}',$n_rows,$langs->trans('STtotalProducts')) ?></td>
                        <td width="20%" style="border-right:0.5px #000000 solid;border-top:2px #000000 solid;border-bottom:2px #000000 solid;text-align:center;"
                            ><?= $n_units!='' ? _qty($n_units) : '&nbsp;' ?></td>
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
						<?php $s_translations = _json_decode_translation('STOCKTRANSFERS_MODULE_SETT_061',$defaultLang); ?>
                        <?php if (!empty($s_translations[$PDFlang])){ ?>
                        _____________________
                        <br /><b><?= $s_translations[$PDFlang] ?></b>
                        <?php } ?>
                    </td>
                    <td style="text-align:center;">
						<?php $s_translations = _json_decode_translation('STOCKTRANSFERS_MODULE_SETT_062',$defaultLang); ?>
                        <?php if (!empty($s_translations[$PDFlang])){ ?>
                        _____________________
                        <br /><b><?= $s_translations[$PDFlang] ?></b>
                        <?php } ?>
                    </td>
                    <td style="text-align:center;">
						<?php $s_translations = _json_decode_translation('STOCKTRANSFERS_MODULE_SETT_063',$defaultLang); ?>
                        <?php if (!empty($s_translations[$PDFlang])){ ?>
                        _____________________
                        <br /><b><?= $s_translations[$PDFlang] ?></b>
                        <?php } ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr><td><p>&nbsp;<br />&nbsp;</p></td></tr>


</table>
