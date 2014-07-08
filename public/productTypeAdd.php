<?php
//
// Description
// -----------
// This method will add a new class for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to add the class to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_products_productTypeAdd(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'10', 'name'=>'Status'), 
		'name_s'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
		'name_p'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Plural'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
	$ac = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productTypeAdd');
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
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.products.type', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$product_id = $rc['id'];

	return array('stat'=>'ok', 'id'=>$product_id);
}
?>
