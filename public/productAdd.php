<?php
//
// Description
// -----------
// This function will add a new product to the products production module.
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_products_productAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'name'=>array('required'=>'yes', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Name'),
		'type'=>array('required'=>'no', 'default'=>'1', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Type'),
		'category'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Category'),
		'permalink'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Permalink'),
        'source'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Source'), 
        'flags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Flags'), 
        'status'=>array('required'=>'no', 'default'=>'10', 'blank'=>'yes', 'name'=>'Status'), 
        'barcode'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Barcode'), 
        'supplier_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Supplier'), 
        'supplier_product_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Supplier Product'), 
        'supplier_item_number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Supplier Item Number'), 
        'supplier_minimum_order'=>array('required'=>'no', 'default'=>'1', 'blank'=>'yes', 'name'=>'Supplier Minimum Order'), 
        'supplier_order_multiple'=>array('required'=>'no', 'default'=>'1', 'blank'=>'yes', 'name'=>'Supplier Order Multiple'), 
        'manufacture_min_time'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Manufacture Minimum Time'), 
        'manufacture_max_time'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Manufacture Maximum Time'), 
        'inventory_flags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Inventory Flags'), 
        'inventory_current_num'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Current Inventory Number'), 
        'price'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Price'), 
        'cost'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Cost'),
        'msrp'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'type'=>'currency', 'name'=>'MSRP'),
        'primary_image_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Image'),
        'short_description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Brief Description'),
        'long_description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Long Description'),
        'start_date'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Start Date'),
        'end_date'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'End Date'),
        'webflags'=>array('required'=>'no', 'default'=>'1', 'blank'=>'yes', 'name'=>'Webflags'),
		// Details
        'wine_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Wine Type'), 
        'kit_length'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Rack Length'), 
        'winekit_oak'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Oak'), 
        'winekit_body'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Body'), 
        'winekit_sweetness'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sweetness'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productAdd', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	if( $args['supplier_id'] == '' ) {
		$args['supplier_id'] = 0;
	}

	if( !isset($args['permalink']) || $args['permalink'] == '' ) {
		$args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 \-]/', '', strtolower($args['name'])));
	}

	//
	// Check the permalink does not already exist
	//
	$strsql = "SELECT id "
		. "FROM ciniki_products "
		. "WHERE permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['product']) || (isset($rc['rows']) && count($rc['rows']) > 0) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1493', 'msg'=>'You already have a product with that name, please choose another'));
	}

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.products');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the product
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.products.product', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$product_id = $rc['id'];

	//
	// Add the wine type and kit_length to the product details
	//
	$detail_fields = array(
		'wine_type'=>'wine_type',
		'kit_length'=>'kit_length',
		'winekit_oak'=>'winekit_oak',
		'winekit_body'=>'winekit_body',
		'winekit_sweetness'=>'winekit_sweetness',
		);
	foreach($detail_fields as $field => $detail_field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$strsql = "INSERT INTO ciniki_product_details (business_id, product_id, "
				. "detail_key, detail_value, date_added, last_updated) VALUES ("
				. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $product_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $detail_field) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $args[$field]) . "', "
				. "UTC_TIMESTAMP(), UTC_TIMESTAMP() "
				. ")";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.products');
			if( $rc['stat'] != 'ok' ) { 
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
				return $rc;
			}
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.products', 
				'ciniki_product_history', $args['business_id'], 
				1, 'ciniki_product_details', $product_id, $detail_field, $args[$field]);
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.products');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'products');

	$ciniki['syncqueue'][] = array('push'=>'ciniki.products.product',
		'args'=>array('id'=>$product_id));

	return array('stat'=>'ok', 'id'=>$product_id);
}
?>
