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
            //while($obj = $db->fetch_object($resql)) $depots[$obj->rowid] = $obj;
            while($row = $resql->fetch_assoc()) $depots[] = $row;
        }

    // == load current stock at origin depot
        $stock = $transfer->getStock();

    // == need to purchase more stock
        $buy_stock = '';
        if ($transfer->status == '0' && count($transfer->products) > 0){
            foreach($transfer->products as $pid => $p){
                $missing_stock = intval($p['n']) - intval($stock[$pid]);
                if (!isset($stock[$pid]) || $missing_stock > 0) {
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

?>

<!-- ========= header with section title ========= -->

<?= load_fiche_titre( ($transfer->rowid > 0 ? $langs->trans('stocktransfersTransfer') : $langs->trans('stocktransfersNewTransfer')),
                    '<a href="transfer_list.php?mainmenu=products&leftmenu=" class="button">'.$langs->trans('purchasesMenuTitle3').'</a>',
                    'title_products.png') ?>


<div class='tabBar'>

    <!-- ========= Form with the transfer details (dates, status, project, etc.) ========= -->

    <form action="<?= $_SERVER["PHP_SELF"] ?>" method="POST" name="transfer_card_form" id="transfer_card_form">
        <input type="hidden" name="token" value="<?= $_SESSION['newtoken'] ?>" />
        <input type="hidden" name="rowid" value="<?= !empty($transfer->rowid) ? $transfer->rowid : '' ?>" />
        <input type="hidden" name="action" value="save_card" />
        <input type="hidden" name="status" value="<?= $transfer->status ?>" />
        <input type="hidden" name="old_status" value="<?= $transfer->status ?>" />

        <?php
            $codemove=GETPOST('codemove');
            $labelmovement = GETPOST("label") ? GETPOST('label') : $langs->trans("StockTransfer").' '.dol_print_date($now,'%Y-%m-%d %H:%M');
        ?>
        <div class="underbanner clearboth"></div>

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
                <td><?= $formproduct->selectWarehouses($transfer->fk_depot1, 'fk_depot1', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp') ?></td>
            </tr>
            <tr>
                <td class="titlefield fieldrequired"><?= $langs->trans('WarehouseTarget') ?></td>
                <td><?= $formproduct->selectWarehouses($transfer->fk_depot2, 'fk_depot2', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp') ?></td>
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
                        $picto = $transfer->status == '1' ? '3' : ( $transfer->status == '2' ? '4' : '0') ;
                        print img_picto($langs->trans('stocktransfersStatus'.$transfer->status),'statut'.$picto)
                        . ' '. $langs->trans('stocktransfersStatus'.$transfer->status) ?>

                    <!-- change status to sent button -->
                    <?php if ($buy_stock=='' && $transfer->rowid > 0 && $transfer->status == '0' && count($transfer->products) > 0){ ?>
                    <a href="#" class="button" onclick="js_set_as_sent();return false;"><?= $langs->trans('stocktransfersSetStatusSent') ?></a>
                    <?php } ?>

                    <!-- change status to received button -->
                    <?php if ($transfer->rowid > 0 && $transfer->status == '1'){ ?>
                    <a href="#" class="button" onclick="js_set_as_received();return false;"><?= $langs->trans('stocktransfersSetStatusReceived') ?></a>
                    <?php } ?>

                    <!-- change status back to draft button -->
                    <?php if ($transfer->status == '1' || $transfer->status == '2'){ ?>
                    <a href="#" class="button" onclick="js_set_as_draft();return false;"><?= $langs->trans('stocktransfersSetStatusDraft') ?></a>
                    <?php } ?>

                </td>
            </tr>
            <?php } ?>
        </table>

        <!-- =========== buttons ======== -->

        <br />
        <div class="center">

            <!-- save button -->
            <a href="#" class="button" onclick="js_validate_form('transfer_card_form');return false;"><?= $transfer->rowid > 0 ? dol_escape_htmltag($langs->trans('STSave')) : dol_escape_htmltag($langs->trans('CreateDraft')) ?></a>

            <!-- delete button -->
            <?php if ($transfer->rowid > 0 && $transfer->status == '0'){ ?>
            <a href="#" class="button" onclick="js_delete_transfer();return false;"><?= dol_escape_htmltag($langs->trans('STDelete')) ?></a>
            <?php } ?>

            <!-- pdf button -->
            <?php if ($transfer->rowid > 0 && count($transfer->products) > 0){ ?>
            <a href="transfer_pdf.php?id=<?= $transfer->rowid ?>" class="button" target="_blank"><img src="img/pdf.png" style="margin-bottom: -2px;" /> <?= dol_escape_htmltag($langs->trans('stocktransfersPDFdownload')) ?></a>
            <?php } ?>
            <!-- purchase proceed button -->
            <?php if ($buy_stock!='' && $conf->purchases->enabled){
                        $fk_project = $conf->purchases->enabled ? '&fk_project='.$transfer->fk_project : '';
                        if (is_dir(DOL_DOCUMENT_ROOT.'/custom/purchases'))
                            $purchases_root = DOL_URL_ROOT.'/custom/purchases';
                        else
                            $purchases_root = DOL_URL_ROOT.'/purchases';
            ?>
            <a href="<?= $purchases_root ?>/purchase_edit.php?mainmenu=commercial&leftmenu=&products=<?= $buy_stock . $fk_project ?>" class="classfortooltip button butActionDelete"
               title="<?= htmlentities($langs->trans('stocktransfersTooltip1')) ?>"><?= $langs->trans('stocktransfersGoShopping') ?></a>
            <?php } ?>

            <!-- =========== easter egg - to show the raw data of the element (mainly for dev debug) ======== -->

            <a href="#" onclick="return false;" ondblclick="$('#stocktransfers_easter_egg').toggle();return false;" style="text-decoration:none;">&nbsp; &nbsp;</a>
            <div id="stocktransfers_easter_egg" style="text-align: left;margin:2rem;display:none;" class="block">
                <?php
                        $element_fields = array('rowid','ts_create','fk_user_author','label','inventorycode','fk_depot1','fk_depot2','date1','date2','fk_project','shipper','n_package','status','s_products','n_products','n_items');
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
        <input type="hidden" name="token" value="<?= $_SESSION['newtoken']  ?>">
        <input type="hidden" name="rowid" value="<?= !empty($transfer->rowid) ? $transfer->rowid : '' ?>" />
        <input type="hidden" name="action" value="add_line">
        <input type="hidden" name="del_pid" value="">

        <div class="div-table-responsive-no-max">
            <table class="liste">
                <tr class="liste_titre">
                <?php
                    print getTitleFieldOfList($langs->trans('STProductRef'),0,$_SERVER["PHP_SELF"],'',$param,'','class="tagtd maxwidthonsmartphone"',$sortfield,$sortorder);
                    if ($conf->productbatch->enabled) {
                     print getTitleFieldOfList($langs->trans('STBatch'),0,$_SERVER["PHP_SELF"],'',$param,'','class="tagtd maxwidthonsmartphone"',$sortfield,$sortorder);
                    }
                    print getTitleFieldOfList($langs->trans('STQty'),0,$_SERVER["PHP_SELF"],'',$param,'','align="center" class="tagtd maxwidthonsmartphone"',$sortfield,$sortorder);
                    if ($transfer->status == '0')
                    print getTitleFieldOfList($langs->trans('STStock'),0,$_SERVER["PHP_SELF"],'',$param,'','align="center" class="tagtd maxwidthonsmartphone"',$sortfield,$sortorder);
                    print getTitleFieldOfList('',0);
                ?>
                </tr>


                <!-- ========= List of current added lines ========= -->

                <?php foreach($transfer->products as $pid => $p){

                    $productstatic->fetch($pid);
                    $needed_stock_this = !isset($stock[$pid]) || intval($stock[$pid]) < intval($p['n']);
                ?>

                <tr <?= $bc[$var] ?> class='product' data-pid='<?= $pid ?>'>
                    <td>
                        <?= $productstatic->getNomUrl(1).' - '.$productstatic->label ?>
                    </td>
                    <?php if ($conf->productbatch->enabled){ ?>
                    <td>
                        <?= $p['b'] ?>
                    </td>
                    <?php } ?>
                    <td style='text-align:center;'>
                        <?= $p['n'] ?>
                    </td>
                    <?php if ($transfer->status == '0'){ ?>
                    <td style='text-align:center;'>
                        <?= isset($stock[$pid]) ? $stock[$pid] : '-' ?>
                        <?= $needed_stock_this ? img_warning($langs->trans('stocktransfersErrorMsg03')) : '' ?>
                    </td>
                    <?php } ?>
                    <td>
                        <?php if ($transfer->status == '0' ){ ?>
                        <a href="#" onclick="js_del_line('<?= $pid ?>');return false;" style="display:inline-block;height:25px;float:left;margin:0px 4px;">
                            <?= img_delete($langs->trans("STRemove")) ?></a>

                        <a href="<?= DOL_URL_ROOT ?>/product/stock/product.php?id=<?= $pid ?>&action=correction&id_entrepot=<?= $transfer->fk_depot1 ?>" target="_blank" style="display:inline-block;height:25px;float:left;margin:0px 4px;">
                            <?= img_warning($langs->trans("stocktransfersAdjustStock")) ?></a>
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

                    <!-- ========= Batch number ========= -->
                    <?php if ($conf->productbatch->enabled) { ?>
                    <td>
                        <input type="text" name="batch" class="flat maxwidth50onsmartphone" value="<?= !empty($_POST['batch']) ? $_POST['batch'] : '' ?>">
                    </td>
                    <?php } ?>

                    <!-- ========= Quantity ========= -->
                    <td style='text-align:center;' class=''>
                        <input type="text" size="3" class="flat" name="n" value="<?= !empty($_POST['n']) ? $_POST['n'] : '' ?>">
                    </td>

                    <?php if ($transfer->status == '0'){ ?>
                    <td>&nbsp;</td>
                    <?php } ?>

                    <!-- ========= Button to add ========= -->
                    <td style='text-align:left;'>
                        <input type="submit" class="button" value="<?= dol_escape_htmltag($langs->trans("STAdd")) ?>">
                    </td>
                </tr>

                <?php } ?>

            </table>
        </div>

    </form>
    <br />

    <?php } ?>


</div>

<script>
    $(document).ready(function(){
        $('#transfer_product_form').bind('submit',function(){
            /* we don't check nothing if we're deleting a line  */
                if ($('#transfer_product_form input[name=action]').val()=='del_line') return;

            /* check data to add  */
                var msg = js_check_submit_add_line();
                if (msg!='') return false;

            /* alert if the product was already added before */
                var pid = parseInt($('#transfer_product_form select[name=pid]').val());
                if ($('form tr.product[data-pid='+pid+']').length != 0){
                    if (!confirm("<?= str_replace('"','',html_entity_decode($langs->trans('STErrorMsg07'))) ?>")) {
                        return false;
                    }
                }
        });
    });

    function js_check_submit_add_line(){

        /* read form values */
        var pid = parseInt($('#transfer_product_form select[name=pid]').val());
        var qty = parseInt($('#transfer_product_form input[name=n]').val());

        /* check wrong data */
        var msg = '';
        if (isNaN(pid) || pid<1) {
            msg += "<?= str_replace('"','',$langs->trans('STErrorMsg05')) ?> ";
            $('#transfer_product_form span[role=combobox]').css('background-color','yellow');
            $('#transfer_product_form .select2').addClass('alertedcontainer');
        }

        if (isNaN(qty) || qty<1) {
            msg += "<?= str_replace('"','',$langs->trans('STErrorMsg06')) ?> ";
            $('#transfer_product_form input[name=n]').addClass('alertedfield');
        }

        /* if wrong data, then show a warning message to user */
        if (msg!=''){
            alert(msg);
        }

        return msg;
    }

    function js_delete_transfer(){
        if (confirm("<?= str_replace('"','',html_entity_decode($langs->trans('stocktransfersDelSure','',0))) ?>")){
            document.location = 'transfer_edit.php?mainmenu=products&action=delete_transfer&rowid=<?= $transfer->rowid ?>';
        }
    }

    function js_del_line(pid){
        $('#transfer_product_form input[name=action]').val('del_line');
        $('#transfer_product_form input[name=del_pid]').val(pid);
        $('#transfer_product_form').submit();
    }

    function js_set_as_sent(){
        if ($('#transfer_card_form input[name=date1]').val()==''){
            $('#transfer_card_form input[name=date1]').addClass('alertedfield');
            alert("<?= str_replace('"','\"',$langs->trans('stocktransfersErrorMsg01')) ?>");
        }else{
            $('#transfer_card_form input[name=status]').val('1');
            js_validate_form('transfer_card_form');
        }
    }

    function js_set_as_received(){
        if ($('#transfer_card_form input[name=date2]').val()==''){
            $('#transfer_card_form input[name=date2]').addClass('alertedfield');
            alert("<?= str_replace('"','\"',$langs->trans('stocktransfersErrorMsg02')) ?>");
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
    llxFooter('$Date: 2009/03/09 11:28:12 $ - $Revision: 1.8 $');
