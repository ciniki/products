<?php
//
// Description
// ===========
// This function will search the products for the ciniki.sapos module.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_products_sapos_itemSearch($ciniki, $business_id, $start_needle, $limit) {

	if( $start_needle == '' ) {
		return array('stat'=>'ok', 'items'=>array());
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// FIXME: Query for the taxes for products
	//
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
//	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_product_settings', 'business_id', $business_id,
//		'ciniki.artcatalog', 'taxes', 'taxes');
//	if( $rc['stat'] != 'ok' ) {
//		return $rc;
//	}
//	if( isset($rc['taxes']) ) {
//		$tax_settings = $rc['taxes'];
//	} else {
//		$tax_settings = array();
//	}

	//
	// Set the default taxtype for the item
	//
	$taxtype_id = 0;
//	if( isset($tax_settings['taxes-default-taxtype']) ) {
//		$taxtype_id = $tax_settings['taxes-default-taxtype'];
//	}

	//
	// Prepare the query
	//
	$strsql = "SELECT ciniki_products.id, "
		. "ciniki_products.name, "
		. "ciniki_products.price, "
		. "'' AS price_name, "
		. "ciniki_products.unit_discount_amount, "
		. "ciniki_products.unit_discount_percentage, "
		. "ciniki_products.taxtype_id "
		. "FROM ciniki_products "
		. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (ciniki_products.name LIKE '" . ciniki_core_dbQuote($ciniki, $start_needle) . "%' "
			. "OR ciniki_products.name LIKE '% " . ciniki_core_dbQuote($ciniki, $start_needle) . "%' "
			. ") "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'products', 'fname'=>'id',
			'fields'=>array('id', 'name')),
		array('container'=>'prices', 'fname'=>'id',
			'fields'=>array('id', 'name'=>'price_name', 'unit_amount'=>'price', 
				'unit_discount_amount', 'unit_discount_percentage',
				'taxtype_id')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['products']) ) {
		$products = $rc['products'];
	} else {
		return array('stat'=>'ok', 'items'=>array());
	}

	$items = array();
	foreach($products as $eid => $product) {
		if( isset($product['prices']) && count($product['prices']) > 1 ) {
			foreach($product['prices'] as $pid => $price) {
				$details = array(
					'status'=>0,
					'object'=>'ciniki.products.product',
					'object_id'=>$product['id'],
					'description'=>$product['name'],
					'quantity'=>1,
					'unit_amount'=>$price['unit_amount'],
					'unit_discount_amount'=>$price['unit_discount_amount'],
					'unit_discount_percentage'=>$price['unit_discount_percentage'],
					'taxtype_id'=>$price['taxtype_id'], 
					'notes'=>'',
					);
				if( $price['name'] != '' ) {
					$details['description'] .= ' - ' . $price['name'];
				}
				$items[] = array('item'=>$details);
			}
		} else {
			$details = array(
				'status'=>0,
				'object'=>'ciniki.products.product',
				'object_id'=>$product['id'],
				'description'=>$product['name'],
				'quantity'=>1,
				'unit_amount'=>0,
				'unit_discount_amount'=>0,
				'unit_discount_percentage'=>0,
				'taxtype_id'=>0, 
				'notes'=>'',
				);
			if( isset($product['prices']) && count($product['prices']) == 1 ) {
				$price = array_pop($product['prices']);
				if( isset($price['name']) && $price['name'] != '' ) {
					$details['description'] .= ' - ' . $price['name'];
				}
				if( isset($price['unit_amount']) && $price['unit_amount'] != '' ) {
					$details['unit_amount'] = $price['unit_amount'];
				}
				if( isset($price['unit_discount_amount']) && $price['unit_discount_amount'] != '' ) {
					$details['unit_discount_amount'] = $price['unit_discount_amount'];
				}
				if( isset($price['unit_discount_percentage']) && $price['unit_discount_percentage'] != '' ) {
					$details['unit_discount_percentage'] = $price['unit_discount_percentage'];
				}
				if( isset($price['taxtype_id']) && $price['taxtype_id'] != '' ) {
					$details['taxtype_id'] = $price['taxtype_id'];
				}
			}
			$items[] = array('item'=>$details);
		}
	}

	return array('stat'=>'ok', 'items'=>$items);		
}
?>
