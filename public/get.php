<?php
//
// Description
// -----------
// This function will retreive the information about a product.
//
// Info
// ----
// Status: 			started
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <product id="1" name="CC Merlot" wine_type="red" kit_length="
function ciniki_products_get($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'product_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No product specified'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.get', $args['product_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$date_format = ciniki_users_dateFormat($ciniki);
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Get the basic product information
	//
	$strsql = "SELECT ciniki_products.id, ciniki_products.name, price, cost, msrp "
		. "FROM ciniki_products "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_products.id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['product']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'438', 'msg'=>'Invalid product'));
	}
	$product = $rc['product'];

	//
	// Get the product details
	//
	$strsql = "SELECT detail_key, detail_value FROM ciniki_product_details "
		. "WHERE product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFetchHashRow');
	$rc = ciniki_core_dbQuery($ciniki, $strsql, 'ciniki.products');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$result_handle = $rc['handle'];

	$result = ciniki_core_dbFetchHashRow($ciniki, $result_handle);
	while( isset($result['row']) ) {
		$product[$result['row']['detail_key']] = $result['row']['detail_value'];
		$result = ciniki_core_dbFetchHashRow($ciniki, $result_handle);
	}

	return array('stat'=>'ok', 'product'=>$product);
}
?>
