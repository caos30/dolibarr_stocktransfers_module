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
 *	\file       htdocs/stocktransfers/lib/views/edit.php
 *      \defgroup   Stocktransfers Stock Transfers
 *      \brief      Page to edit a stock transfer
 *      \version    v 1.0 2017/11/20
 */

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
        $page = empty($page) ? 0 : $page;
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
            while($row = $resql->fetch_assoc()){
				$depots[$row['rowid']] = $row;
				$depots[$row['rowid']]['tit1'] = !empty($row['ref']) ? $row['ref'] : '#'.$row['rowid'];
				$depots[$row['rowid']]['tit2'] = !empty($row['lieu']) ? $row['lieu'] : (!empty($row['label']) ? $row['label'] : '');
				$depots[$row['rowid']]['tit3'] = !empty($row['town']) ? $row['town'] : '';
				//echo _var($row,'$row');
			}
        }

    // == load current stock at all depots, of the products included in this transfer
        $stock = $transfer->getStock();

    // == need to purchase more stock
        $buy_stock = '';
        $depot1_stock = array();
        $depot2_stock = array();
        if ($transfer->status == '0' && count($transfer->products) > 0){
            foreach($transfer->products as $pid => $p){
				// calculate stocks ond departure and destination 
					$depot1_stock[$pid] = !empty($stock[$transfer->fk_depot1][$pid]) ? floatval($stock[$transfer->fk_depot1][$pid]) : 0;
					$depot2_stock[$pid] = !empty($stock[$transfer->fk_depot2][$pid]) ? floatval($stock[$transfer->fk_depot2][$pid]) : 0;
                $missing_stock = floatval($p['n']) - $depot1_stock[$pid];
                if ($missing_stock > 0) {
                    if ($buy_stock!='') $buy_stock .= '_';
                    $buy_stock .= $pid.'-'.$missing_stock;
                }
            }
        }

/***************************************************
 *
 *	View
 *
****************************************************/

    // == browser top title
        $title = $langs->trans('stocktransfersBriefTitle');
        llxHeader('',$title);

    // == misc.
        $moreforfilter = true;
        $var = true;
        $param = '';
        $filtertype=0;
        $b_batch_enabled = $conf->productbatch->enabled
                            && (empty($conf->global->STOCKTRANSFERS_MODULE_SETT_08)
                                || $conf->global->STOCKTRANSFERS_MODULE_SETT_08 != 'N') ? true : false;

	// == include JS library to render HTML tooltips
		if (!empty($conf->use_javascript_ajax)) {
			print "\n".'<!-- Includes JS Footer of Dolibarr -->'."\n";
			$ext = 'layout='.$conf->browser->layout.'&version='.urlencode(DOL_VERSION);
			print '<script src="'.DOL_URL_ROOT.'/core/js/lib_foot.js.php?lang='.$langs->defaultlang.($ext ? '&'.$ext : '').'"></script>'."\n";
		}
?>

<style><?= str_replace(array(" ","\n","\t"),'',file_get_contents(__DIR__.'/styles.css')) ?></style>

<!-- ========= header with section title ========= -->

