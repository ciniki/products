<?php
//
// Description
// ===========
// This function will lookup an item that is being added to a shopping cart online.  This function
// has extra checks to make sure the requested item is available to the customer.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_products_sapos_cartItemLookup($ciniki, $business_id, $customer, $args) {

	if( !isset($args['object']) || $args['object'] == '' 
		|| !isset($args['object_id']) || $args['object_id'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1608', 'msg'=>'No product specified.'));
	}

	//
	// Lookup the requested product if specified along with a price_id
	//
	if( $args['object'] == 'ciniki.products.product' && isset($args['price_id']) && $args['price_id'] > 0 ) {
		$strsql = "SELECT ciniki_products.id, "
			. "ciniki_products.parent_id, "
			. "ciniki_products.name, "
			. "ciniki_product_prices.id AS price_id, "
			. "ciniki_product_prices.name AS price_name, "
			. "ciniki_product_prices.pricepoint_id, "
			. "ciniki_product_prices.available_to, "
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
				'fields'=>array('id', 'price_id', 'parent_id', 'description'=>'name',
					'pricepoint_id', 'available_to',
					'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
					'inventory_flags', 'inventory_current_num', 
					'taxtype_id')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) || count($rc['products']) < 1 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1804', 'msg'=>'No product found.'));		
		}
		$product = array_pop($rc['products']);

		//
		// Check the pricepoint_id is valid for this customer, only if specified
		//
		if( $product['pricepoint_id'] > 0 ) {
			if( !isset($customer['pricepoint']['id']) || $customer['pricepoint']['id'] == 0 ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1839', 'msg'=>"I'm sorry, but this product is not available to you."));
			}
			if( $product['pricepoint_id'] != $customer['pricepoint']['id'] ) {
				if( !isset($customer['pricepoint']['sequence']) || $customer['pricepoint']['sequence'] == '' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1841', 'msg'=>"I'm sorry, but this product is not available to you."));
				}
				// Get the sequence for this pricepoint and see if it's lower than customers pricepoint_sequence
				$strsql = "SELECT sequence "
					. "FROM ciniki_customer_pricepoints "
					. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $product['pricepoint_id']) . "' "
					. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "";
				$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'pricepoint');
				if( $rc['stat'] != 'ok' ) {	
					return $rc;
				}
				if( !isset($rc['pricepoint']) ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1840', 'msg'=>"I'm sorry but we seem to be having difficulty updating your shopping cart.  Please call customer support."));
				}
				if( $rc['pricepoint']['sequence'] > $customer['pricepoint']['sequence'] ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1842', 'msg'=>"I'm sorry, but this product is not available to you."));
				}
			}
		}

		//
		// Check the available_to is correct for the specified customer
		//
		if( ($product['available_to']|0xF0) > 0 ) {
			if( ($product['available_to']&$customer['price_flags']) == 0 ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1843', 'msg'=>"I'm sorry, but this product is not available to you."));
			}
		}

		// Check if product has inventory or unlimited
		if( ($product['inventory_flags']&0x01) > 0 ) {
			if( ($product['inventory_flags']&0x02) > 0 ) {
				$product['limited_units'] = 'no';
				$product['flags'] = 4;
			} else {
				$product['limited_units'] = 'yes';
				$product['flags'] = 0;
			}
			$product['units_available'] = $product['inventory_current_num'];
		} else {
			$product['limited_units'] = 'no';
			$product['units_available'] = 0;
		}

		return array('stat'=>'ok', 'item'=>$product);
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1607', 'msg'=>'No product specified.'));		
}
?>
