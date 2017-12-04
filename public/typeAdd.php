<?php
//
// Description
// -----------
// This method will add a new class for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to add the class to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_products_typeAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'10', 'name'=>'Status'), 
        'name_s'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'name_p'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Plural'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $ac = ciniki_products_checkAccess($ciniki, $args['tnid'], 'ciniki.products.typeAdd');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Build the object
    //
    $object_def = array(
        'name'=>array('single'=>$args['name_s'], 'plural'=>$args['name_p']),
        'parent'=>array(
            'products'=>array(),
        ),
    );
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'typeObjectDefUpdate');
    $rc = ciniki_products_typeObjectDefUpdate($ciniki, $object_def, $ciniki['request']['args']);
    $args['object_def'] = serialize($rc['object_def']);

    //
    // Add the product type to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.products.type', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $product_id = $rc['id'];

    return array('stat'=>'ok', 'id'=>$product_id);
}
?>
