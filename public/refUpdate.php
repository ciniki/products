<?php
//
// Description
// -----------
// This method will update an existing product reference.  
//
// The object cannot be changed, only the object_id.  If it should be a different object,
// then the reference needs to be deleted and added new.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the product belongs to.
// ref_id:              The ID of the product reference to update.
// object_id:           The ID of the object.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_products_refUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'ref_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product Reference'), 
        'object_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Object'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.refUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the existing product_id and related_id to make sure we're not adding a duplicate
    //
    $strsql = "SELECT id, product_id, object, object_id "
        . "FROM ciniki_product_refs "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['ref_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'ref');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.111', 'msg'=>'Unable to find existing product reference'));
    }
    $ref = $rc['ref'];

    //
    // Check for blank or 0 products
    //
    if( isset($args['object_id']) && ($args['object_id'] == '' || $args['object_id'] == '0') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.112', 'msg'=>'Please specify a product reference.'));
    }

    //
    // Check if product reference already exists
    //
    if( isset($args['object_id']) ) {
        $product_id = $ref['product_id'];
        $object = $ref['object'];
        $strsql = "SELECT id "
            . "FROM ciniki_product_refs "
            . "WHERE product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
            . "AND object = '" . ciniki_core_dbQuote($ciniki, $object) . "' "
            . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'ref');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.113', 'msg'=>'Reference already exists for this product'));
        }
    }

    //
    // Update the existing product reference
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.products.ref',
        $args['ref_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
