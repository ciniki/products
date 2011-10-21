<?php
//
// Description
// -----------
// This function will return a list of wine kits
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
// <products>
//		<product id="1" name="CC Merlot" type="red" kit_length="4"
// </products>
//
function ciniki_products_listWineKits($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'type'=>array('required'=>'no', 'default'=>'', 'errmsg'=>'No type specified'),
		'sorting'=>array('required'=>'no', 'default'=>'', 'errmsg'=>'No type specified'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.listWineKits', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// FIXME: Add timezone information from business settings
	//
	date_default_timezone_set('America/Toronto');
	$todays_date = strftime("%Y-%m-%d");

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/dateFormat.php');
	$date_format = ciniki_users_dateFormat($ciniki);

    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuoteIDs.php');

	$strsql = "SELECT products.id, products.name, "
		. "IFNULL(d1.detail_value, '') AS wine_type, "
		. "IFNULL(d2.detail_value, '') AS kit_length "
		. "FROM products "
		. "LEFT JOIN product_details AS d1 ON (products.id = d1.product_id AND d1.detail_key = 'wine_type') "
		. "LEFT JOIN product_details AS d2 ON (products.id = d2.product_id AND d2.detail_key = 'kit_length') "
		. "WHERE products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND products.type = 64 "
		. "";

	if( $args['sorting'] != 'name' ) {
		$strsql .= "ORDER BY products.name, wine_type DESC ";
	} else {
		$strsql .= "ORDER BY products.name DESC ";
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'products', 'products', 'product', array('stat'=>'ok', 'products'=>array()));
	if( $rc != 'ok' ) {
		return $rc;
	}

	if( !isset($rc['orders']) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'435', 'msg'=>'Unable to find any orders'));
	}

	return $rc;
}
?>
