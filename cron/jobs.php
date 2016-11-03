<?php
//
// Description
// ===========
//
// Arguments
// =========
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_products_cron_jobs(&$ciniki) {
    ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for products jobs', 'severity'=>'5'));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'dropboxDownload');

    //
    // Get the list of businesses that have products enables and dropbox flag 
    //
    $strsql = "SELECT business_id "
        . "FROM ciniki_business_modules "
        . "WHERE package = 'ciniki' "
        . "AND module = 'products' "
        . "AND (flags&0x0100) = 0x0100 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.1', 'msg'=>'Unable to get list of businesses with products', 'err'=>$rc['err']));
    }
    if( isset($rc['rows']) ) {
        $businesses = $rc['rows'];
        
        foreach($businesses as $business) {
            //
            // Load business modules
            //
            $rc = ciniki_businesses_checkModuleAccess($ciniki, $business['business_id'], 'ciniki', 'products');
            if( $rc['stat'] != 'ok' ) { 
                ciniki_cron_logMsg($ciniki, $business['business_id'], array('code'=>'ciniki.products.166', 'msg'=>'ciniki.products not configured', 
                    'severity'=>30, 'err'=>$rc['err']));
                continue;
            }

            ciniki_cron_logMsg($ciniki, $business['business_id'], array('code'=>'0', 'msg'=>'Updating products from dropbox', 'severity'=>'10'));

            //
            // Update the business products from dropbox
            //
            $rc = ciniki_products_dropboxDownload($ciniki, $business['business_id']);
            if( $rc['stat'] != 'ok' ) {
                ciniki_cron_logMsg($ciniki, $business['business_id'], array('code'=>'ciniki.products.167', 'msg'=>'Unable to update products', 
                    'severity'=>50, 'err'=>$rc['err']));
                continue;
            }
        }
    }

    //
    // Check for pdfcatalogs that need processing
    //
    $strsql = "SELECT id, business_id "
        . "FROM ciniki_product_pdfcatalogs "
        . "WHERE status = 10 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'catalog');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.2', 'msg'=>'Unable to get list of businesses with pdfcatalogs', 'err'=>$rc['err']));
    }
    if( isset($rc['rows']) ) {
        $catalogs = $rc['rows'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'processPDFCatalog');
        foreach($catalogs as $catalog) {
            $rc = ciniki_products_processPDFCatalog($ciniki, $catalog['business_id'], $catalog['id']);
            if( $rc['stat'] != 'ok' ) {
                ciniki_cron_logMsg($ciniki, $catalog['business_id'], array('code'=>'ciniki.products.168', 'msg'=>'Unable to update PDF Catalog: ' . $catalog['id'], 
                    'severity'=>50, 'err'=>$rc['err']));
                continue;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
