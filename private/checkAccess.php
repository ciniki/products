<?php
//
// Description
// -----------
// This function will validate the user making the request has the 
// proper permissions to access or change the data.  This function
// must be called by all public API functions to ensure security.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// ciniki:
// business_id:			The ID of the business to check access for.
// method:				The requested method.
// product_id:			The ID of the product requested.
// 
// Returns
// -------
//
function ciniki_products_checkAccess(&$ciniki, $business_id, $method, $product_id=0) {
	//
	// Check if the business is active and the module is enabled
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
	$rc = ciniki_businesses_checkModuleAccess($ciniki, $business_id, 'ciniki', 'products');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( !isset($rc['ruleset']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'698', 'msg'=>'No permissions granted'));
	}
	$modules = $rc['modules'];

	//
	// Sysadmins are allowed full access
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		return array('stat'=>'ok', 'modules'=>$modules);
	}

	//
	// Check if the business is active and the module is enabled
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'getUserPermissions');
	$rc = ciniki_businesses_getUserPermissions($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$perms = $rc['perms'];

	//
	// Check the session user is a business owner
	//
	if( $business_id <= 0 ) {
		// If no business_id specified, then fail
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'400', 'msg'=>'Access denied'));
	}

	// 
	// Owners and Employees have access to everything
	//
	if( ($ciniki['business']['user']['perms']&0x03) > 0 ) {
		return array('stat'=>'ok', 'modules'=>$modules, 'perms'=>$perms);
	}

	//
	// If the user is part of the salesreps, ensure they have access to request method
	//
	$salesreps_methods = array(
		'ciniki.products.productSearch',
		'ciniki.products.productStats',
		'ciniki.products.productList',
		'ciniki.products.categoryDetails',
		);
	if( in_array($method, $salesreps_methods) && ($perms&0x04) == 0x04 ) {
		return array('stat'=>'ok', 'modules'=>$modules, 'perms'=>$perms);
	}

	//
	// By default, deny access
	//
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2258', 'msg'=>'Access denied'));
	
/* OLD CODE
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	//
	// Find any users which are owners of the requested business_id
	//
	$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
		. "AND package = 'ciniki' "
		. "AND status = 10 "
		. "AND (permission_group = 'owners' OR permission_group = 'employees') "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'398', 'msg'=>'Access denied', 'err'=>$rc['err']));
	}
	//
	// If the user has permission, return ok
	//
	if( !isset($rc['rows']) 
		|| !isset($rc['rows'][0]) 
		|| $rc['rows'][0]['user_id'] <= 0 
		|| $rc['rows'][0]['user_id'] != $ciniki['session']['user']['id'] ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'399', 'msg'=>'Access denied'));
	}

	// 
	// At this point, we have ensured the user is a part of the business.
	//

	//
	// Check the product is attached to the business
	//
	if( $product_id > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
		$strsql = "SELECT business_id, id FROM ciniki_products "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
		$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.products', 'products', 'product', array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'401', 'msg'=>'Access denied')));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'517', 'msg'=>'Access denied', 'err'=>$rc['err']));
		}
		if( $rc['num_rows'] != 1 
			|| $rc['products'][0]['product']['business_id'] != $business_id
			|| $rc['products'][0]['product']['id'] != $product_id ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'402', 'msg'=>'Access denied'));
		}
	}

	//
	// All checks passed, return ok
	//
	return array('stat'=>'ok', 'modules'=>$modules); */
}
?>
