<?php
//
// Description
// -----------
// This method will update a supplier to the tenant.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_products_supplierUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'supplier_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Supplier'), 
        'name'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Name'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['tnid'], 'ciniki.products.supplierUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Add the supplier
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.products.supplier', 
        $args['supplier_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    return $rc;
}
?>
