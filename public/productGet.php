<?php
//
// Description
// -----------
// This function will retreive the information about a product.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_products_productGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
		'prices'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Prices'),
		'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'),
		'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
		'audio'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Audio'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productGet', $args['product_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

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
