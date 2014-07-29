<?php
//
// Description
// -----------
// This method returns the details about a category.
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
function ciniki_products_categoryGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'category'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
		'subcategory'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Category'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.categoryGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Build the query to get the details about a category
	//
	$strsql = "SELECT id, name, sequence, primary_image_id, "
		. "synopsis, description "
		. "FROM ciniki_product_categories "
		. "WHERE category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
		. "AND subcategory = '" . ciniki_core_dbQuote($ciniki, $args['subcategory']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'category');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1844', 'msg'=>'Unable to find category', 'err'=>$rc['err']));
	}
	if( !isset($rc['category']) ) {
		//
		// Setup the default entry
		//
		$category = array('id'=>0,
			'name'=>'',
			'sequence'=>'',
			'primary_image_id'=>'0',
			'synopsis'=>'',
			'description'=>'',
			);
	} else {
		$category = $rc['category'];
	}

	return array('stat'=>'ok', 'category'=>$category);
}
?>
