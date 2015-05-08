<?php
//
// Description
// ===========
// This function will lookup an object for adding to an invoice/cart.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_products_sapos_itemLookup($ciniki, $business_id, $args) {

	if( !isset($args['object']) || $args['object'] == '' 
		|| !isset($args['object_id']) || $args['object_id'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1960', 'msg'=>'No product specified.'));
	}

	//
	// Lookup the requested product if specified along with a price_id
	//
	if( $args['object'] == 'ciniki.products.product' && isset($args['price_id']) && $args['price_id'] > 0 ) {
		$strsql = "SELECT ciniki_products.id, "
			. "ciniki_products.parent_id, "
			. "ciniki_products.code, "
			. "ciniki_products.name, "
			. "ciniki_product_prices.id AS price_id, "
			. "ciniki_product_prices.pricepoint_id AS pricepoint_id, "
			. "ciniki_product_prices.name AS price_name, "
			. "ciniki_product_prices.unit_amount, "
			. "ciniki_product_prices.unit_discount_amount, "
			. "ciniki_product_prices.unit_discount_percentage, "
			. "inventory_flags, inventory_current_num, "
			. "ciniki_product_prices.taxtype_id, "
			. "ciniki_product_types.object_def "
			. "FROM ciniki_product_prices "
			. "LEFT JOIN ciniki_products ON ("
				. "ciniki_product_prices.product_id = ciniki_products.id "
				. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND ciniki_products.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
				. ") "
			. "LEFT JOIN ciniki_product_types ON ("
				. "ciniki_products.type_id = ciniki_product_types.id "
				. "AND ciniki_product_types.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_product_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_product_prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id',
				'fields'=>array('id', 'price_id', 'parent_id', 'code', 'description'=>'name',
					'pricepoint_id', 
					'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
					'inventory_flags', 'inventory_current_num', 
					'taxtype_id')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) || count($rc['products']) < 1 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1962', 'msg'=>'No product found.'));		
		}
		$product = array_pop($rc['products']);

		// Check if product has inventory or unlimited
		if( ($product['inventory_flags']&0x01) > 0 ) {
			if( ($product['inventory_flags']&0x02) > 0 ) {
				$product['limited_units'] = 'no';
				$product['flags'] = 0x46;	// Inventoried and backorder available
			} else {
				$product['limited_units'] = 'yes';
				$product['flags'] = 0x42;	// Inventoried, no backorder
			}
			$product['units_available'] = $product['inventory_current_num'];
			if( $product['inventory_current_num'] <= 0 && $product['flags'] == 0x46 ) {
				$product['flags'] |= 0x0100;
			}
		} else {
			$product['limited_units'] = 'no';
			$product['units_available'] = 0;
		}

		return array('stat'=>'ok', 'item'=>$product);
	}

	//
	// Lookup an existing object and pricepoint
	//
	if( $args['object'] == 'ciniki.products.product' 
		&& isset($args['object_id']) && $args['object_id'] > 0
		&& isset($args['pricepoint_id']) && $args['pricepoint_id'] > 0 
		) {
		$strsql = "SELECT ciniki_products.id, "
			. "ciniki_products.parent_id, "
			. "ciniki_products.code, "
			. "ciniki_products.name, "
			. "ciniki_product_prices.id AS price_id, "
			. "ciniki_product_prices.pricepoint_id AS pricepoint_id, "
			. "ciniki_product_prices.name AS price_name, "
			. "ciniki_product_prices.unit_amount, "
			. "ciniki_product_prices.unit_discount_amount, "
			. "ciniki_product_prices.unit_discount_percentage, "
			. "inventory_flags, inventory_current_num, "
			. "ciniki_product_prices.taxtype_id "
			. "FROM ciniki_products "
			. "LEFT JOIN ciniki_product_prices ON ("
				. "ciniki_products.id = ciniki_product_prices.product_id "
				. "AND ciniki_product_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND ciniki_product_prices.pricepoint_id = '" . ciniki_core_dbQuote($ciniki, $args['pricepoint_id']) . "' "
				. ") "
			. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_products.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id',
				'fields'=>array('id', 'price_id', 'parent_id', 'code', 'description'=>'name',
					'pricepoint_id', 
					'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
					'inventory_flags', 'inventory_current_num', 
					'taxtype_id')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) || count($rc['products']) < 1 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2039', 'msg'=>'No product found.'));		
		}
		$product = array_pop($rc['products']);

		// Check if product has inventory or unlimited
		if( ($product['inventory_flags']&0x01) > 0 ) {
			if( ($product['inventory_flags']&0x02) > 0 ) {
				$product['limited_units'] = 'no';
				$product['flags'] = 0x46;	// Inventoried and backorder available
			} else {
				$product['limited_units'] = 'yes';
				$product['flags'] = 0x42;	// Inventoried, no backorder
			}
			$product['units_available'] = $product['inventory_current_num'];
			if( $product['inventory_current_num'] <= 0 && $product['flags'] == 0x46 ) {
				$product['flags'] |= 0x0100;
			}
		} else {
			$product['limited_units'] = 'no';
			$product['units_available'] = 0;
		}

		return array('stat'=>'ok', 'item'=>$product);
	}

	//
	// Lookup the specified item based on the product id
	//
	// Currently Broken, must use prices sub table
	if( $args['object'] == 'ciniki.products.product' ) {
		$strsql = "SELECT ciniki_products.id, "
			. "ciniki_products.parent_id, "
			. "ciniki_products.code, "
			. "ciniki_products.name, "
			. "ciniki_products.price, "
			. "'' AS price_name, "
			. "ciniki_products.unit_discount_amount, "
			. "ciniki_products.unit_discount_percentage, "
			. "inventory_flags, inventory_current_num, "
			. "ciniki_products.taxtype_id, "
			. "ciniki_product_types.object_def "
			. "FROM ciniki_products "
			. "LEFT JOIN ciniki_product_types ON ("
				. "ciniki_products.type_id = ciniki_product_types.id "
				. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_products.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id',
				'fields'=>array('id', 'parent_id', 'code', 'description'=>'name',
					'unit_amount'=>'price', 'unit_discount_amount', 'unit_discount_percentage',
					'inventory_flags', 'inventory_current_num', 
					'taxtype_id')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) || count($rc['products']) < 1 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1961', 'msg'=>'No product found.'));		
		}
		$product = array_pop($rc['products']);
		// Check if product has inventory or unlimited
		if( ($product['inventory_flags']&0x01) > 0 ) {
			$product['limited_units'] = 'yes';
			$product['units_available'] = $product['inventory_current_num'];
		} else {
			$product['limited_units'] = 'no';
			$product['units_available'] = 0;
		}
		// Check if product has inventory or unlimited
		$product['flags'] = 0;
		if( ($product['inventory_flags']&0x01) > 0 ) {
			if( ($product['inventory_flags']&0x02) > 0 ) {
				$product['limited_units'] = 'no';
				$product['flags'] = 0x46;	// Inventoried and backorder available
			} else {
				$product['limited_units'] = 'yes';
				$product['flags'] = 0x42;	// Inventoried, no backorder
			}
			$product['units_available'] = $product['inventory_current_num'];
			if( $product['inventory_current_num'] <= 0 && $product['flags'] == 0x46 ) {
				$product['flags'] |= 0x0100;
			}
		} else {
			$product['limited_units'] = 'no';
			$product['units_available'] = 0;
		}
		return array('stat'=>'ok', 'item'=>$product);
	} 

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1963', 'msg'=>'No product specified.'));		
}
?>
