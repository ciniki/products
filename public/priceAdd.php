<?php
//
// Description
// ===========
// This method will add a new price for an product.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to add the file to.
// product_id:          The ID of the product the file is attached to.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_products_priceAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
        'pricepoint_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Price Point'),
        'available_to'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Available To'),
        'min_quantity'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Minimum Quantity'),
        'unit_amount'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'currency', 'name'=>'Unit Amount'),
        'unit_discount_amount'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 
            'name'=>'Unit Discount Amount'),
        'unit_discount_percentage'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 
            'name'=>'Unit Discount Percentage'),
        'taxtype_id'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Tax Type'),
        'start_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'datetimetoutc',
            'name'=>'Start Date'),
        'end_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'datetimetoutc',
            'name'=>'End Date'),
        'webflags'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Web Flags'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.priceAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    if( !isset($args['available_to']) || $args['available_to'] == '' ) {
        $args['available_to'] = 1;
    }
    if( !isset($args['min_quantity']) || $args['min_quantity'] == '' ) {
        $args['min_quantity'] = 1;
    }

    //
    // Add the price to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    return ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.products.price', $args);
}
?>
