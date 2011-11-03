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
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
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
    require_once($ciniki['config']['core']['modules_dir'] . '/products/private/checkAccess.php');
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.get', $args['product_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
    require_once($ciniki['config']['core']['modules_dir'] . '/users/private/dateFormat.php');
    require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	$date_format = ciniki_users_dateFormat($ciniki);
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Get the basic product information
	//
	$strsql = "SELECT products.id, products.name, price, cost, msrp "
		. "FROM products "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND products.id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'products', 'product');
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
	$strsql = "SELECT detail_key, detail_value FROM product_details "
		. "WHERE product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' ";
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuery.php');
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbFetchHashRow.php');
	$rc = ciniki_core_dbQuery($ciniki, $strsql, 'products');
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
