<?php
//
// Description
// ===========
// This method will be called whenever a item is updated in an invoice.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_products_sapos_itemUpdate($ciniki, $tnid, $invoice_id, $item) {

    if( isset($item['object']) && $item['object'] == 'ciniki.products.product' && isset($item['object_id']) ) {
        //
        // Check the product exists
        //
        $strsql = "SELECT id, inventory_flags, inventory_current_num "
            . "FROM ciniki_products "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['product']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.153', 'msg'=>'Unable to find product.'));
        }
        $product = $rc['product'];

        //
        // If the quantity is different, update the registration
        //
/*      if( isset($item['quantity']) && isset($item['old_quantity'])
            && $item['quantity'] != $item['old_quantity'] ) {
            $quantity_diff = $item['old_quantity'] - $item['quantity'];
            //
            // Update inventory. It's done with a direct query so there isn't race condition on update.
            //
            $strsql = "UPDATE ciniki_products "
                . "SET inventory_current_num = inventory_current_num + '" . ciniki_core_dbQuote($ciniki, $quantity_diff) . "' "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.products');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }

            // Get the new value
            $strsql = "SELECT id, name, "
                . "inventory_flags, "
                . "inventory_current_num "
                . "FROM ciniki_products "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
            if( $rc['stat'] != 'ok' ) { 
                return $rc;
            }
            if( !isset($rc['product']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.154', 'msg'=>'Unable to find product'));
            }
            $product = $rc['product'];
        
            //
            // Update the history
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.products', 'ciniki_product_history', $tnid,
                2, 'ciniki_products', $product['id'], 'inventory_current_num', $product['inventory_current_num']);
        } 
*/

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
