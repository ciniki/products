<?php
//
// Description
// ===========
// This method will return all the information about an pdf catalog.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the pdf catalog is attached to.
// catalog_id:          The ID of the pdf catalog to get the details for.
//
// Returns
// -------
//
function ciniki_products_pdfcatalogGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'catalog_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'PDF Catalog'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.pdfcatalogGet');
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
    // Return default for new PDF Catalog
    //
    if( $args['catalog_id'] == 0 ) {
        $strsql = "SELECT MAX(sequence) AS sequence "
            . "FROM ciniki_product_pdfcatalogs "
            . "WHERE ciniki_product_pdfcatalogs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'max');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $catalog = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'sequence'=>(isset($rc['max']['sequence']) ? ($rc['max']['sequence']+1) : 1),
            'flags'=>'1',
            'num_pages'=>'0',
            'images'=>array(),
            'primary_image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing PDF Catalog
    //
    else {
        $strsql = "SELECT ciniki_product_pdfcatalogs.id, "
            . "ciniki_product_pdfcatalogs.name, "
            . "ciniki_product_pdfcatalogs.permalink, "
            . "ciniki_product_pdfcatalogs.sequence, "
            . "ciniki_product_pdfcatalogs.status, "
            . "ciniki_product_pdfcatalogs.flags, "
            . "ciniki_product_pdfcatalogs.num_pages, "
            . "ciniki_product_pdfcatalogs.primary_image_id, "
            . "ciniki_product_pdfcatalogs.synopsis, "
            . "ciniki_product_pdfcatalogs.description "
            . "FROM ciniki_product_pdfcatalogs "
            . "WHERE ciniki_product_pdfcatalogs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_product_pdfcatalogs.id = '" . ciniki_core_dbQuote($ciniki, $args['catalog_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'catalog');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.77', 'msg'=>'PDF Catalog not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['catalog']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.78', 'msg'=>'Unable to find PDF Catalog'));
        }
        $catalog = $rc['catalog'];

        //
        // Get the images for the catalog
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
        $strsql = "SELECT ciniki_product_pdfcatalog_images.id, "
            . "ciniki_product_pdfcatalog_images.image_id, "
            . "ciniki_product_pdfcatalog_images.page_number "
            . "FROM ciniki_product_pdfcatalog_images "
            . "WHERE ciniki_product_pdfcatalog_images.catalog_id = '" . ciniki_core_dbQuote($ciniki, $args['catalog_id']) . "' "
            . "AND ciniki_product_pdfcatalog_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY ciniki_product_pdfcatalog_images.page_number "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'images', 'fname'=>'id', 'name'=>'image',
                'fields'=>array('id', 'image_id', 'page_number')),
            ));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( isset($rc['images']) ) {
            $catalog['images'] = $rc['images'];
            foreach($catalog['images'] as $img_id => $img) {
                $catalog['images'][$img_id]['name'] = 'Page ' . $img['page_number'];
                if( isset($img['image_id']) && $img['image_id'] > 0 ) {
                    $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['business_id'], $img['image_id'], 75);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $catalog['images'][$img_id]['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                }
            }
        } else {
            $catalog['images'] = array();
        }
    }

    return array('stat'=>'ok', 'catalog'=>$catalog);
}
?>
