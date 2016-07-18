<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to add the class mage to.
// name:                The name of the image.  
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_products_typeUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'type_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'), 
        'name_s'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'), 
        'name_p'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Plural'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.typeUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Get the existing details
    //
    $strsql = "SELECT id, uuid, name_s, name_p, object_def "
        . "FROM ciniki_product_types "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['type_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1808', 'msg'=>'Product type not found'));
    }
    $item = $rc['item'];

    $object_def = unserialize($item['object_def']);

    //
    // Update the object definition
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'typeObjectDefUpdate');
    $rc = ciniki_products_typeObjectDefUpdate($ciniki, $object_def, $ciniki['request']['args']);

    if( isset($args['name_s']) && isset($args['name_p']) ) {
        $rc['object_def']['name'] = array();
    }
    if( isset($args['name_s']) ) {
        $rc['object_def']['name']['single'] = $args['name_s'];
    }
    if( isset($args['name_p']) ) {
        $rc['object_def']['name']['plural'] = $args['name_p'];
    }

    $new_object_def = serialize($rc['object_def']);
    if( $new_object_def != $item['object_def'] ) {
        $args['object_def'] = $new_object_def;
    }

    //
    // Update the class in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    return ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.products.type', 
        $args['type_id'], $args);
}
?>
