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
	// Load the status maps for the text description of each type
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'maps');
	$rc = ciniki_products_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	//
	// Check if pricepoints are enabled
	//
	if( isset($ciniki['business']['modules']['ciniki.customers'])
		&& ($ciniki['business']['modules']['ciniki.customers']['flags']&0x1000) > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'pricepoints');
		$rc = ciniki_customers_pricepoints($ciniki, $business_id);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['pricepoints']) ) {
			$pricepoints = $rc['pricepoints'];
		}
	}

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
		. "ciniki_products.inventory_flags, "
		. "ciniki_products.inventory_current_num, "
		. "ciniki_product_prices.id AS price_id, "
		. "ciniki_product_prices.name AS price_name, "
		. "ciniki_product_prices.available_to AS available_to_text, "
		. "ciniki_product_prices.pricepoint_id, "
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
	if( isset($args['limit']) && $args['limit'] != '' && preg_match("/^[0-9]+$/", $args['limit']) ) {
		$strsql .= "LIMIT " . $args['limit'];
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'products', 'fname'=>'id',
			'fields'=>array('id', 'code', 'name', 'unit_amount', 
				'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id',
				'inventory_flags', 'inventory_available'=>'inventory_current_num')),
		array('container'=>'prices', 'fname'=>'price_id',
			'fields'=>array('id'=>'price_id', 'name'=>'price_name', 
				'unit_amount'=>'price_unit_amount', 'available_to_text', 'pricepoint_id',
				'unit_discount_amount'=>'price_unit_discount_amount', 
				'unit_discount_percentage'=>'price_unit_discount_percentage',
				'taxtype_id'=>'price_taxtype_id'),
			'flags'=>array('available_to_text'=>$maps['price']['available_to'])),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['products']) ) {
		$products = $rc['products'];
	} else {
		return array('stat'=>'ok', 'items'=>array());
	}

	$product_ids = array();
	foreach($products as $eid => $product) {
		if( ($product['inventory_flags']&0x01) > 0 ) {
			$product_ids[] = $product['id'];
		}
	}

	//
	// Get the reserved quantities for each product
	//
	if( isset($ciniki['business']['modules']['ciniki.sapos']) && count($product_ids) > 0 ) {
		$cur_invoice_id = 0;
		if( isset($args['invoice_id']) && $args['invoice_id'] > 0 ) {
			$cur_invoice_id = $args['invoice_id'];
		}
		$product_ids = array_unique($product_ids);
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getReservedQuantities');
		$rc = ciniki_sapos_getReservedQuantities($ciniki, $business_id, 
			'ciniki.products.product', $product_ids, $cur_invoice_id);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$quantities = $rc['quantities'];
		foreach($products as $pid => $product) {
			if( isset($quantities[$product['id']]) ) {
				$products[$pid]['inventory_available'] -= $quantities[$product['id']]['quantity_reserved'];
				if( $products[$pid]['inventory_available'] < 0 ) {
					$products[$pid]['inventory_available'] = 0;
				}
			}
		}
	}

	$items = array();
	foreach($products as $eid => $product) {
		//
		// Check the inventory available for the product
		//
		$product_ids[] = $product['id'];

		if( isset($product['prices']) ) {
			foreach($product['prices'] as $pid => $price) {
				$details = array(
					'status'=>0,
					'object'=>'ciniki.products.product',
					'object_id'=>$product['id'],
					'description'=>($product['code']!=''?$product['code'].' - ':'') . $product['name'],
					'inventory_available'=>($product['inventory_available']!=null?$product['inventory_available']:''),
					'quantity'=>1,
					'unit_amount'=>$price['unit_amount'],
					'unit_discount_amount'=>$price['unit_discount_amount'],
					'unit_discount_percentage'=>$price['unit_discount_percentage'],
					'price_id'=>$price['id'],
					'taxtype_id'=>$price['taxtype_id'], 
					'notes'=>'',
					);
//				error_log(print_r($pricepoints, true));
				if( $price['name'] != '' ) {
					$details['price_description'] = $price['name'];
				} elseif( isset($price['pricepoint_id']) && $price['pricepoint_id'] > 0 
					&& isset($pricepoints) && isset($pricepoints[$price['pricepoint_id']]) 
					&& isset($pricepoints[$price['pricepoint_id']]['name']) 
					&& $pricepoints[$price['pricepoint_id']]['name'] != '' 
					) {
					$details['price_description'] = $pricepoints[$price['pricepoint_id']]['name'];
				} elseif( isset($price['pricepoint_id']) && $price['pricepoint_id'] > 0 
					&& isset($pricepoints) && isset($pricepoints[$price['pricepoint_id']]) 
					&& isset($pricepoints[$price['pricepoint_id']]['code']) 
					&& $pricepoints[$price['pricepoint_id']]['code'] != '' 
					) {
					$details['price_description'] = $pricepoints[$price['pricepoint_id']]['code'];
				} elseif( isset($price['available_to_text']) && $price['available_to_text'] != '' ) {
					$details['price_description'] = $price['available_to_text'];
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
