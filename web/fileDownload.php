<?php
//
// Description
// ===========
// This function will return the file details and content so it can be sent to the client.
//
// Returns
// -------
//
function ciniki_products_web_fileDownload($ciniki, $tnid, $product_permalink, $file_permalink) {

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

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
        . "WHERE ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_products.permalink = '" . ciniki_core_dbQuote($ciniki, $product_permalink) . "' "
        . "AND ciniki_products.id = ciniki_product_files.product_id "
        . "AND ciniki_product_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND CONCAT_WS('.', ciniki_product_files.permalink, ciniki_product_files.extension) = '" . ciniki_core_dbQuote($ciniki, $file_permalink) . "' "
        . "AND (ciniki_product_files.webflags&0x01) > 0 "       // Make sure file is to be visible
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.products.155', 'msg'=>'Unable to find requested file'));
    }
    $rc['file']['filename'] = $rc['file']['name'] . '.' . $rc['file']['extension'];

    //
    // Get the storage filename
    //
    $storage_filename = $tenant_storage_dir . '/ciniki.products/files/' . $rc['file']['uuid'][0] . '/' . $rc['file']['uuid'];
    if( file_exists($storage_filename) ) {
        $rc['file']['binary_content'] = file_get_contents($storage_filename);    
    }

    return array('stat'=>'ok', 'file'=>$rc['file']);
}
?>
