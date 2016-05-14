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
			0x01=>'Public',
			0x02=>'Private',
			0x10=>'Customers',
			0x20=>'Members',
			0x40=>'Dealers',
			0x80=>'Distributors',
		),
	);
	$maps['pdfcatalog'] = array(
		'status'=>array(
			'10'=>'Uploaded',
			'20'=>'Processing',
			'30'=>'Active',
		),
	);
	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
