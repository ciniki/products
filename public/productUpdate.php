<?php
//
// Description
// -----------
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
function ciniki_products_productUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'), 
		'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
		'type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type'),
		'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
		'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
        'barcode'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Barcode'), 
        'supplier_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Supplier'), 
        'supplier_product_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Supplier Product'), 
        'supplier_item_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Supplier Item Number'), 
        'supplier_minimum_order'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Supplier Minimum Order'), 
        'supplier_order_multiple'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Supplier Order Multiple'), 
        'price'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Price'), 
        'cost'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Cost'), 
        'msrp'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'MSRP'), 
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
        'short_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Brief Description'), 
        'long_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Short Description'), 
        'start_date'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Start Date'), 
        'end_date'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'End Date'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Webflags'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productUpdate', $args['product_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	if( isset($args['name']) ) {
		$args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 \-]/', '', 
			strtolower($args['name'])));

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
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1494', 'msg'=>'You already have a product with that name, please choose another'));
		}
	}

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.products');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Update the product
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.products.product', 
		$args['product_id'], $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the details
	//
	$detail_fields = array(
		'wine_type'=>'wine_type',
		'kit_length'=>'kit_length',
		'winekit_oak'=>'winekit_oak',
		'winekit_body'=>'winekit_body',
		'winekit_sweetness'=>'winekit_sweetness',
		);
	foreach($detail_fields as $field => $detail_field) {
		if( isset($args[$field]) ) {
			$strsql = "INSERT INTO ciniki_product_details (business_id, product_id, "
				. "detail_key, detail_value, date_added, last_updated) VALUES ("
				. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $detail_field) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $args[$field]) . "', "
				. "UTC_TIMESTAMP(), UTC_TIMESTAMP() "
				. ") "
				. "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.products');
			if( $rc['stat'] != 'ok' ) { 
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
				return $rc;
			}
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.products', 
				'ciniki_product_history', $args['business_id'], 
				2, 'ciniki_product_details', $args['product_id'], $detail_field, $args[$field]);
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
		'args'=>array('id'=>$args['product_id']));

	return array('stat'=>'ok');
}
?>
