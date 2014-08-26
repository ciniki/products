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
function ciniki_products_hooks_inventoryRemove($ciniki, $business_id, $args) {

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
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1943', 'msg'=>'Unable to find product'));
		}
		$product = $rc['product'];

		$rsp = array('stat'=>'ok');
		if( ($product['inventory_flags']&0x01) > 0 ) {
			//
			// Check to make sure the quantity to be removed is a positive value
			//
			if( $args['quantity'] < 0 ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1941', 'msg'=>'Unable to find product'));
			}
			//
			// Check to make sure there is enough in inventory
			//
			if( $product['inventory_current_num'] < $args['quantity'] ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1942', 'msg'=>'There is not enough inventory.'));
			}


			//
			// Reduce the amount in the inventory
			//
			$new_quantity = $product['inventory_current_num'] - $args['quantity'];
			$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.products.product', $product['id'], 
				array('quantity'=>$new_quantity), 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
		
		return $rsp;
	}

	return array('stat'=>'ok');
}
?>
