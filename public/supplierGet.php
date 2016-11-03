<?php
//
// Description
// -----------
// This method returns the details for a supplier.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
function ciniki_products_supplierGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'supplier_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Supplier'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.supplierGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load currency and timezone settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Get the basic supplier information
    //
    $strsql = "SELECT ciniki_product_suppliers.id, "
        . "ciniki_product_suppliers.name, "
        . "FROM ciniki_product_suppliers "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_product_suppliers.id = '" . ciniki_core_dbQuote($ciniki, $args['supplier_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'suppliers', 'fname'=>'id', 'name'=>'supplier',
            'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['suppliers'][0]['supplier']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.127', 'msg'=>'Unable to find the specified supplier'));
    }
    $supplier = $rc['suppliers'][0]['supplier'];

    return array('stat'=>'ok', 'supplier'=>$supplier);
}
?>
