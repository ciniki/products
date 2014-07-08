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
		'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'),
		'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
		'similar'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Similar Products'),
		'recipes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Recommended Recipes'),
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
	$product = $rc['product'];

	return array('stat'=>'ok', 'product'=>$product);
}
?>
