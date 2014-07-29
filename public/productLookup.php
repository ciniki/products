<?php
//
// Description
// -----------
// This method will lookup a product in the database.  This is used
// by the import scripts to check if a product exists.  This method is
// more flexible than productGet and more rigid than productSearch.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_products_productLookup($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'product_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Product'),
		'code'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Product Code'),
		'prices'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Prices'),
		'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'),
		'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
		'similar'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Similar Products'),
		'recipes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Recommended Recipes'),
		'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'All Categories'),
		'subcategories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sub Categories'),
		'tags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'All Tags'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productLookup', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Check that either product_id or code is specified
	//
	if( (!isset($args['code']) || $args['code'] == '') 
		&& (!isset($args['product_id']) || $args['product_id'] == '') 
		) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1846', 'msg'=>'You must specify either the product_id or the code.'));
	}

	//
	// See if the product exists by code
	// 
	$strsql = "SELECT id "
		. "FROM ciniki_products "
		. "WHERE code = '" . ciniki_core_dbQuote($ciniki, $args['code']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['product']) ) {
		$args['product_id'] = $rc['product']['id'];
	} else {
		if( $rc['num_rows'] == 0 ) {
			return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'1847', 'msg'=>'Product code does not exist.'));
		} 
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1848', 'msg'=>'Multiple products exist with that code.'));
	}

	//
	// Load the product
	//
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'productLoad');
    $rc = ciniki_products_productLoad($ciniki, $args['business_id'], $args['product_id'], $args); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$rsp = array('stat'=>'ok', 'product'=>$rc['product']);

	//
	// Check if all categories should be returned
	//
	if( isset($args['categories']) && $args['categories'] == 'yes' ) {
		//
		// Get the available tags
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
		$rc = ciniki_core_tagsList($ciniki, 'ciniki.products', $args['business_id'], 
			'ciniki_product_tags', 10);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1818', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
		}
		if( isset($rc['tags']) ) {
			$rsp['categories'] = $rc['tags'];
		}
	}

	//
	// Check if all subcategories should be returned
	//
	if( isset($args['subcategories']) && $args['subcategories'] == 'yes' ) {
		//
		// Get the available tags
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
	}

	//
	// Check if all tags should be returned
	//
	if( isset($args['tags']) && $args['tags'] == 'yes' ) {
		//
		// Get the available tags
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
		$rc = ciniki_core_tagsList($ciniki, 'ciniki.products', $args['business_id'], 
			'ciniki_product_tags', 40);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1819', 'msg'=>'Unable to get list of tags', 'err'=>$rc['err']));
		}
		if( isset($rc['tags']) ) {
			$rsp['tags'] = $rc['tags'];
		}
	}

	return $rsp;
}
?>
