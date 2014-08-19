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
function ciniki_products_sapos_itemAdd($ciniki, $business_id, $invoice_id, $item) {

	//
	// A product was added to an invoice item, get the details and see if we need to 
	// create a registration for this product
	//
	if( isset($item['object']) && $item['object'] == 'ciniki.products.product' && isset($item['object_id']) ) {
		//
		// Check the product exists
		//
		$strsql = "SELECT id, name, "
			. "inventory_flags, "
			. "inventory_current_num "
			. "FROM ciniki_products "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['product']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1695', 'msg'=>'Unable to find product'));
		}
		$product = $rc['product'];

		$rsp = array('stat'=>'ok');
		if( ($product['inventory_flags']&0x01) > 0 ) {
			if( ($product['inventory_flags']&0x02) > 0 ) {
				$rsp['flags'] = 6;
			} else {
				$rsp['flags'] = 2;
			}
		}

		//
		// Check if inventory needs to be updated
		//
//		if( ($product['inventory_flags']&0x01) > 0 && isset($item['quantity']) && $item['quantity'] > 0 ) {
			//
			// Update inventory. It's done with a direct query so there isn't race condition on update.
			//
//			$strsql = "UPDATE ciniki_products "
//				. "SET inventory_current_num = inventory_current_num - '" . ciniki_core_dbQuote($ciniki, $item['quantity']) . "' "
//				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
//				. "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
//				. "";
//			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
//			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.products');
//			if( $rc['stat'] != 'ok' ) {
//				return $rc;
//			}
//
//			// Get the new value
//			$strsql = "SELECT id, name, "
//				. "inventory_flags, "
//				. "inventory_current_num "
//				. "FROM ciniki_products "
//				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
//				. "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
//				. "";
//			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
//			if( $rc['stat'] != 'ok' ) {	
//				return $rc;
//			}
//			if( !isset($rc['product']) ) {
//				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1698', 'msg'=>'Unable to find product'));
//			}
//			$product = $rc['product'];
		
			//
			// Update the history
			//
//			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
//			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.products', 'ciniki_product_history', $business_id,
//				2, 'ciniki_products', $product['id'], 'inventory_current_num', $product['inventory_current_num']);
//		}

		return $rsp;
	}

	return array('stat'=>'ok');
}
?>
