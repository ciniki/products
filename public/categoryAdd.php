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
function ciniki_products_categoryAdd($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'category'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
		'subcategory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sub-Category'),
		'name'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Name'),
		'subname'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Sub Name'),
		'sequence'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Sequence'),
		'tag_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tag Type'),
		'display'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category Format'),
		'subcategorydisplay'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sub Category Format'),
		'productdisplay'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Product Format'),
		'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Image'),
		'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Synopsis'),
		'description'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Description'),
		'webflags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Options'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.categoryAdd', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Add the category
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	return ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.products.category', $args, 0x07);
}
?>
