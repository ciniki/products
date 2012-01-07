<?php
//
// Description
// -----------
// Search products by name
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// search_str:		The search string provided by the user.
// 
// Returns
// -------
//
function ciniki_products_searchQuick($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'category_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'errmsg'=>'No category specified'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No search specified'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No limit specified'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.searchQuick', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the number of products in each status for the business, 
	// if no rows found, then return empty array
	//
	$strsql = "SELECT id, name, type, status FROM ciniki_products "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND status = 1 ";
	if( $args['category_id'] > 0 ) {
		$strsql .= "AND category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
	}
	$strsql .= "AND (name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. ") "
		. "ORDER BY name DESC ";
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";	// is_numeric verified
	} else {
		$strsql .= "LIMIT 25 ";
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'products', 'products', 'product', array('stat'=>'ok', 'products'=>array()));
}
?>
