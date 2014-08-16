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
function ciniki_products_sapos_itemSearch($ciniki, $business_id, $args) {

	if( !isset($args['start_needle']) || $args['start_needle'] == '' ) {
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
		. "ciniki_products.code, "
		. "ciniki_products.name, "
		. "ciniki_products.price AS unit_amount, "
		. "ciniki_products.unit_discount_amount, "
		. "ciniki_products.unit_discount_percentage, "
		. "ciniki_products.taxtype_id, "
		. "ciniki_product_prices.id AS price_id, "
		. "ciniki_product_prices.name AS price_name, "
		. "ciniki_product_prices.unit_amount AS price_unit_amount, "
		. "ciniki_product_prices.unit_discount_amount AS price_unit_discount_amount, "
		. "ciniki_product_prices.unit_discount_percentage AS price_unit_discount_percentage, "
		. "ciniki_product_prices.taxtype_id AS price_taxtype_id "
		. "FROM ciniki_products "
		. "LEFT JOIN ciniki_product_prices ON ("
			. "ciniki_products.id = ciniki_product_prices.product_id "
			. "AND ciniki_product_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. (isset($args['pricepoint_id'])?"AND ciniki_product_prices.pricepoint_id = '" . ciniki_core_dbQuote($ciniki, $args['pricepoint_id']) . "' ":'')
			. ") "
		. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (ciniki_products.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_products.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_products.code LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_products.code LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. ") "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'products', 'fname'=>'id',
			'fields'=>array('id', 'code', 'name', 'unit_amount', 
				'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id')),
		array('container'=>'prices', 'fname'=>'price_id',
			'fields'=>array('id'=>'price_id', 'name'=>'price_name', 
				'unit_amount'=>'price_unit_amount', 
				'unit_discount_amount'=>'price_unit_discount_amount', 
				'unit_discount_percentage'=>'price_unit_discount_percentage',
				'taxtype_id'=>'price_taxtype_id')),
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
		if( isset($product['prices']) ) {
			foreach($product['prices'] as $pid => $price) {
				$details = array(
					'status'=>0,
					'object'=>'ciniki.products.product',
					'object_id'=>$product['id'],
					'description'=>($product['code']!=''?$product['code'].' - ':'') . $product['name'],
					'quantity'=>1,
					'unit_amount'=>$price['unit_amount'],
					'unit_discount_amount'=>$price['unit_discount_amount'],
					'unit_discount_percentage'=>$price['unit_discount_percentage'],
					'price_id'=>$price['id'],
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
				'description'=>($product['code']!=''?$product['code'].' - ':'') . $product['name'],
				'quantity'=>1,
				'unit_amount'=>$product['unit_amount'],
				'unit_discount_amount'=>$product['unit_discount_amount'],
				'unit_discount_percentage'=>$product['unit_discount_percentage'],
				'price_id'=>0,
				'taxtype_id'=>$product['taxtype_id'], 
				'notes'=>'',
				);
			$items[] = array('item'=>$details);
		}
	}

	return array('stat'=>'ok', 'items'=>$items);		
}
?>
