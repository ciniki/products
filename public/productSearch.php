<?php
//
// Description
// -----------
// Search products by name
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// search_str:		The search string provided by the user.
// 
// Returns
// -------
//
function ciniki_products_productSearch($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
        'inventoried'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Inventoried Products Only'), 
        'reserved'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Reserved Quantities'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productSearch', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	//
	// Get the number of products in each status for the business, 
	// if no rows found, then return empty array
	//
	$strsql = "SELECT id, IF(code<>'', CONCAT_WS(' - ', code, name), name) AS name, "
		. "code, type, status, "
		. "IF((inventory_flags&0x01)=1,inventory_current_num,'') AS inventory_current_num "
		. "FROM ciniki_products "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND status = 10 ";
	if( isset($args['inventoried']) && $args['inventoried'] == 'yes' ) {
		$strsql .= "AND (inventory_flags&0x01) = 0x01 ";
	}
	$strsql .= "AND (name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR code LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR code LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "";
	if( is_numeric($args['start_needle']) ) {
		$strsql .= "OR barcode LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' ";
	}
	$strsql .= ") "
		. "ORDER BY name DESC ";
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";	// is_numeric verified
	} else {
		$strsql .= "LIMIT 25 ";
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'products', 'fname'=>'id', 'name'=>'product',
			'fields'=>array('id', 'code', 'name', 'inventory_current_num')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['products']) ) {
		return array('stat'=>'ok', 'products'=>array());
	}
	$products = $rc['products'];

	//
	// Get the reserved quantities for each product
	//
	if( isset($args['reserved']) && $args['reserved'] == 'yes' ) {
		$product_ids = array();
		foreach($products as $pid => $product) {
			$product_ids[] = $product['product']['id'];
			$products[$pid]['product']['rsv'] = 0;
			$products[$pid]['product']['bo'] = '';
		}
		$product_ids = array_unique($product_ids);
		if( isset($ciniki['business']['modules']['ciniki.sapos']) ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getReservedQuantities');
			$rc = ciniki_sapos_getReservedQuantities($ciniki, $args['business_id'], 
				'ciniki.products.product', $product_ids, 0);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$quantities = $rc['quantities'];
			foreach($products as $pid => $product) {
				if( isset($quantities[$product['product']['id']]) ) {
					$products[$pid]['product']['rsv'] = (float)$quantities[$product['product']['id']]['quantity_reserved'];
					$bo = $products[$pid]['product']['rsv'] - $product['product']['inventory_current_num'];
					if( $bo > 0 ) {
						$products[$pid]['product']['bo'] = $bo;
					}
				}
			}
		}
	}

	return array('stat'=>'ok', 'products'=>$products);
}
?>
