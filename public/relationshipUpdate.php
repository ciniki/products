<?php
//
// Description
// -----------
// This method will update an existing product relationship.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the product belongs to.
// product_id:          (optional) The ID of the product that is related to related_id. 
// relationship_id:     The ID of the relationship to change the details for.
// relationship_type:   (optional) The type of relationship between the product_id and
//                      the related_id.  
//
//                      10 - similar product
//
// related_id:          (optional) The ID of the related product.
//
// date_started:        (optional) The date the relationship started.
// date_ended:          (optional) The date the relationship ended.
// notes:               (optional) Any notes about the relationship.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_products_relationshipUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'relationship_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Relationship'), 
        'product_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Product'), 
        'relationship_type'=>array('required'=>'no', 'blank'=>'no', 
            'validlist'=>array('10', '11'), 'name'=>'Relationship Type'), 
        'related_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Related Product'), 
        'date_started'=>array('required'=>'no', 'type'=>'date', 'blank'=>'yes', 'name'=>'Date Started'), 
        'date_ended'=>array('required'=>'no', 'type'=>'date', 'blank'=>'yes', 'name'=>'Date Ended'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.relationshipUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the existing product_id and related_id to make sure we're not adding a duplicate
    //
    $strsql = "SELECT id, product_id, related_id "
        . "FROM ciniki_product_relationships "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['relationship_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'relationship');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['relationship']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.120', 'msg'=>'Unable to find existing relationship'));
    }
    $relationship = $rc['relationship'];

    if( (isset($args['product_id']) && $args['product_id'] == $relationship['related_id'])
        || (isset($args['related_id']) && $args['related_id'] == $relationship['product_id'])
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.121', 'msg'=>'The product is the same, please choose another'));
    }

    //
    // Check for blank or 0 products
    //
    if( (isset($args['product_id']) && ($args['product_id'] == '' || $args['product_id'] == '0') )
        || (isset($args['related_id']) && ($args['related_id'] == '' || $args['related_id'] == '0') )
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.122', 'msg'=>'Please specify a product.'));
    }

    //
    // Check if relationship already exists
    //
    if( isset($args['product_id']) || isset($args['related_id']) ) {
        if( isset($args['product_id']) ) {
            $product_id = $args['product_id'];
            $related_id = $relationship['related_id'];
        } elseif( isset($args['related_id']) ) {
            $product_id = $relationship['product_id'];
            $related_id = $args['related_id'];
        }
        $strsql = "SELECT id "
            . "FROM ciniki_product_relationships "
            . "WHERE ("
                . "("
                    . "product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
                    . "AND related_id = '" . ciniki_core_dbQuote($ciniki, $related_id) . "' "
                . ") OR ("
                    . "product_id = '" . ciniki_core_dbQuote($ciniki, $related_id) . "' "
                    . "AND related_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
                . ")) "
                . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'relationship');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.123', 'msg'=>'Relationship already exists'));
        }
    }

    //
    // Update the existing relationship
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.products.relationship',
        $args['relationship_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
