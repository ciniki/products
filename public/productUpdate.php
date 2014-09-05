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
        'parent_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Parent'), 
		'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
		'code'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Code'),
		'type_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type'),
		'type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type'),
		'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
		'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
        'barcode'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Barcode'), 
        'supplier_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Supplier'), 
        'supplier_product_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Supplier Product'), 
        'supplier_item_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Supplier Item Number'), 
        'supplier_minimum_order'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Supplier Minimum Order'), 
        'supplier_order_multiple'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Supplier Order Multiple'), 
        'manufacture_min_time'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Manufacture Minimum Time'), 
        'manufacture_max_time'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Manufacture Maximum Time'), 
        'inventory_flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Inventory Flags'), 
        'inventory_current_num'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Current Inventory Number'), 
        'inventory_reorder_num'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Current Reorder Level'), 
        'inventory_reorder_quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Current Reorder Quantity'), 
        'price'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Price'), 
        'cost'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Cost'), 
        'msrp'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'MSRP'), 
        'sell_unit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sell Unit'),
        'shipping_flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Options'), 
        'shipping_weight'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Weight'), 
        'shipping_weight_units'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Weight Units'), 
        'shipping_length'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Length'), 
        'shipping_width'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Width'), 
        'shipping_height'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Height'), 
        'shipping_size_units'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Size Units'), 
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
        'short_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Brief Description'), 
        'long_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Short Description'), 
        'start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Start Date'),
        'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'End Date'),
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Webflags'), 
		// Details
		'detail01'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Detail 01'),
		'detail02'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Detail 02'),
		'detail03'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Detail 03'),
		'detail04'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Detail 04'),
		'detail05'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Detail 05'),
		'detail06'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Detail 06'),
		'detail07'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Detail 07'),
		'detail08'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Detail 08'),
		'detail09'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Detail 09'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'), 
        'subcategories-11'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Sub-Categories'), 
        'subcategories-12'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Sub-Categories'), 
        'subcategories-13'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Sub-Categories'), 
        'subcategories-14'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Sub-Categories'), 
        'subcategories-15'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Sub-Categories'), 
        'tags'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Tags'), 
//        'wine_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Wine Type'), 
//        'kit_length'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Rack Length'), 
//        'winekit_oak'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Oak'), 
//        'winekit_body'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Body'), 
//        'winekit_sweetness'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sweetness'), 
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
	$modules = $rc['modules'];

	if( isset($args['name']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
		$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);

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
	// Check if the inventory was being updated, and then update orders containing this item
	//
	if( isset($args['inventory_current_num']) && $args['inventory_current_num'] != '' ) {
		foreach($modules as $module => $m) {
			list($pkg, $mod) = explode('.', $module);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryUpdated');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $args['business_id'], array(
					'object'=>'ciniki.products.product',
					'object_id'=>$args['product_id'],
					'new_inventory_level'=>$args['inventory_current_num'],
					));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2009', 'msg'=>'Unable to update inventory levels.', 'err'=>$rc['err']));
				}
			}
		}
	}

	//
	// Update the details
	//
//	$detail_fields = array(
//		'wine_type'=>'wine_type',
//		'kit_length'=>'kit_length',
//		'winekit_oak'=>'winekit_oak',
//		'winekit_body'=>'winekit_body',
//		'winekit_sweetness'=>'winekit_sweetness',
//		);
//	foreach($detail_fields as $field => $detail_field) {
//		if( isset($args[$field]) ) {
//			$strsql = "INSERT INTO ciniki_product_details (business_id, product_id, "
//				. "detail_key, detail_value, date_added, last_updated) VALUES ("
//				. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
//				. "'" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "', "
//				. "'" . ciniki_core_dbQuote($ciniki, $detail_field) . "', "
//				. "'" . ciniki_core_dbQuote($ciniki, $args[$field]) . "', "
//				. "UTC_TIMESTAMP(), UTC_TIMESTAMP() "
//				. ") "
//				. "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' "
//				. ", last_updated = UTC_TIMESTAMP() "
//				. "";
//			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.products');
//			if( $rc['stat'] != 'ok' ) { 
//				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
//				return $rc;
//			}
//			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.products', 
//				'ciniki_product_history', $args['business_id'], 
//				2, 'ciniki_product_details', $args['product_id'], $detail_field, $args[$field]);
//		}
//	}

	//
	// Update the categories
	//
	if( isset($args['categories']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
		$rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.products', 'tag', $args['business_id'],
			'ciniki_product_tags', 'ciniki_product_history',
			'product_id', $args['product_id'], 10, $args['categories']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
			return $rc;
		}
	}

	//
	// Update the subcategories
	//
	for($i=11;$i<30;$i++) {
		if( isset($args['subcategories-'.$i]) ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
			$rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.products', 'tag', $args['business_id'],
				'ciniki_product_tags', 'ciniki_product_history',
				'product_id', $args['product_id'], $i, $args['subcategories-'.$i]);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
				return $rc;
			}
		}
	}

	//
	// Update the tags
	//
	if( isset($args['tags']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
		$rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.products', 'tag', $args['business_id'],
			'ciniki_product_tags', 'ciniki_product_history',
			'product_id', $args['product_id'], 40, $args['tags']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
			return $rc;
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
