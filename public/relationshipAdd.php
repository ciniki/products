<?php
//
// Description
// -----------
// This method will add a new relationship between products to the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the product belongs to.
// product_id:          The ID of the product to add the relationship to.
// relationship_type:   The type of relationship between the product_id and
//                      the related_id.  
//                      
//                      10 - similar product, cross linked
//                      11 - similar product, don't cross link
//
// related_id:          The ID of the related product.
//
// date_started:        (optional) The date the relationship started. **future**
// date_ended:          (optional) The date the relationship ended. **future**
// notes:               (optional) Any notes about the relationship.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_products_relationshipAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'), 
        'relationship_type'=>array('required'=>'no', 'blank'=>'no', 'default'=>'10',
            'validlist'=>array('10','11'), 'name'=>'Relationship Type'), 
        'related_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Related Product'), 
        'date_started'=>array('required'=>'no', 'type'=>'date', 'default'=>'', 'blank'=>'yes', 'name'=>'Date Started'), 
        'date_ended'=>array('required'=>'no', 'type'=>'date', 'default'=>'', 'blank'=>'yes', 'name'=>'Date Ended'), 
        'notes'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Notes'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.relationshipAdd', $args['product_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check if relationship already exists
    //
    $strsql = "SELECT id "
        . "FROM ciniki_product_relationships "
        . "WHERE ("
            . "("
                . "product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
                . "AND related_id = '" . ciniki_core_dbQuote($ciniki, $args['related_id']) . "' "
            . ") OR ("
                . "product_id = '" . ciniki_core_dbQuote($ciniki, $args['related_id']) . "' "
                . "AND relationship_type = 10 "
                . "AND related_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . ")) "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'relationship');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.114', 'msg'=>'Relationship already exists'));
    }

    //
    // Check to make sure the product and related product are not the same
    //
    if( $args['product_id'] == $args['related_id'] ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.115', 'msg'=>'The products are the same, please choose another product'));
    }

    //
    // Add the relationship
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    return ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.products.relationship', $args, 0x07);
}
?>
