<?php
//
// Description
// -----------
// This function will clean up the history for products.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_products_historyFix($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
	$rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.historyFix', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFixTableHistory');

	//
	// Update the history for ciniki_productss
	//
	$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.products', $args['business_id'],
		'ciniki_products', 'ciniki_product_history', 
		array('uuid', 'name', 'type', 'source', 'flags', 'status',
			'barcode', 'supplier_business_id', 'supplier_product_id', 'price', 'cost', 'msrp'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check for items missing a UUID
	//
	$strsql = "UPDATE ciniki_product_history SET uuid = UUID() WHERE uuid = ''";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.products');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Remote any entries with blank table_key, they are useless we don't know what they were attached to
	//
	$strsql = "DELETE FROM ciniki_product_history WHERE table_key = ''";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.products');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
