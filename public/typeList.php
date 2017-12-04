<?php
//
// Description
// -----------
// This method returns the list of products.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_products_typeList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['tnid'], 'ciniki.products.typeList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    //
    // Load the status maps for the text description of each type
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'typeStatusMaps');
    $rc = ciniki_products_typeStatusMaps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $status_maps = $rc['maps'];

    //
    // Get the list of product types
    //
    $strsql = "SELECT id, "
        . "status, status AS status_text, "
        . "name_s, name_p "
        . "FROM ciniki_product_types "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY name_s ";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'types', 'fname'=>'id', 'name'=>'type',
            'fields'=>array('id', 'status', 'status_text', 'name_s', 'name_p'),
            'maps'=>array('status_text'=>$status_maps)),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['types']) ) {
        return array('stat'=>'ok', 'types'=>array());
    }
    return array('stat'=>'ok', 'types'=>$rc['types']);
}
?>
