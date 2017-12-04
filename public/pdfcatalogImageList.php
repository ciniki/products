<?php
//
// Description
// -----------
// This method will return the list of PDF Catalog Imagess for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get PDF Catalog Images for.
//
// Returns
// -------
//
function ciniki_products_pdfcatalogImageList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['tnid'], 'ciniki.products.pdfcatalogImageList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of images
    //
    $strsql = "SELECT ciniki_product_pdfcatalog_images.id, "
        . "ciniki_product_pdfcatalog_images.catalog_id, "
        . "ciniki_product_pdfcatalog_images.page_number, "
        . "ciniki_product_pdfcatalog_images.image_id "
        . "FROM ciniki_product_pdfcatalog_images "
        . "WHERE ciniki_product_pdfcatalog_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'images', 'fname'=>'id', 
            'fields'=>array('id', 'catalog_id', 'page_number', 'image_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['images']) ) {
        $images = $rc['images'];
    } else {
        $images = array();
    }

    return array('stat'=>'ok', 'images'=>$images);
}
?>
