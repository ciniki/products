<?php
//
// Description
// -----------
// This method will remove a product from the database, only if all references
// have been removed.
//
// Returns
// -------
//
function ciniki_products_productDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productDelete', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// get the active modules for the business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'getActiveModules');
    $rc = ciniki_businesses_getActiveModules($ciniki, $args['business_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

	//
	// Get the uuid of the product to be deleted
	//
	$strsql = "SELECT uuid FROM ciniki_products "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['product']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'893', 'msg'=>'Unable to find existing product'));
	}
	$uuid = $rc['product']['uuid'];

	//
	// Check for wine production orders
	//
	if( isset($modules['ciniki.wineproductions']) ) {
		$strsql = "SELECT 'wineproductions', COUNT(*) "
			. "FROM ciniki_wineproductions "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.products', 'num');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'896', 'msg'=>'Unable to check for wine orders', 'err'=>$rc['err']));
		}
		if( isset($rc['num']['wineproductions']) && $rc['num']['wineproductions'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'897', 'msg'=>'Unable to delete, wine orders still exist for this product.'));
		}
	}

	//  
	// Turn off autocommit
	//  
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.products');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}

	//
	// Delete any relationships
	//
	$strsql = "SELECT id, uuid "
		. "FROM ciniki_product_relationships "
		. "WHERE (product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
			. "OR related_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
			. ") "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'relationship');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
		return $rc;
	}
	if( isset($rc['rows']) ) {
		$relationships = $rc['rows'];
		foreach($relationships as $relationship) {
			$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.products.relationship',
				$relationship['id'], $relationship['uuid'], 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
				return $rc;
			}
		}
	}
	
	//
	// Delete the product details
	//
	$strsql = "DELETE FROM ciniki_product_details "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
		. "";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.products');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'901', 'msg'=>'Unable to delete, internal error.'));
	}
	// FIXME: Does this need history logged for details delete?

	//
	// Delete the product
	//
	$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.products.product',
		$args['product_id'], $uuid, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'898', 'msg'=>'Unable to delete, internal error.'));
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.products');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'products');

	$ciniki['syncqueue'][] = array('push'=>'ciniki.products.product', 
		'args'=>array('delete_uuid'=>$uuid, 'delete_id'=>$args['product_id']));

	return array('stat'=>'ok');
}
?>
