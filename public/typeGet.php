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
function ciniki_products_typeGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'type_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.typeGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Load the status maps for the text description of each type
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'typeStatusMaps');
	$rc = ciniki_products_typeStatusMaps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$status_maps = $rc['maps'];

	//
	// Get the basic product information
	//
	$strsql = "SELECT id, "
		. "status, "
		. "status AS status_text, "
		. "name_s, "
		. "name_p, "
		. "object_def "
		. "FROM ciniki_product_types "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['type_id']) . "' "
		. "";
		
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'types', 'fname'=>'id', 'name'=>'type',
			'fields'=>array('id', 'status', 'status_text', 'name_s', 'name_p', 'object_def'),
			'maps'=>array('status_text'=>$status_maps)),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['types'][0]['type']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1809', 'msg'=>'Unable to find the specified product type'));
	}
	$type = $rc['types'][0]['type'];

	//
	// Expand the object_def
	//
	$object_def = unserialize($type['object_def']);

	if( isset($object_def['parent']['products']) ) {
		foreach($object_def['parent']['products'] as $field => $fdetails) {
			$type['parent_product_' . $field] = 'on';
			if( isset($object_def['parent']['products'][$field]['name']) ) {
				$type['parent_product_' . $field . '-name'] = $object_def['parent']['products'][$field]['name'];
			}
		}
	}
	if( isset($object_def['parent']['prices']) ) {
		foreach($object_def['parent']['prices'] as $field => $fdetails) {
			$type['parent_price_' . $field] = 'on';
		}
	}
	if( isset($object_def['parent']['categories']) ) { $type['parent_categories'] = 'on'; }
	if( isset($object_def['parent']['subcategories']) ) { $type['parent_subcategories'] = 'on'; }
	if( isset($object_def['parent']['tags']) ) { $type['parent_tags'] = 'on'; }
	if( isset($object_def['parent']['images']) ) { $type['parent_images'] = 'on'; }
	if( isset($object_def['parent']['files']) ) { $type['parent_files'] = 'on'; }
	if( isset($object_def['parent']['similar']) ) { $type['parent_similar'] = 'on'; }
	if( isset($object_def['parent']['recipes']) ) { $type['parent_recipes'] = 'on'; }
	if( isset($object_def['child']['products']) ) {
		foreach($object_def['child']['products'] as $field => $fdetails) {
			$type['child_product_' . $field] = 'on';
			if( isset($object_def['child']['products'][$field]['name']) ) {
				$type['child_product_' . $field . '-name'] = $object_def['child']['products'][$field]['name'];
			}
		}
	}
	if( isset($object_def['child']['prices']) ) {
		foreach($object_def['child']['prices'] as $field => $fdetails) {
			$type['child_price_' . $field] = 'on';
		}
	}
	if( isset($object_def['child']['categories']) ) { $type['child_categories'] = 'on'; }
	if( isset($object_def['child']['subcategories']) ) { $type['child_subcategories'] = 'on'; }
	if( isset($object_def['child']['tags']) ) { $type['child_tags'] = 'on'; }
	if( isset($object_def['child']['images']) ) { $type['child_images'] = 'on'; }
	if( isset($object_def['child']['files']) ) { $type['child_files'] = 'on'; }
	if( isset($object_def['child']['similar']) ) { $type['child_similar'] = 'on'; }
	if( isset($object_def['child']['recipes']) ) { $type['child_recipes'] = 'on'; }

	return array('stat'=>'ok', 'type'=>$type);
}
?>
