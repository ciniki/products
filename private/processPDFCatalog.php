<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//

function ciniki_products_processPDFCatalog(&$ciniki, $business_id, $catalog_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'insertFromImagick');

    //
    // Load the pdf catalog
    //
    $strsql = "SELECT id, uuid, status, permalink "
        . "FROM ciniki_product_pdfcatalogs "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $catalog_id) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND status = 10 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'catalog');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['catalog']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3412', 'msg'=>'Catalog does not exist'));
    }
    $catalog = $rc['catalog'];
   
    //
    // Update the pdf catalog status to lock
    //
    $strsql = "UPDATE ciniki_product_pdfcatalogs SET status = 20 "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $catalog_id) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND status = 10 "
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_affected_rows'] < 1 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3413', 'msg'=>'Unable to lock catalog for processing.'));
    }

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
    // Check the file exists
    $storage_filename = $business_storage_dir . '/ciniki.products/pdfcatalogs/' . $catalog['uuid'][0] . '/' . $catalog['uuid'];
    if( !file_exists($storage_filename) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3414', 'msg'=>'Unable to open pdf.'));
    }

    //
    // Copy to tmp directory so it's local for processing. Remove files take too long to open over and over for each page.
    //
    if( isset($ciniki['config']['ciniki.core']['tmp_dir']) ) {
        $tmp_filename = $ciniki['config']['ciniki.core']['tmp_dir'] . '/' . $catalog['uuid'];
    } else {
        $tmp_filename = '/tmp/' . $catalog['uuid'];
    }

    if( !copy($storage_filename, $tmp_filename) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3458', 'msg'=>'Unable to copy pdf.'));
    }

    ini_set('memory_limit', '4096M');

    $imagick = new Imagick();
    $imagick->setResolution(300, 300);

    $imagick->pingImage($tmp_filename);
    $num_pages = $imagick->getNumberImages();
    $imagick->clear();
    $imagick->destroy();

    $imagick = new Imagick();
    $imagick->setResolution(300, 300);

    $page_number = 0;
    for($page_number = 0; $page_number < $num_pages; $page_number++) {
        $imagick->readImage($tmp_filename . '[' . $page_number . ']');
        $imagick = $imagick->flattenImages();
        $imagick->setImageFormat('jpeg');
       
        //
        // Add the image
        //
        $rc = ciniki_images_hooks_insertFromImagick($ciniki, $business_id, array(
            'image'=>$imagick,
            'original_filename'=>$catalog['permalink'] . '-' . ($page_number+1) . '.jpg',
            'name'=>'',
            'force_duplicate'=>'no',
            'perms'=>1,
            ));
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3424', 'msg'=>"Unable to save image for page $page_number.", 'err'=>$rc['err']));
        }
        $image_id = $rc['id'];

        //
        // Add the pdfcatalog image
        //
        $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.products.pdfcatalogimage', array(
            'catalog_id'=>$catalog_id,
            'page_number'=>($page_number+1),
            'image_id'=>$image_id,
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3442', 'msg'=>"Unable to save image for page $page_number."));
        }
    }

    unlink($tmp_filename);

    //
    // Update the pdf catalog status to lock
    //
    $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.products.pdfcatalog', $catalog_id, array(
        'status'=>30,
        'num_pages'=>$num_pages,
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
