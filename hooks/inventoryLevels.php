<?php
//
// Description
// ===========
// This will return the amount of inventory for products.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_products_hooks_inventoryLevels($ciniki, $business_id, $args) {

	if( !isset($args['object']) || $args['object'] == '' 
		|| !isset($args['object_ids']) || $args['object_ids'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1979', 'msg'=>'No products specified.'));
	}

	//
	// Lookup the requested item based on the price ID
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
	if( $args['object'] == 'ciniki.products.product' ) {
		$strsql = "SELECT ciniki_products.id, inventory_current_num AS quantity "
			. "FROM ciniki_products "
			. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_products.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['object_ids']) . ") "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'quantities', 'fname'=>'id',
				'fields'=>array('object_id'=>'id', 'inventory_quantity'=>'quantity')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['quantities']) || count($rc['quantities']) < 1 ) {
			return array('stat'=>'ok', 'quantities'=>array());
		}
		return array('stat'=>'ok', 'quantities'=>$rc['quantities']);
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1992', 'msg'=>'No product specified.'));		
}
?>
