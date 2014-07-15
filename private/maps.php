<?php
//
// Description
// -----------
// This function returns the array of status text for ciniki_products.status.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_products_maps($ciniki) {

	$maps = array();
	$maps['product'] = array(
		'status'=>array(
			'10'=>'Active',
			'60'=>'Inactive',
		),
	);
	$maps['price'] = array(
		'available_to'=>array(
			0x01=>'Customers',
			0x02=>'Private',
			0x10=>'Members',
			0x20=>'Dealers',
			0x40=>'Distributors',
		),
	);
	
	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
