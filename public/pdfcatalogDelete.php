<?php
//
// Description
// -----------
// This method will delete an pdf catalog.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:            The ID of the business the pdf catalog is attached to.
// catalog_id:            The ID of the pdf catalog to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_products_pdfcatalogDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'catalog_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'PDF Catalog'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.pdfcatalogDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the pdf catalog
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_product_pdfcatalogs "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['catalog_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'catalog');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['catalog']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3403', 'msg'=>'PDF Catalog does not exist.'));
    }
    $catalog = $rc['catalog'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the images
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_product_pdfcatalog_images "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND catalog_id = '" . ciniki_core_dbQuote($ciniki, $args['catalog_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'image');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        foreach($rc['rows'] as $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.products.pdfcatalogimage', $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Remove the catalog
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.products.pdfcatalog', $args['catalog_id'], $catalog['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'products');

    return array('stat'=>'ok');
}
?>
