<?php
//
// Description
// -----------
// This method returns the list of products.
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
function ciniki_products_productList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'category'=>array('required'=>'no', 'name'=>'Category'),
		'supplier_id'=>array('required'=>'no', 'name'=>'Supplier'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productList', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

	$strsql = "SELECT ciniki_products.id, "
		. "ciniki_products.category, "
		. "ciniki_products.name "
		. "FROM ciniki_products "
		. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	if( isset($args['category']) ) {
		$strsql .= "AND category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' ";
		$strsql .= "ORDER BY category, name ";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id', 'name'=>'product',
				'fields'=>array('id', 'category', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) ) {
			return array('stat'=>'ok', 'products'=>array());
		}
		return array('stat'=>'ok', 'products'=>$rc['products']);
	} elseif( isset($args['supplier_id']) ) {
		$strsql .= "AND supplier_id = '" . ciniki_core_dbQuote($ciniki, $args['supplier_id']) . "' ";
		$strsql .= "ORDER BY name ";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id', 'name'=>'product',
				'fields'=>array('id', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) ) {
			return array('stat'=>'ok', 'products'=>array());
		}
		return array('stat'=>'ok', 'products'=>$rc['products']);
	} else {
		$strsql .= "ORDER BY category, name ";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'categories', 'fname'=>'category', 'name'=>'category',
				'fields'=>array('name'=>'category')),
			array('container'=>'products', 'fname'=>'id', 'name'=>'product',
				'fields'=>array('id', 'category', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['categories']) ) {
			return array('stat'=>'ok', 'categories'=>array());
		}
		return array('stat'=>'ok', 'categories'=>$rc['categories']);
	}

	return $rc;
}
?>
