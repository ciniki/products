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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1608', 'msg'=>'No product specified.'));
	}

	//
	// Prepare the query
	//
	if( $args['object'] == 'ciniki.products.product' ) {
		$strsql = "SELECT ciniki_products.id, "
			. "ciniki_products.name, "
			. "ciniki_products.price, "
			. "'' AS price_name, "
			. "ciniki_products.unit_discount_amount, "
			. "ciniki_products.unit_discount_percentage, "
			. "inventory_flags, inventory_current_num, "
			. "ciniki_products.taxtype_id "
			. "FROM ciniki_products "
			. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_products.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id',
				'fields'=>array('id', 'description'=>'name',
					'unit_amount'=>'price', 'unit_discount_amount', 'unit_discount_percentage',
					'inventory_flags', 'inventory_current_num', 
					'taxtype_id')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) || count($rc['products']) < 1 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1605', 'msg'=>'No product found.'));		
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

		return array('stat'=>'ok', 'item'=>$product);
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1607', 'msg'=>'No product specified.'));		
}
?>
