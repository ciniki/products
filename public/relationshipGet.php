<?php
//
// Description
// -----------
// This method will return the details about a product relationship with another product.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the relationship from.
// relationship_id:		The ID of the relationship to get.
// 
// Returns
// -------
//
function ciniki_products_relationshipGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'relationship_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Relationship'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.relationshipGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

	//
	// Build the query to get the details about a relationship, including the related product id and name.
	//
	$strsql = "SELECT ciniki_product_relationships.id, ciniki_product_relationships.product_id, "
		. "relationship_type, related_id, "
		. "IFNULL(DATE_FORMAT(date_started, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_started, "
		. "IFNULL(DATE_FORMAT(date_ended, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_ended, "
		. "ciniki_product_relationships.notes, "
		. "ciniki_products.name AS product_name "
		. "FROM ciniki_product_relationships "
		. "LEFT JOIN ciniki_products ON ("
			. "(ciniki_product_relationships.product_id <> '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
			. "AND ciniki_product_relationships.product_id = ciniki_products.id "
			. ") OR ("
			. "ciniki_product_relationships.related_id <> '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
			. "AND ciniki_product_relationships.related_id = ciniki_products.id "
			. ")) "
		. "WHERE ciniki_product_relationships.id = '" . ciniki_core_dbQuote($ciniki, $args['relationship_id']) . "' "
		. "AND ciniki_product_relationships.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'relationship');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1532', 'msg'=>'Unable to find relationship', 'err'=>$rc['err']));
	}
	if( !isset($rc['relationship']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1533', 'msg'=>'Relationship does not exist'));
	}
	$relationship = $rc['relationship'];

	return array('stat'=>'ok', 'relationship'=>$relationship);
}
?>
