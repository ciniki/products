<?php
//
// Description
// -----------
// This method will remove a supplier from the database, only if all references
// have been removed.
//
// Returns
// -------
//
function ciniki_products_supplierDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'supplier_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Supplier'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['tnid'], 'ciniki.products.supplierDelete', 0); 
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    //
    // Get the uuid of the product to be deleted
    //
    $strsql = "SELECT uuid FROM ciniki_product_suppliers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['supplier_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'supplier');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['supplier']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.124', 'msg'=>'Unable to find existing supplier'));
    }
    $uuid = $rc['supplier']['uuid'];

    //
    // Check if any products are still attached to this supplier
    //
    $strsql = "SELECT 'products', COUNT(*) "
        . "FROM ciniki_products "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND supplier_id = '" . ciniki_core_dbQuote($ciniki, $args['supplier_id']) . "' "
        . "";
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.products', 'num');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.125', 'msg'=>'Unable to check for products', 'err'=>$rc['err']));
    }
    if( isset($rc['num']['products']) && $rc['num']['products'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.126', 'msg'=>'Unable to delete, products still exist for this supplier.'));
    }

    //
    // Remove the supplier
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.products.supplier',
        $args['supplier_id'], $uuid, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
