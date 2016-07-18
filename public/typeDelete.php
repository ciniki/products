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
function ciniki_products_typeDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'type_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.typeDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check if the type is currently used in the business for any products
    //
    $strsql = "SELECT COUNT(id) AS num_products "
        . "FROM ciniki_products "
        . "WHERE type_id = '" . ciniki_core_dbQuote($ciniki, $args['type_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['num']) && $rc['num']['num_products'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1815', 'msg'=>'Unable to remove product type, there are still products using it.'));
    }
    
    //
    // Delete the product
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.products.type',
        $args['type_id'], NULL, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1816', 'msg'=>'Unable to delete, internal error.'));
    }

    return array('stat'=>'ok');
}
?>
