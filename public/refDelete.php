<?php
//
// Description
// -----------
// This method will remove an object reference from a product.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to remove the relationship from.
// ref_id:              The ID of the product reference to delete.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_products_refDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'ref_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object Reference'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.refDelete', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Delete the reference
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    return ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.products.ref', $args['ref_id'], NULL, 0x07);
}
?>
