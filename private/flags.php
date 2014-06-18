<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_products_flags($ciniki, $modules) {
	$flags = array();
	$flags[] = array('flag'=>array('bit'=>'1', 'name'=>'Similar Products'));
	if( isset($modules['ciniki.recipes']) ) {
		$flags[] = array('flag'=>array('bit'=>'2', 'name'=>'Recommended Recipes'));
	}
	$flags[] = array('flag'=>array('bit'=>'3', 'name'=>'Inventory'));
	$flags[] = array('flag'=>array('bit'=>'4', 'name'=>'Suppliers'));

	return array('stat'=>'ok', 'flags'=>$flags);
}
?>
