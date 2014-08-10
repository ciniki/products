<?php
//
// Description
// ===========
// This method will return the existing categories and tags for products.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 		The ID of the business to get the item from.
// 
// Returns
// -------
//
function ciniki_products_productTags($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productTags'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	$rsp = array('stat'=>'ok');

	//
	// Get the list of categories
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
	$rc = ciniki_core_tagsList($ciniki, 'ciniki.products', $args['business_id'], 'ciniki_product_tags', 10);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['tags']) ) {
		$rsp['categories'] = $rc['tags'];
	} else {
		$rsp['categories'] = array();
	}

	//
	// Check if all subcategories should be returned
	//
	$strsql = "SELECT DISTINCT tag_type, tag_name "
		. "FROM ciniki_product_tags "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND tag_type > 10 AND tag_type < 30 "
		. "ORDER BY tag_type, tag_name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'types', 'fname'=>'tag_type', 'name'=>'type',
			'fields'=>array('tag_type')),
		array('container'=>'tags', 'fname'=>'tag_name', 'name'=>'tag', 
			'fields'=>array('type'=>'tag_type', 'name'=>'tag_name')),
		));
	if( isset($rc['types']) ) {
		foreach($rc['types'] as $type) {
			$rsp['subcategories-' . $type['type']['tag_type']] = $type['type']['tags'];
		}
	}

	//
	// Get the list of tags
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
	$rc = ciniki_core_tagsList($ciniki, 'ciniki.products', $args['business_id'], 'ciniki_product_tags', 20);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['tags']) ) {
		$rsp['tags'] = $rc['tags'];
	} else {
		$rsp['tags'] = array();
	}

	return $rsp;
}
?>
