<?php
//
// Description
// ===========
// This method will return all the information about an pdf catalog images.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the pdf catalog images is attached to.
// catalog_image_id:          The ID of the pdf catalog images to get the details for.
//
// Returns
// -------
//
function ciniki_products_pdfcatalogImageGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'catalog_image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'PDF Catalog Images'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.pdfcatalogImageGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new PDF Catalog Images
    //
    if( $args['catalog_image_id'] == 0 ) {
        $image = array('id'=>0,
            'catalog_id'=>'',
            'page_number'=>'',
            'image_id'=>'',
        );
    }

    //
    // Get the details for an existing PDF Catalog Images
    //
    else {
        $strsql = "SELECT ciniki_product_pdfcatalog_images.id, "
            . "ciniki_product_pdfcatalog_images.catalog_id, "
            . "ciniki_product_pdfcatalog_images.page_number, "
            . "ciniki_product_pdfcatalog_images.image_id "
            . "FROM ciniki_product_pdfcatalog_images "
            . "WHERE ciniki_product_pdfcatalog_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_product_pdfcatalog_images.id = '" . ciniki_core_dbQuote($ciniki, $args['catalog_image_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'image');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3426', 'msg'=>'PDF Catalog Images not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['image']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3427', 'msg'=>'Unable to find PDF Catalog Images'));
        }
        $image = $rc['image'];
    }

    return array('stat'=>'ok', 'image'=>$image);
}
?>
