<?php
//
// Description
// ===========
// This function will return the list of products and their current inventories.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_products_sapos_cartItemsDetails($ciniki, $business_id, $args) {

	if( !isset($args['object']) || $args['object'] == '' 
		|| !isset($args['object_ids']) || $args['object_ids'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1955', 'msg'=>'No product specified.'));
	}

	//
	// Lookup the requested item based on the price ID
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
	if( $args['object'] == 'ciniki.products.product' ) {
		$strsql = "SELECT ciniki_products.id, "
			. "permalink, "
			. "inventory_current_num AS quantity "
			. "FROM ciniki_products "
			. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_products.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['object_ids']) . ") "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'details', 'fname'=>'id',
				'fields'=>array('object_id'=>'id', 'permalink', 'quantity_inventory'=>'quantity')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['details']) || count($rc['details']) < 1 ) {
			return array('stat'=>'ok', 'details'=>array());
		}
		return array('stat'=>'ok', 'details'=>$rc['details']);
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1956', 'msg'=>'No product specified.'));		
}
?>
