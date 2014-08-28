<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_products_hooks_inventoryReplace($ciniki, $business_id, $args) {

	//
	// Remove product inventory
	//
	if( isset($args['object']) && $args['object'] == 'ciniki.products.product' && isset($args['object_id']) ) {
		//
		// Check the product exists
		//
		$strsql = "SELECT id, name, "
			. "inventory_flags, "
			. "inventory_current_num "
			. "FROM ciniki_products "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['product']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1944', 'msg'=>'Unable to find product'));
		}
		$product = $rc['product'];

		$rsp = array('stat'=>'ok');
		if( ($product['inventory_flags']&0x01) > 0 ) {
			//
			// Reduce the amount in the inventory
			//
			if( $args['quantity'] < 0 ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1945', 'msg'=>'Unable to find product'));
			}
			$new_quantity = $product['inventory_current_num'] + $args['quantity'];
			$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.products.product', $product['id'], 
				array('inventory_current_num'=>$new_quantity), 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
		
		return $rsp;
	}

	return array('stat'=>'ok');
}
?>
