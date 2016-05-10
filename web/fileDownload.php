<?php
//
// Description
// ===========
// This function will return the file details and content so it can be sent to the client.
//
// Returns
// -------
//
function ciniki_products_web_fileDownload($ciniki, $business_id, $product_permalink, $file_permalink) {

    //
    // Get the business storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'hooks', 'storageDir');
    $rc = ciniki_businesses_hooks_storageDir($ciniki, $business_id, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $business_storage_dir = $rc['storage_dir'];

	//
	// Get the file details
	//
	$strsql = "SELECT ciniki_product_files.id, "
		. "ciniki_product_files.uuid, "
		. "ciniki_product_files.name, "
		. "ciniki_product_files.permalink, "
		. "ciniki_product_files.extension, "
		. "ciniki_product_files.binary_content "
		. "FROM ciniki_products, ciniki_product_files "
		. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_products.permalink = '" . ciniki_core_dbQuote($ciniki, $product_permalink) . "' "
		. "AND ciniki_products.id = ciniki_product_files.product_id "
		. "AND ciniki_product_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND CONCAT_WS('.', ciniki_product_files.permalink, ciniki_product_files.extension) = '" . ciniki_core_dbQuote($ciniki, $file_permalink) . "' "
		. "AND (ciniki_product_files.webflags&0x01) > 0 "		// Make sure file is to be visible
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'file');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['file']) ) {
		return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'1500', 'msg'=>'Unable to find requested file'));
	}
	$rc['file']['filename'] = $rc['file']['name'] . '.' . $rc['file']['extension'];

    //
    // Get the storage filename
    //
    $storage_filename = $business_storage_dir . '/ciniki.products/files/' . $rc['file']['uuid'][0] . '/' . $rc['file']['uuid'];
    if( file_exists($storage_filename) ) {
        $rc['file']['binary_content'] = file_get_contents($storage_filename);    
    }

	return array('stat'=>'ok', 'file'=>$rc['file']);
}
?>
