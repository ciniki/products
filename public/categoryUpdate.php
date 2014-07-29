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
function ciniki_products_categoryUpdate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
		'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
		'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'),
		'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
		'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
		'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
		'webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.categoryUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Update the category
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	return ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.products.category', 
		$args['category_id'], $args);
}
?>