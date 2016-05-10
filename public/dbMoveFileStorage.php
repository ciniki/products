<?php
//
// Description
// -----------
// This method will check the products are stored in ciniki-storage and then it
// will clear the image blob from the datbase.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_products_dbMoveFileStorage($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'clear'=>array('required'=>'no', 'default'=>'no', 'name'=>'Clear DB Blob Content'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
	$rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.dbMoveFileStorage');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the business storage directory
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'hooks', 'storageDir');
	$rc = ciniki_businesses_hooks_storageDir($ciniki, $args['business_id'], array());
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$business_storage_dir = $rc['storage_dir'];
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFixTableHistory');

    ini_set('memory_limit', '4192M');

    $strsql = "SELECT id, uuid, binary_content "
        . "FROM ciniki_product_files "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND binary_content <> '' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'image');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['rows']) ) { 
        return array('stat'=>'ok');
    }

    //
    // Got through the products and check all of them are stored in the storage directory
    //
    $files = $rc['rows'];
    foreach($files as $file) {
        $storage_filename = $business_storage_dir . '/ciniki.products/files/' . $file['uuid'][0] . '/' . $file['uuid'];
        if( !is_dir(dirname($storage_filename)) ) {
            if( !mkdir(dirname($storage_filename), 0700, true) ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3405', 'msg'=>'Unable to add file'));
            }
        }
        if( !file_exists($storage_filename) ) { 
            file_put_contents($storage_filename, $file['binary_content']);
        }
        if( isset($args['clear']) && $args['clear'] == 'yes' && file_exists($storage_filename) ) {
            $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.products.file', $file['id'], array('binary_content'=>''));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

	return array('stat'=>'ok');
}
?>
