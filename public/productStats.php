<?php
//
// Description
// -----------
// This method will return the categories and suppliers with product counts for each.
//
// Arguments
// ---------
// 
// Returns
// -------
// <categories>
//		<category name="Red Wines" num_products="45"/>
// </categories>
// <suppliers>
//		<supplier id="1" name="Red Wines" num_products="45"/>
// </suppliers>
//
function ciniki_products_productStats($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'25', 'name'=>'Limit'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productStats', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

//	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
//	$date_format = ciniki_users_dateFormat($ciniki);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

	//
	// Get the list of categories and counts
	//
	$strsql = "SELECT tag_name AS name, ciniki_product_tags.permalink, "
		. "COUNT(ciniki_products.id) AS num_products "
		. "FROM ciniki_product_tags "
		. "LEFT JOIN ciniki_products ON ("
			. "ciniki_product_tags.product_id = ciniki_products.id "
			. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_product_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_product_tags.tag_type = 10 "
		. "GROUP BY ciniki_product_tags.tag_name "
		. "";
//	$strsql = "SELECT category AS name, "
//		. "IF(category='','Uncategorized',category) AS name, "
//		. "COUNT(id) AS num_products "
//		. "FROM ciniki_products "
//		. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
//		. "";
//	if( isset($args['status']) && $args['status'] != '' ) {
//		$strsql .= "AND ciniki_products.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
//	}
//	$strsql .= "GROUP BY tag_name ";
//	$strsql .= "ORDER BY tag_name "
//		. "";
//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'categories', 'fname'=>'name', 'name'=>'category',
			'fields'=>array('name', 'permalink', 'product_count'=>'num_products')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['categories']) ) {
		$categories = array();
	} else {
		$categories = $rc['categories'];
	}

	//
	// Check for any un-categorized products
	//
	$strsql = "SELECT COUNT(ciniki_products.id) AS num_products, ciniki_product_tags.tag_name "
		. "FROM ciniki_products "
		. "LEFT JOIN ciniki_product_tags ON ("
			. "ciniki_products.id = ciniki_product_tags.product_id "
			. "AND ciniki_product_tags.tag_type = 10 "
			. ") "
		. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ISNULL(tag_name) "
		. "GROUP BY tag_name "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'uncategorized');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['uncategorized']) ) {
		$categories[] = array('category'=>array('name'=>'Uncategorized', 'permalink'=>'', 'product_count'=>$rc['uncategorized']['num_products']));
	}

	//
	// Get the list of suppliers and counts
	//
	$strsql = "SELECT ciniki_products.supplier_id, "
		. "IFNULL(ciniki_product_suppliers.name, '') AS name, "
		. "COUNT(ciniki_products.id) AS num_products "
		. "FROM ciniki_products "
		. "LEFT JOIN ciniki_product_suppliers ON (ciniki_products.supplier_id = ciniki_product_suppliers.id "
			. "AND ciniki_product_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	if( isset($args['status']) && $args['status'] != '' ) {
		$strsql .= "AND ciniki_products.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
	}
	$strsql .= "GROUP BY ciniki_products.supplier_id ";
	$strsql .= "ORDER BY ciniki_product_suppliers.name "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'suppliers', 'fname'=>'name', 'name'=>'supplier',
			'fields'=>array('id'=>'supplier_id', 'name', 'product_count'=>'num_products')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['suppliers']) ) {
		$suppliers = array();
	} else {
		$suppliers = $rc['suppliers'];
	}

	return array('stat'=>'ok', 'categories'=>$categories, 'suppliers'=>$suppliers);
}
?>
