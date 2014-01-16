<?php
//
// Description
// -----------
// This method returns the list of suppliers matching the keyword.
//
// Arguments
// ---------
// 
// Returns
// -------
// <suppliers>
//		<supplier name="RJ Spagnols"/>
//		...
// </suppliers>
//
function ciniki_products_supplierSearch($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search String'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.supplierSearch', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

	$strsql = "SELECT id, name "
		. "FROM ciniki_product_suppliers "
		. "WHERE ciniki_product_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_product_suppliers.name != '' "
		. "";
	if( isset($args['start_needle']) && $args['start_needle'] != '' ) {
		$strsql .= "AND (ciniki_product_suppliers.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_product_suppliers.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%') "
			. "";
	}
	$strsql .= "ORDER BY ciniki_product_suppliers.name "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'suppliers', 'fname'=>'name', 'name'=>'supplier',
			'fields'=>array('id', 'name')),
		));
	if( $rc != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['suppliers']) ) {
		return array('stat'=>'ok', 'suppliers'=>array());
	}
	$suppliers = $rc['suppliers'];

	return array('stat'=>'ok', 'suppliers'=>$suppliers);
}
?>
