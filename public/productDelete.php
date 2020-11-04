<?php
//
// Description
// -----------
// This method will remove a product from the database, only if all references
// have been removed.
//
// Returns
// -------
//
function ciniki_products_productDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['tnid'], 'ciniki.products.productDelete', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // get the active modules for the tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'getActiveModules');
    $rc = ciniki_tenants_getActiveModules($ciniki, $args['tnid']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    //
    // Get the uuid of the product to be deleted
    //
    $strsql = "SELECT uuid FROM ciniki_products "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.87', 'msg'=>'Unable to find existing product'));
    }
    $uuid = $rc['product']['uuid'];

    //
    // Check if any modules are currently using this product
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.products.product', $args['product_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.179', 'msg'=>'Unable to check if product is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.180', 'msg'=>"The product is still in use. " . $rc['msg']));
    }

    //
    // Check for sapos
    //
    if( isset($modules['ciniki.sapos']) ) {
        $strsql = "SELECT 'invoices', COUNT(*) "
            . "FROM ciniki_sapos_invoice_items "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND object = 'ciniki.products.product' "
            . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "";
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.products', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.90', 'msg'=>'Unable to check for orders', 'err'=>$rc['err']));
        }
        if( isset($rc['num']['invoices']) && $rc['num']['invoices'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.91', 'msg'=>'Unable to delete, orders still exist for this product.'));
        }
    }

    //  
    // Turn off autocommit
    //  
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Delete any relationships
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_product_relationships "
        . "WHERE (product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "OR related_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . ") "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'relationship');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $relationships = $rc['rows'];
        foreach($relationships as $relationship) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.products.relationship',
                $relationship['id'], $relationship['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
                return $rc;
            }
        }
    }
    
    //
    // Delete the product details
    //
    $strsql = "DELETE FROM ciniki_product_details "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.92', 'msg'=>'Unable to delete, internal error.'));
    }
    // FIXME: Does this need history logged for details delete?

    //
    // Delete the product
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.products.product',
        $args['product_id'], $uuid, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.93', 'msg'=>'Unable to delete, internal error.'));
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'products');

    $ciniki['syncqueue'][] = array('push'=>'ciniki.products.product', 
        'args'=>array('delete_uuid'=>$uuid, 'delete_id'=>$args['product_id']));

    return array('stat'=>'ok');
}
?>
