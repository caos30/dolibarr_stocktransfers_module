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

// Put here all includes required by your class file

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

// == STOCKTRANSFERS_MODULE DOCUMENT_ROOT & URL_ROOT
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/stocktransfers/core/modules/modStocktransfers.class.php')){
        define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/custom/stocktransfers');
        define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/custom/stocktransfers');
    }else{
        define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/stocktransfers');
        define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/stocktransfers');
    }

class StockTransfer extends CommonObject
{
    var $db;							//!< To store db handler
    var $error;							//!< To return error code (or message)
    var $errors=array();				//!< To return several error codes (or messages)

    public $element='transfer';
    public $table_element='stocktransfers_transfers';
    public $picto='stocktransfers';

    var $rowid;
    var $ts_create;
    var $fk_depot1;
    var $fk_depot2;
    var $date1;
    var $date2;
    var $fk_user_author;
    var $fk_project;
    var $label;
    var $inventorycode;
    var $shipper;
    var $n_package = 1;
    var $status = '0'; // 0->draft, 1->validated-not-delivered, 2->delivered

    var $s_products = '';
    var $products = array();

    var $s;

    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_DELIVERED = 2;

    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function __construct($DB)
    {
        global $conf;
        $this->db = $DB;

        return 1;
    }


    /**
     *      \brief      Create in database
     *      \param      user        	User that create
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, Id of created object if OK
     */
    function create()
    {
    	global $conf, $langs, $user;
        $error=0;

        // Clean parameters

            //if (isset($this->id)) $this->id=trim($this->id);

        // Check parameters
            if (empty($user->id) || empty($this->fk_depot1) || empty($this->fk_depot2))
            {
                    $this->error = "ErrorBadParameter";
                    dol_syslog(get_class($this)."::create Try to create a transfer with an empty parameter (user, depots, ...)", LOG_ERR);
                    return -3;
            }

        // Insert request
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."stocktransfers_transfers(";

            $sql.= "fk_user_author,";
            $sql.= "fk_project,";
            $sql.= "label,";
            $sql.= "inventorycode,";
            $sql.= "fk_depot1,";
            $sql.= "fk_depot2,";
            $sql.= "date1,";
            $sql.= "date2,";
            $sql.= "shipper,";
            $sql.= "n_package";

            $sql.= ") VALUES (";

            $sql.= " '".$user->id."',";
            $sql.= " ".($this->fk_project ? "'".intval($this->fk_project)."'" : '0' ).",";
            $sql.= " ".($this->label ? "'".$this->db->escape($this->label)."'" : "'Nueva transferencia'").",";
            $sql.= " ".($this->inventorycode ? "'".$this->db->escape($this->inventorycode)."'" : 'NULL').",";
            $sql.= " ".($this->fk_depot1 ? "'".intval($this->fk_depot1)."'" : '0' ).",";
            $sql.= " ".($this->fk_depot2 ? "'".intval($this->fk_depot2)."'" : '0').",";
            $sql.= " ".($this->date1 ? "'".$this->date1."'" : 'NULL').",";
            $sql.= " ".($this->date2 ? "'".$this->date2."'" : 'NULL').",";
            $sql.= " ".($this->shipper ? "'".$this->db->escape($this->shipper)."'" : 'NULL').",";
            $sql.= " ".($this->n_package ? "'".$this->db->escape($this->n_package)."'" : 'NULL');

            $sql.= ")";

            $this->db->begin();

            dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);

        // run SQL
            $resql=$this->db->query($sql);
            if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

