<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_products_hooks_uiSettings($ciniki, $business_id, $args) {

	$settings = array();

	//
	// Get the product types
	//
	$strsql = "SELECT id, name_s, name_p, object_def "
		. "FROM ciniki_product_types "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY name_s "
		. "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'types', 'fname'=>'id',
			'fields'=>array('id', 'name_s', 'name_p', 'object_def')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['types']) ) {	
		$settings['types'] = array();
		foreach($rc['types'] as $tid => $type) {
			$object_def = unserialize($type['object_def']);
			$object_def['id'] = $type['id'];
			$settings['types'][] = array('type'=>$object_def);
		}
	}

	return array('stat'=>'ok', 'settings'=>$settings);	
}
?>
