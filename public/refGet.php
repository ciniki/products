<?php
//
// Description
// -----------
// This method will return the details about an object ref that is linked to the product.
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
function ciniki_products_refGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'ref_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object Reference'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.refGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Build the query to get the details about a relationship, including the related product id and name.
	//
	$strsql = "SELECT ciniki_product_refs.id, "
		. "ciniki_product_refs.product_id, "
		. "ciniki_product_refs.object, "
		. "ciniki_product_refs.object_id "
		. "FROM ciniki_product_refs "
		. "WHERE ciniki_product_refs.id = '" . ciniki_core_dbQuote($ciniki, $args['ref_id']) . "' "
		. "AND ciniki_product_refs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'ref');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1543', 'msg'=>'Unable to find reference', 'err'=>$rc['err']));
	}
	if( !isset($rc['ref']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1544', 'msg'=>'Reference does not exist'));
	}
	$ref = $rc['ref'];

	//
	// Load the name of the reference
	//
	$ref['object_name'] = '';
	if( $ref['object'] == 'ciniki.recipes.recipe' ) {
		$strsql = "SELECT name "
			. "FROM ciniki_recipes "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.recipes', 'recipe');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['recipe']) ) {
			$ref['object_name'] = $rc['recipe']['name'];
		}
	}

	return array('stat'=>'ok', 'ref'=>$ref);
}
?>