                    if (! $error)
            {
                $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."stockTransfers_transfers");

            }

        // Commit or rollback
            if ($error)
                    {
                            foreach($this->errors as $errmsg)
                            {
                        dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
                        $this->error.=($this->error?', '.$errmsg:$errmsg);
                            }
                            $this->db->rollback();
                            return -1*$error;
                    }
                    else
                    {
                            $this->db->commit();
                return $this->id;
                    }
    }


    /**
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."stocktransfers_transfers";
        $sql.= " WHERE rowid = ".$id;
    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $row = $resql->fetch_assoc();
                if(is_array($row)){
                    foreach ($row as $f=>$v) $this->{$f} = $v;
                }
                /*
                $this->rowid = $obj->rowid;
                $this->ts_create = $obj->pattern;
                 *
                 */
            }
            $this->db->free($resql);

            $this->unserializeProducts();

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
        $error=0;

        // Check parameters
            if (empty($this->fk_depot1) || empty($this->fk_depot2))
            {
                    $this->error = "ErrorBadParameter";
                    dol_syslog(get_class($this)."::create Try to create a transfer with an empty parameter (user, depots, ...)", LOG_ERR);
                    return -3;
            }

        // Update request
            $sql = "UPDATE ".MAIN_DB_PREFIX."stocktransfers_transfers SET ";

            $sql.= "label=".($this->label ? "'".$this->db->escape($this->label)."'" : "'Nueva transferencia'").", ";
            $sql.= "fk_project=".($this->fk_project ? "'".intval($this->fk_project)."'" : '0' ).",";
            $sql.= "inventorycode=".($this->inventorycode ? "'".$this->db->escape($this->inventorycode)."'" : 'NULL').", ";
            $sql.= "fk_depot1=".($this->fk_depot1 ? "'".intval($this->fk_depot1)."'" : '0' ).",";
            $sql.= "fk_depot2=".($this->fk_depot2 ? "'".intval($this->fk_depot2)."'" : '0' ).",";
            if (!empty($this->date1))
            $sql.= "date1=STR_TO_DATE('".str_replace('-','',$this->date1)."','%Y%m%d'), "; // 20190613
            if (!empty($this->date2))
            $sql.= "date2=STR_TO_DATE('".str_replace('-','',$this->date2)."','%Y%m%d'), "; // 20190613
            $sql.= "shipper=".($this->shipper ? "'".$this->db->escape($this->shipper)."'" : 'NULL').",";
            $sql.= "n_package=".($this->n_package ? "'".$this->db->escape($this->n_package)."'" : 'NULL').",";
            $sql.= "s_products=".(is_array($this->products) ? "'".serialize($this->products)."'" : "'".serialize(array())."'").",";
            $sql.= "status='".$this->status."',";
            $sql.= "n_products='".count($this->products)."'";

            $sql.= " WHERE rowid=".$this->rowid;

            //echo "<h3>sql=$sql</h3>";

            $this->db->begin();

            dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);

        // run query
            $resql = $this->db->query($sql);
            if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        // Commit or rollback
            if ($error)
            {
                foreach($this->errors as $errmsg)
                {
                    dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
                    $this->error.=($this->error?', '.$errmsg:$errmsg);
                }
                $this->db->rollback();
                return -1*$error;
            }
            else
            {
                $this->db->commit();
                return 1;
            }
    }


    /**
    *   \brief      Delete object in database
    *	\param      user        	User that delete
    *   \param      notrigger	    0=launch triggers after, 1=disable triggers
    *	\return		int				<0 if KO, >0 if OK
    */
    function delete($user, $notrigger=0){

        global $conf, $langs;
        $error=0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."stocktransfers_transfers";
        $sql.= " WHERE rowid=".$this->rowid;

        $this->db->begin();

        dol_syslog(get_class($this)."::delete sql=".$sql);

        $resql = $this->db->query($sql);
        if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        // Commit or rollback
            if ($error){
                    foreach($this->errors as $errmsg){
                        dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
                        $this->error.=($this->error?', '.$errmsg:$errmsg);
                    }
                    $this->db->rollback();
                    return -1*$error;
            }else{
                    $this->db->commit();
                    return 1;
            }
    }

    /**
    *   \brief      Create stock movements OUT of depot1 or IN the depot2 --- or also we can REVERSE these movements (actually add movements with negative quantity value)
    *	\param      user        	User that delete
    *   \param      notrigger	    0=launch triggers after, 1=disable triggers
    *	\return		int				<0 if KO, >0 if OK
    */
    function create_stock_movements($depot,$reverse=0){ // $depot = '1' -> movements on depot1 / $depot = '2' -> movements on depot2

    	global $conf, $langs, $user;
        $error=0;

        $this->db->begin();

        $product = new Product($this->db);

        foreach($this->products as $pid => $p)
        {
                $batch = $p['b'];
                $qty   = intval($p['n']);
                if ($qty==0) continue;

                if ($reverse && empty($p['m'.$depot])) continue; // we check that this product really has a stock movement ID, if not then we do nothing. It shouldn't be match never... so it's a redundant checking, by the way :)

                $id_sw = $this->fk_depot1;
                $id_tw = $this->fk_depot2;
                $dlc=-1;		// They are loaded later from serial
                $dluo=-1;		// They are loaded later from serial

                if (! $error && is_numeric($qty) && $pid)
                {
                        $result = $product->fetch($pid);

                        $product->load_stock('novirtual');	// Load array product->stock_warehouse

                        // Define value of products moved
                        $pricesrc=0;
                        if (! empty($product->pmp)) $pricesrc=$product->pmp;
                        $pricedest=$pricesrc;

                        //print 'price src='.$pricesrc.', price dest='.$pricedest;exit;

                        if (empty($conf->productbatch->enabled) || ! $product->hasbatch())		// If product does not need lot/serial
                        {
                            if ($depot == '1'){
                                // Remove stock
                                $typemov = $reverse ? 0 : 1;
                                $label = ($this->label).($reverse ? ' CANCEL':'');
                                $invcode = ($this->inventorycode).($reverse ? ' CANCEL':'');
                                $result = $product->correct_stock($user,$id_sw,$qty,$typemov,$label,$pricesrc,$invcode);
                                if ($result < 0){
                                        $error++;
                                        $_SESSION['EventMessages'][] = array($product->errors, $product->errorss, 'errors');
                                }
                            }else if ($depot == '2'){
                                // Add stock
                                $typemov = $reverse ? 1 : 0;
                                $label = ($this->label).($reverse ? ' CANCEL':'');
                                $invcode = ($this->inventorycode).($reverse ? ' CANCEL':'');
                                $result = $product->correct_stock($user,$id_tw,$qty,$typemov,$label,$pricedest,$invcode);
                                if ($result < 0){
                                        $error++;
                                        $_SESSION['EventMessages'][] = array($product->errors, $product->errorss, 'errors');
                                }
                            }
                        }
                        else
                        {
                                $arraybatchinfo = $product->loadBatchInfo($batch);
                                if (count($arraybatchinfo) > 0){
                                        $firstrecord = array_shift($arraybatchinfo);
                                        $dlc=$firstrecord['eatby'];
                                        $dluo=$firstrecord['sellby'];
                                        //var_dump($batch); var_dump($arraybatchinfo); var_dump($firstrecord); var_dump($dlc); var_dump($dluo); exit;
                                }else{
                                        $dlc='';
                                        $dluo='';
                                }

                            if ($depot == '1'){
                                // Remove stock
                                $typemov = $reverse ? 0 : 1;
                                $label = ($this->label).($reverse ? ' CANCEL':'');
                                $invcode = ($this->inventorycode).($reverse ? ' CANCEL':'');
                                $result = $product->correct_stock_batch($user,$id_sw,$qty,$typemov,$label,$pricesrc,$dlc,$dluo,$batch,$invcode);
                                if ($result < 0){
                                        $error++;
                                        $_SESSION['EventMessages'][] = array($product->errors, $product->errorss, 'errors');
                                }
                            }else if ($depot == '2'){
                                // Add stock
                                $typemov = $reverse ? 1 : 0;
                                $label = ($this->label).($reverse ? ' CANCEL':'');
                                $invcode = ($this->inventorycode).($reverse ? ' CANCEL':'');
                                $result = $product->correct_stock_batch($user,$id_tw,$qty,$typemov,$label,$pricedest,$dlc,$dluo,$batch,$invcode);
                                if ($result < 0){
                                        $error++;
                                        $_SESSION['EventMessages'][] = array($product->errors, $product->errorss, 'errors');
                                }
                            }
                        }
                }
                else
                {
                        dol_print_error('',"Bad value saved into sessions");
                        $error++;
                }
        }

	if (! $error){

                // == update the stock movements IDs stored at $transfer->products array
                    if (!$reverse){
                        // = get IDs of stock movements
                            $fk_entrepot = $depot == '1' ? $this->fk_depot1 : $this->fk_depot2;
                            $sql = "SELECT * FROM ".MAIN_DB_PREFIX."stock_mouvement";
                            $sql.= " ORDER BY rowid DESC";
                            $sql.= " LIMIT ".count($this->products);
                            $last_stock_movements = array();
                            $resql = $this->db->query($sql);
                            if ($resql) {
                                while($mov = $resql->fetch_assoc()){
                                    $pid = $mov['fk_product'];
                                    if ($mov['fk_entrepot']==$fk_entrepot
                                            && !empty($this->products[$pid])){
                                        $this->products[$pid]['m'.$depot] = $mov['rowid'];
                                    }
                                }
                            }
                    }else{
                        // = empty the IDs of the stock movements
                        foreach ($this->products as $pid=>$p){
                            $this->products[$pid]['m'.$depot] = '';
                        }
                    }

                // = to save IDs of the stock movements
                // = it will let us to delete that movements later if it's requested by user
                $this->update();
		$this->db->commit();

		$_SESSION['EventMessages'][] = array($langs->trans("StockMovementRecorded"), null, 'mesgs');
                return 1;
	}else{
		$this->db->rollback();
		$_SESSION['EventMessages'][] = array($langs->trans("Error"), null, 'errors');
                return -1;
	}
    }


    /**
     *		\brief      Load an object from its id and create a new one in database
     *		\param      fromid     		Id of object to clone
     * 	 	\return		int				New id of clone
     */
    function createFromClone($fromid)
    {
        global $user,$langs;

        $error=0;

        $object=new ImpBM_rule($this->db);

        $this->db->begin();

        // Load source object
            $object->fetch($fromid);
            $object->id=0;
            $object->statut=0;

        // Clear fields
        // ...

        // Create clone
            $result=$object->create($user);

        // Other options
            if ($result < 0)
            {
                $this->error=$object->error;
                $error++;
            }

        // End
            if (! $error)
            {
                    $this->db->commit();
                    return $object->id;
            }
            else
            {
                    $this->db->rollback();
                    return -1;
            }
    }


    /**
     *		\brief		Initialise object with example values
     *		\remarks	id must be 0 if object instance is a specimen.
     */
    function initAsSpecimen()
    {
        $this->id='';
        $this->pattern='';
        $this->ruleOrder='';
        $this->fk_account='';
        $this->category='';
    }

    /**
     *		\brief		Unserialize s_products
     */
    function unserializeProducts()
    {
        $products = array();
        if (!empty($this->s_products)){
            $arr = @unserialize($this->s_products);
            if (is_array($arr)){
                foreach ($arr as $arr2){
                    if (!empty($arr2['id'])) $products[$arr2['id']] = $arr2;
                }
            }
        }
        $this->products = $products;
        $this->n_products = count($products);
    }

    /*
     * return an array with current stock of products in the origin depot
     */
    function getStock(){
        $stock = array();
        if ($this->fk_depot1 > 0 && count($this->products) > 0){
                $sql = "SELECT fk_product,reel ";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_stock";
		$sql.= " WHERE fk_entrepot = ".$this->fk_depot1;
		$sql.= " AND fk_product IN (".implode(',', array_keys($this->products)).")";
                $resql = $this->db->query($sql);
                if ($resql){
                    if ($this->db->num_rows($resql)){
                        while ($row = $resql->fetch_assoc()){
                            if (is_array($row)) $stock[$row['fk_product']] = $row['reel'];
                        }
                    }
                    $this->db->free($resql);
                }else{
                    $this->error="Error ".$this->db->lasterror();
                    dol_syslog(get_class($this)."::getStock ".$this->error, LOG_ERR);
                }
        }
        //echo _var_export($stock,'$stock');die();
        return $stock;
    }

    /*
     * Returns the last $max transfers
     */
    function getLatestTransfers($vars)
    {
    	global $langs;

        $max = !empty($vars['max']) ? intval($vars['max']) : 5;

        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."stocktransfers_transfers";
        $sql.= " ORDER BY rowid DESC";
        $sql.= " LIMIT ".$max;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);

        $elements = array();
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                while($row = $resql->fetch_assoc()){
                    if (is_array($row)) $elements[] = $row;
                }
            }
            $this->db->free($resql);
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
        }

        return $elements;
    }

}
?>