<?= load_fiche_titre( ($transfer->rowid > 0 ? $langs->trans('stocktransfersTransfer') : $langs->trans('stocktransfersNewTransfer')),
                    '<a href="transfer_list.php?mainmenu=products&leftmenu=" class="button">'
                    .(DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') ? '<i class="fa fa-list"></i>&nbsp; ' : '')
                    .$langs->trans('purchasesMenuTitle3').'</a>',
                    'title_products.png') ?>


<div class='tabBar tabBarWithBottom'>

    <!-- ========= Form with the transfer details (dates, status, project, etc.) ========= -->

    <form action="<?= $_SERVER["PHP_SELF"] ?>" method="POST" name="transfer_card_form" id="transfer_card_form">
        <input type="hidden" name="token" value="<?= newToken() ?>" />
        <input type="hidden" name="rowid" value="<?= !empty($transfer->rowid) ? $transfer->rowid : '' ?>" />
        <input type="hidden" name="action" value="save_card" />
        <input type="hidden" name="status" value="<?= $transfer->status ?>" />
        <input type="hidden" name="old_status" value="<?= $transfer->status ?>" />

        <?php
            $codemove=GETPOST('codemove');
            $labelmovement = GETPOST("label") ? GETPOST('label') : $langs->trans("StockTransfer").' '.dol_print_date($now,'%Y-%m-%d %H:%M');
            if (DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME')){ // we can use fontwawesome icons 
				$ic_warehouse = '<span class="fa fa-box-open"></span>';
			}else{ 
				$ic_warehouse = '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme .'/img/object_company.png" border="0" />';
			}
        ?>

        <div class="sk-container">
            <div class="sk-eight sk-columns">
				<div class="ficheaddleft">
					<table class="border" style="width:100%;">
						<?php if ($transfer->rowid > 0){ ?>
						<tr>
							<td class="titlefield fieldrequired"><?= $langs->trans('STID') ?></td>
							<td>#<?= $transfer->rowid ?></td>
						</tr>
						<?php } ?>
						<?php if (!empty($conf->projet->enabled)) { ?>
						<tr>
							<td class="titlefield"><?= $langs->trans('stocktransfersProject') ?></td>
							<td><?= $formproject->select_projects((empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS) ? $socid :    -1), $transfer->fk_project, 'fk_project', 0, 0, 1, 1) ?></td>
						</tr>
						<?php } ?>
						<tr>
							<td class="titlefield fieldrequired"><?= $langs->trans('WarehouseSource') ?></td>
							<td><?php 
									if ($transfer->status == '0'){
										
										// select control
										echo $formproduct->selectWarehouses($transfer->fk_depot1, 'fk_depot1', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp') ;
										
									}else{
										
										// hidden input
										echo "<input type='hidden' name='fk_depot1' id='fk_depot1' value='".$transfer->fk_depot1 ."' />";
									}
										
									// title & link to warehouse
									if (!empty($transfer->fk_depot1) && !is_null($transfer->fk_depot1)){
								?>
									&nbsp; <a href="<?= DOL_URL_ROOT.'/product/stock/card.php?id='.$transfer->fk_depot1 ?>"><?= $ic_warehouse.' '.$depots[$transfer->fk_depot1]['tit1'] ?></a>
									 · <?= $depots[$transfer->fk_depot1]['tit2'] ?>
									 <?= !empty($depots[$transfer->fk_depot1]['tit3']) ? ' · <em>'.$depots[$transfer->fk_depot1]['tit3'].'</em>' : '' ?>
									 
								 <?php } ?>
							</td>
						</tr>
						<tr>
							<td class="titlefield fieldrequired"><?= $langs->trans('WarehouseTarget') ?></td>
							<td><?php 
									if ($transfer->status == '0'){
										
										// select control
										echo $formproduct->selectWarehouses($transfer->fk_depot2, 'fk_depot2', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp') ;
										
									}else{
										
										// hidden input
										echo "<input type='hidden' name='fk_depot2' id='fk_depot2' value='".$transfer->fk_depot2 ."' />";
									}
										
									// title & link to warehouse & hidden input
									if (!empty($transfer->fk_depot2) && !is_null($transfer->fk_depot2)){
								?>
										&nbsp; <a href="<?= DOL_URL_ROOT.'/product/stock/card.php?id='.$transfer->fk_depot2 ?>"><?= $ic_warehouse.' '.$depots[$transfer->fk_depot2]['tit1'] ?></a>
										 · <?= $depots[$transfer->fk_depot2]['tit2'] ?>
										 <?= !empty($depots[$transfer->fk_depot2]['tit3']) ? ' · <em>'.$depots[$transfer->fk_depot2]['tit3'].'</em>' : '' ?>
										 
								 <?php } ?>
							</td>
						</tr>
						<tr>
							<td class="titlefield"><?= $langs->trans('stocktransfersDate1') ?></td>
							<td><?= $form->select_date(!empty($transfer->date1) ? $db->jdate($transfer->date1) : '','date1',0,0,0,"transfer_card_form",1,1) ?></td>
						</tr>
						<tr>
							<td class="titlefield"><?= $langs->trans('stocktransfersDate2') ?></td>
							<td>
								<?php if ($transfer->status > '0'){ ?>
								<?= $form->select_date(!empty($transfer->date2) ? $db->jdate($transfer->date2): '','date2',0,0,1,"transfer_card_form",1,1) ?></td>
								<?php } ?>
						</tr>
						<tr>
							<td class="titlefield"><?= $langs->trans("stocktransfersShipper") ?></td>
							<td>
								<input type="text" name="shipper" style="width:200px;" maxlength="255" value="<?= dol_escape_htmltag($transfer->shipper) ?>">
							</td>
						</tr>
						<tr>
							<td class="titlefield"><?= $langs->trans("stocktransfersNPackages") ?></td>
							<td>
								<input type="text" name="n_package" style="width:200px;" maxlength="20" value="<?= dol_escape_htmltag($transfer->n_package) ?>">
							</td>
						</tr>
						<tr>
							<td class="titlefield"><?= $langs->trans("STinventorycode") ?></td>
							<td>
								<input type="text" name="inventorycode" style="width:300px;" maxlength="128" value="<?= dol_escape_htmltag($transfer->inventorycode) ?>">
							</td>
						</tr>
						<?php if ($transfer->rowid > 0){ ?>
						<tr>
							<td><?= $langs->trans("STLabelMovement") ?></td>
							<td>
								<input type="text" name="label"  style="width:300px;" maxlength="255" value="<?= dol_escape_htmltag($labelmovement) ?>">
							</td>
						</tr>
						<tr>
							<td class="titlefield fieldrequired"><?= $langs->trans("STStatus") ?></td>
							<td>
								<?php
									$label = $langs->trans('stocktransfersStatus'.$transfer->status).' '.$transfer->status;
									if (DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME')){
										$icon_class = $transfer->status == '1' ? 'fa-truck' : ( $transfer->status == '2' ? 'fa-check-circle' : 'fa-file-o') ;
										print '<i class="fa fa-lg '.$icon_class.'" style="opacity:0.4;color:inherit;" title="'.$label.'"></i>';
									}else{
										$picto = $transfer->status == '1' ? '3' : ( $transfer->status == '2' ? '4' : '0') ;
										print img_picto($label,'statut'.$picto);
									}
									print ' '. $langs->trans('stocktransfersStatus'.$transfer->status);
								?>

								<!-- change status to sent button -->
								<?php if ($buy_stock=='' && $transfer->rowid > 0 && $transfer->status == '0' && count($transfer->products) > 0){ ?>
								<a href="#" class="button" onclick="js_set_as_sent();return false;">
									<?= $langs->trans('stocktransfersSetStatusSent') ?>
									<?= DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') ? ' &nbsp;<i class="fa fa-truck"></i>':'' ?>
								</a>
								<?php } ?>

								<!-- change status to received button -->
								<?php if ($transfer->rowid > 0 && $transfer->status == '1'){ ?>
								<a href="#" class="button" onclick="js_set_as_received();return false;">
									<?= $langs->trans('stocktransfersSetStatusReceived') ?>
									<?= DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') ? ' &nbsp;<i class="fa fa-check-circle"></i>':'' ?>
								</a>
								<?php } ?>

								<!-- change status back to draft button -->
								<?php if ($transfer->status == '1' || $transfer->status == '2'){ ?>
								<a href="#" class="button" onclick="js_set_as_draft();return false;">
									<?= $langs->trans('stocktransfersSetStatusDraft') ?>
									<?= DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') ? ' &nbsp; <i class="fa fa-rotate-left"></i>':'' ?>
								</a>
								<?php } ?>

							</td>
						</tr>
						<?php } ?>
					</table>
					<p style="padding:0;margin:0;">&nbsp;</p>
				</div>
            </div> <!-- end sk-column -->

            <div class="sk-four sk-columns">
				<div class="ficheaddleft">
					<table class="border" style="width:95%;">
						<tr>
							<td class="titlefield"><?= $langs->trans('STprivateNote') ?><br />
							<textarea name="private_note" class="note_textarea"><?= $transfer->private_note ?></textarea></td>
						</tr>
						<tr>
							<td class="titlefield"><?= $langs->trans('STpdfNote') ?><br />
							<textarea name="pdf_note" class="note_textarea"><?= $transfer->pdf_note ?></textarea></td>
						</tr>
					</table>
				</div>
            </div><!-- end sk-column -->

        </div> <!-- end sk-container -->

        <!-- =========== buttons ======== -->

        <br />
        <div class="center">

            <!-- save button -->
            <a href="#" class="button" onclick="js_validate_form('transfer_card_form');return false;">
				<?= DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') ? '<i class="fa fa-download"></i>&nbsp; ':'' ?>
				<?= $transfer->rowid > 0 ? dol_escape_htmltag($langs->trans('STSave')) : dol_escape_htmltag($langs->trans('CreateDraft')) ?>
				</a>

            <!-- delete button -->
            <?php if ($transfer->rowid > 0 && $transfer->status == '0'){ ?>
            <a href="#" class="button" onclick="js_delete_transfer();return false;">
				<?= DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') ? '<i class="fa fa-trash" style="color:white;"></i>&nbsp; ':'' ?>
				<?= dol_escape_htmltag($langs->trans('STDelete')) ?>
			</a>
            <?php } ?>

            <!-- hidden language selector for PDF -->
			<?php 
				$languages = scandir(STOCKTRANSFERS_MODULE_DOCUMENT_ROOT.'/langs'); 
				$def_lang = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_16) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_16 : 'auto';
				if ($def_lang=='auto') $def_lang = $langs->getDefaultLang();
				$PDFlang = !empty($transfer->lang) ? $transfer->lang : $def_lang;
				$langs->load("languages"); 
			?>
			<select id="sel_pdf_langcode" style="text-align:center;display:none;"
					onclick="$('#bt_download_pdf').attr('href','transfer_pdf.php?id=<?= $transfer->rowid ?>&l='+$(this).val());">
				<?php foreach ($languages as $langcode){ 
						if ($langcode=='.' || $langcode=='..') continue;
				?>
				<option value="<?= $langcode ?>" <?= $langcode==$PDFlang ? "selected='selected'":"" ?>><?= $langs->trans('Language_'.$langcode) ?></option>
				<?php } ?>
			</select>

            <!-- pdf download button -->
            <?php if ($transfer->rowid > 0 && count($transfer->products) > 0){ ?>
            <a  id="bt_download_pdf"
				href="transfer_pdf.php?id=<?= $transfer->rowid ?>&l=<?= $PDFlang ?>" 
				class="button" target="_blank">
				<?= DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME')  
								? '<i class="fa fa-file-pdf"></i>&nbsp; '
								: '<img src="img/pdf.png" style="margin-bottom: -2px;" />' ?>
				<?= dol_escape_htmltag($langs->trans('stocktransfersPDFdownload')) ?>
			</a>
			
            <!-- pdf language button -->
            <a  href="#" onclick="$('#sel_pdf_langcode').toggle();return false;" style="display:inline-block;vertical-align:middle;">
				<?= DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME')  
								? '<i class="fa fa-2x fa-language"></i>'
								: '<img src="img/lang.png" style="margin-bottom: -2px;" />' ?>
			</a>
            
            <?php } ?>
            <!-- purchase proceed button -->
            <?php if ($buy_stock!='' && $conf->purchases->enabled){
                        $fk_project = $conf->purchases->enabled ? '&fk_project='.$transfer->fk_project : '';
                        if (is_dir(DOL_DOCUMENT_ROOT.'/custom/purchases'))
                            $purchases_root = DOL_URL_ROOT.'/custom/purchases';
                        else
                            $purchases_root = DOL_URL_ROOT.'/purchases';
            ?>
            <a  href="<?= $purchases_root ?>/purchase_edit.php?mainmenu=commercial&leftmenu=&products=<?= $buy_stock . $fk_project ?>" 
				class="classfortooltip button butActionDelete"
                title="<?= htmlentities($langs->trans('stocktransfersTooltip1')) ?>"><?= $langs->trans('stocktransfersGoShopping') ?></a>
            <?php } ?>

            <!-- =========== easter egg - to show the raw data of the element (mainly for dev debug) ======== -->

            <a href="#" onclick="return false;" ondblclick="$('#stocktransfers_easter_egg').toggle();return false;" style="text-decoration:none;">&nbsp; &nbsp;</a>
            <div id="stocktransfers_easter_egg" style="text-align: left;margin:2rem;display:none;" class="block">
                <?php
                        $element_fields = array('rowid','ts_create','fk_user_author','label','inventorycode','fk_depot1',
                                                'fk_depot2','date1','date2','fk_project','shipper','n_package','status',
                                                's_products','n_products','n_items','private_note','pdf_note');
                        $element = array();
                        foreach ($element_fields as $f) $element[$f] = $transfer->{$f};
                        echo _var_export($element);
                ?>
            </div>

        </div>


    </form>


    <!-- ========= list of products transferred ========= -->

    <?php if ($transfer->rowid > 0){ ?>

    <br />
    <form action="<?= $_SERVER["PHP_SELF"] ?>" method="POST" id="transfer_product_form">
        <input type="hidden" name="token" value="<?= newToken() ?>" />
        <input type="hidden" name="rowid" value="<?= !empty($transfer->rowid) ? $transfer->rowid : '' ?>" />
        <input type="hidden" name="action" value="add_line">
        <input type="hidden" name="del_pid" value="">
        <input type="hidden" name="add_pid" value="">

        <div class="div-table-responsive-no-max">
            <table class="liste">
                <tr class="liste_titre">
                <?php
                    print getTitleFieldOfList($langs->trans('STProductRef'),0,$_SERVER["PHP_SELF"],'',$param,'','class="tagtd maxwidthonsmartphone"',$sortfield,$sortorder);
                    if ($b_batch_enabled) {
                     print getTitleFieldOfList($langs->trans('STBatch'),0,$_SERVER["PHP_SELF"],'',$param,'','class="tagtd maxwidthonsmartphone"',$sortfield,$sortorder);
                    }
                    print getTitleFieldOfList($langs->trans('STnote'),0,$_SERVER["PHP_SELF"],'',$param,'','align="left" class="tagtd maxwidthonsmartphone"',$sortfield,$sortorder);
                    $ic_truck = '<i class="fa fa-dolly"></i>';
                    print getTitleFieldOfList($langs->trans('STQty').'<br />'.$ic_truck,0,$_SERVER["PHP_SELF"],'',$param,'','align="center" class="tagtd maxwidthonsmartphone"',$sortfield,$sortorder);
                    if ($transfer->status == '0'){
						$ic_house = '<i class="fa fa-home"></i>';
                        print getTitleFieldOfList($langs->trans('STStock').'<br /><span style="font-size:0.8em;">'.$langs->trans('WarehouseSource').'</span>',0,$_SERVER["PHP_SELF"],'',$param,'','align="center" class="tagtd maxwidthonsmartphone"',$sortfield,$sortorder);
                        print getTitleFieldOfList($langs->trans('STStock').'<br /><span style="font-size:0.8em;">'.$langs->trans('WarehouseTarget').'</span>',0,$_SERVER["PHP_SELF"],'',$param,'','align="center" class="tagtd maxwidthonsmartphone"',$sortfield,$sortorder);
                    }
                    print getTitleFieldOfList('',0);
                ?>
                </tr>


                <!-- ========= List of current added lines ========= -->

                <?php foreach($transfer->products as $pid => $p){

                    $productstatic->fetch($pid);
                    $needed_stock_this = $depot1_stock[$pid] < floatval($p['n']);
                ?>

                <tr <?= $bc[$var] ?> class='product' data-pid='<?= $pid ?>'
                    data-ref="<?= $productstatic->ref ?>"
                    data-batch="<?= $b_batch_enabled ? $p['b']:'' ?>"
                    data-n="<?= $p['n'] ?>"
                    >
                    <td>
                        <?= $productstatic->getNomUrl(1).' - '.$productstatic->label ?>
                        <div id="ST_pid_<?= $pid ?>_m" style="display:none;"><?= isset($p['m']) ? $p['m']:'' ?></div>
                    </td>
                    <?php if ($b_batch_enabled){ ?>
                    <td>
                        <?= $p['b'] ?>
                    </td>
                    <?php } ?>
                    <td style='text-align:left;'>
                        <em><?= $p['m'] ?></em>
                    </td>
                    <td style='text-align:center;'>
                        <?= _qty($p['n']) ?>
                    </td>
                    <?php if ($transfer->status == '0'){ ?>
                    <td style='text-align:center;'>
                        <?= $depot1_stock[$pid] > 0 ? _qty($depot1_stock[$pid]) : '-' ?>
                        <?php if ($needed_stock_this){ ?>
                            <a href="<?= DOL_URL_ROOT ?>/product/stock/product.php?id=<?= $pid ?>&action=correction&id_entrepot=<?= $transfer->fk_depot1 ?>&token=<?= newToken() ?>"
                                title="<?= str_replace('"','',($langs->trans('stocktransfersErrorMsg03')).' '.($langs->trans('stocktransfersAdjustStock'))) ?>"
                                target="_blank" style="display:inline-block;margin:0px 4px;min-width:0;" class="button">
                                <?= DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') ? "<i class='fa fa-warning'></i>" : img_warning('') ?></a>
                        <?php } ?>
                    </td>
                    <td style='text-align:center;'>
                        <?= $depot2_stock[$pid] > 0 ? _qty($depot2_stock[$pid]) : '-' ?>
                    </td>
                    <?php } ?>
                    <td>
                        <?php if ($transfer->status == '0' ){ ?>
                            <a href="#" onclick="js_edit_line('<?= $pid ?>');return false;" style="display:inline-block;float:left;margin:3px;min-width:0;" 
									class="button" title="<?= str_replace('"','',$langs->trans("STedit"))?>">
                                <?= DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') ? "<i class='fa fa-pencil'></i>" : img_edit($langs->trans("STedit")) ?></a>
                            <a href="#" onclick="js_del_line('<?= $pid ?>');return false;" style="display:inline-block;float:left;margin:3px;min-width:0;" 
									class="button" title="<?= str_replace('"','',$langs->trans("STRemove"))?>">
                                <?= DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') ? "<i class='fa fa-trash' style='color:white;'></i>" : img_delete($langs->trans("STRemove")) ?></a>
                        <?php } ?>
                    </td>
                </tr>

                <?php } ?>


                <!-- ========= boxes to add a new line ========= -->

                <?php if ($transfer->status == '0' ){ ?>

                <tr <?= $bc[$var] ?> id="new_line">

                    <!-- ========= product ========= -->
                    <td class="titlefield">
                        <?= $form->select_produits(!empty($_POST['pid']) ? $_POST['pid'] : '', 'pid', $filtertype, $limit, 0, -1, 2, '', 0, array(), 0, '1', 0, 'maxwidth400', 1) ?>
                    </td>

                    <!-- ========= Batch/lot number ========= -->
                    <?php if ($b_batch_enabled) { ?>
                    <td>
                        <input type="text" name="batch" class="flat maxwidth50onsmartphone" style="width:100%;"
                            value="<?= !empty($_POST['batch']) ? $_POST['batch'] : '' ?>">
                    </td>
                    <?php } ?>

                    <!-- ========= Note (Message) ========= -->
                    <td style='text-align:center;' class=''>
                        <textarea size="3" class="flat" name="m" style="width:100%;"><?= !empty($_POST['m']) ? $_POST['m'] : '' ?></textarea>
                    </td>

                    <!-- ========= Quantity ========= -->
                    <td style='text-align:center;' class=''>
                        <input type="text" size="4" class="flat" name="n" value="<?= !empty($_POST['n']) ? $_POST['n'] : '' ?>">
                    </td>

                    <!-- ========= Automatic Stock ========= -->
                    <td style='text-align:center;' class=''>
                        <span id='new_line_stock1'></span>
                    </td>
                    <td style='text-align:center;' class=''>
                        <span id='new_line_stock2'></span>
                    </td>

                    <!-- ========= Button to add ========= -->
                    <td style='text-align:left;'>
                        <input id="ST_add" type="submit" class="button" value="<?= dol_escape_htmltag($langs->trans("STAdd")) ?>" />
                        <input id="ST_save" type="submit" class="button" style="display:none;" value="<?= dol_escape_htmltag($langs->trans("STSave")) ?>" />
                    </td>
                </tr>

                <?php } ?>

            </table>
        </div>

    </form>
    <br />

    <?php }  ?>


</div>

<script>

    var b_batch_enabled = <?= $b_batch_enabled ? 'true':'false' ?>;

    $(document).ready(function(){
        $('#transfer_product_form').bind('submit',function(){
            /* we don't check nothing if we're deleting a line  */
                if ($('#transfer_product_form input[name=action]').val()=='del_line') return;

            /* check data to add  */
                var msg = js_check_submit_add_line();
                if (msg!='') return false;

            /* alert if the product was already added before */
                var ref = $('#new_line input[name=search_pid]').val();
                if ($('#transfer_product_form tr[data-ref="'+ref+'"]').length != 0){
                    if (!confirm("<?= str_replace('"','',html_entity_decode($langs->trans('STErrorMsg07'))) ?>")) {
                        return false;
                    }
                }
        });
        $('#pid').on('change',function(){
            js_update_stock_new_line();
        });
    });

    var last_pid_stocked = '';
    function js_update_stock_new_line(){
        var pid = $('#pid').val();
        if (pid=='' || pid==last_pid_stocked) return;
        last_pid_stocked = pid;
        /* get from server the current stock for this product and departure warehouse */
        var url_json = 'json.php?action=get_stock'
                        +'&pid='+pid
                        +'&wid1=<?= $transfer->fk_depot1 ?>'
                        +'&wid2=<?= $transfer->fk_depot2 ?>'
                        +'&token=<?= currentToken() ?>';
        console.log(url_json);
        $.getJSON(
            url_json,
            function(data){
                console.log(data);
                if (data.ok=='1'){
                    var stock1 = data.stock['stock1'] ? data.stock['stock1'] : 0;
                    var stock2 = data.stock['stock2'] ? data.stock['stock2'] : 0;
                    $('#new_line_stock1').html('<b>'+stock1+'</b>');
                    $('#new_line_stock2').html('<b>'+stock2+'</b>');
                }
            }
        );
    }

    function js_check_submit_add_line(){
        /* read form values */
        if ($('#new_line select[name=pid]').length==1){
            var pid = parseInt($('#new_line select[name=pid]').val());
        }else if ($('#new_line input[name=pid]').length==1){
            var pid = parseInt($('#new_line input[name=pid]').val());
        }else if ($('#new_line select[name=search_pid]').length==1){
            var pid = parseInt($('#new_line select[name=search_pid]').val());
        }else if ($('#new_line input[name=search_pid]').length==1){
            var pid = parseInt($('#new_line input[name=search_pid]').val());
        }else{
            alert('Product select box not found, please contact developer.');
            return;
        }
        var qty = parseFloat($('#new_line input[name=n]').val());

        /* check product */
        var msg = '';
        if (isNaN(pid) || pid<1) {
            $('#transfer_product_form input[name=add_pid]').val('');
            msg += "<?= html_entity_decode(str_replace('"','',$langs->trans('STErrorMsg05'))) ?> ";
            $('#transfer_product_form span[role=combobox]').css('background-color','yellow');
            $('#transfer_product_form .select2').addClass('alertedcontainer');
        }else{
            $('#transfer_product_form input[name=add_pid]').val(pid);
        }

        /* check quantity */
        if (isNaN(qty) || qty<0) {
            msg += "<?= html_entity_decode(str_replace('"','',$langs->trans('STErrorMsg06'))) ?> ";
            $('#transfer_product_form input[name=n]').addClass('alertedfield');
        }

        /* check batch/lot serial number if this feature is enabled on Dolibarr */
        if (b_batch_enabled===true){

            var batch = $('#transfer_product_form input[name=batch]').val();
            if (batch.trim()=='') {
                msg += "<?= html_entity_decode(str_replace('"','',$langs->trans('STErrorMsg08'))) ?> ";
                $('#transfer_product_form input[name=batch]').addClass('alertedfield');
            }

        }

        /* if wrong data, then show a warning message to user */
        if (msg!=''){
            alert(msg);
        }

        return msg;
    }

    function js_delete_transfer(){
        if (confirm("<?= str_replace('"','',html_entity_decode($langs->trans('stocktransfersDelSure','',0))) ?>")){
            document.location = 'transfer_edit.php?mainmenu=products&action=delete_transfer&rowid=<?= $transfer->rowid ?>&token=<?= newToken() ?>';
        }
    }

    function js_edit_line(pid){
        var tr = $('#transfer_product_form table tr[data-pid='+pid+']');
        if ($('#pid').hasClass('select2-hidden-accessible')){ /* Dolibarr +11.x */
            $('#pid').val(pid); // Select the option with a value of '1'
            $('#pid').trigger('change'); // Notify any JS components that the value changed
        }else{ /* Dolibarr versions until 10.x included */
            $('#new_line input[name=search_pid]').val(tr.attr('data-ref'));
            $('#new_line input[name=pid]').val(pid);
        }
        if (b_batch_enabled===true){
            $('#new_line input[name=batch]').val(tr.attr('data-batch'));
        }
        $('#new_line input[name=n]').val(tr.attr('data-n'));
        $('#new_line textarea[name=m]').val($('#ST_pid_'+pid+'_m').html().replace('&amp;','&'));
        /* exchange save buttons */
        $('#ST_add').hide();
        $('#ST_save').hide().fadeIn();

        js_update_stock_new_line();
    }

    function js_del_line(pid){
        if (!confirm("<?= str_replace('"','',html_entity_decode($langs->trans('STconfirmDEL','',0))) ?>")) return;
        $('#transfer_product_form input[name=action]').val('del_line');
        $('#transfer_product_form input[name=del_pid]').val(pid);
        $('#transfer_product_form').submit();
    }

    function js_set_as_sent(){
        if ($('#transfer_card_form input[name=date1]').val()==''){
            $('#transfer_card_form input[name=date1]').addClass('alertedfield');
            alert("<?= html_entity_decode(str_replace('"','\"',$langs->trans('stocktransfersErrorMsg01'))) ?>");
        }else{
            $('#transfer_card_form input[name=status]').val('1');
            js_validate_form('transfer_card_form');
        }
    }

    function js_set_as_received(){
        if ($('#transfer_card_form input[name=date2]').val()==''){
            $('#transfer_card_form input[name=date2]').addClass('alertedfield');
            alert("<?= html_entity_decode(str_replace('"','\"',$langs->trans('stocktransfersErrorMsg02'))) ?>");
        }else{
            $('#transfer_card_form input[name=status]').val('2');
            js_validate_form('transfer_card_form');
        }
    }

    function js_set_as_draft(){
        $('#transfer_card_form input[name=status]').val('0');
        js_validate_form('transfer_card_form');
    }

    function js_validate_form(form_id){

        /* prepare */
            var all_fine = true, fine = true, control, c_val, c_name, c_id;
            $(control).removeClass('alertedfield');
            $('#'+form_id+' tr').removeClass('alertedcontainer');

        /* check required fields */
            $('#'+form_id+' .fieldrequired').each(function(){
                /* = input fields = */
                    control = $(this).closest('tr').find('input');
                    c_val = $(control).val();
                    c_name = $(control).attr('name');
                    c_id = $(control).attr('id');
                    if (c_name!=undefined){
                        if (c_val=='') fine = false;
                        if (!fine){
                            all_fine = false;
                            $(control).addClass('alertedfield');
                            $(control).closest('tr').addClass('alertedcontainer');
                        }
                    }
                /* = select fields = */
                    control = $(this).closest('tr').find('select');
                    c_val = $(control).val();
                    c_name = $(control).attr('name');
                    c_id = $(control).attr('id');
                    if (c_name!=undefined){
                        if (c_val=='' || c_val=='-1') fine = false;
                        if (!fine){
                            all_fine = false;
                            $(control).addClass('alertedfield');
                            $(control).closest('tr').addClass('alertedcontainer');
                        }
                    }
            });

        /* check warehouses (source and destiny) are not the same */
            if ($('#fk_depot1').val() == $('#fk_depot2').val()){
                $('#fk_depot1').closest('tr').addClass('alertedcontainer');
                $('#fk_depot2').closest('tr').addClass('alertedcontainer');
                all_fine = false;
            }

        /* submit form */
            if (all_fine){
                $('#'+form_id).submit();
            }

    }

    $(document).ready(function(){
        /* == put focus on the box to add a new product line == */
            if ($('#new_line td.titlefield').length){
                $('#new_line td.titlefield').find('label').focus();
            }

        /* set auto UN-ALERT */
            $('form').on('click','.select2.alertedcontainer span',function(){
                $('#transfer_product_form span[role=combobox]').css('background-color','');
                $('#transfer_product_form .select2').removeClass('alertedcontainer');
            });
            $('form').on('click','.alertedfield',function(){
                $(this).removeClass('alertedfield');
            });
            $('form').on('click','.alertedcontainer',function(){
                $(this).removeClass('alertedcontainer');
            });

    });
</script>
<div id="debug"></div>

<style>
    input.alertedfield, select.alertedfield, textarea.alertedfield{background-color:yellow!important;}
    .alertedcontainer td, .alertedcontainer td.fieldrequired{color:red!important;}
    .block{padding:0.5rem;background-color:rgba(100,100,100,0.05);border-radius:3px;border:1px rgba(100,100,100,0.2) solid;}
</style>

<?php
    // End of page
    $db->close();
    llxFooter('');
