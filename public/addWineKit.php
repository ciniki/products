<?php
//
// Description
// -----------
// This function will add a new product to the products production module.
//
// Info
// ----
// Status:          defined
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_products_addWineKit(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'name'=>array('required'=>'yes', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'type'=>array('required'=>'no', 'default'=>'0', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Type'),
        'barcode'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Barcode'), 
        'supplier_business_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Supplier'), 
        'supplier_product_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Supplier Product'), 
        'price'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Price'), 
        'cost'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Cost'), 
        'msrp'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'MSRP'), 
        'wine_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Wine Type'), 
        'kit_length'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Rack Length'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.addWineKit', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get a new UUID
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.services');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args['uuid'] = $rc['uuid'];

    //
    // Add the product to the database
    //
    $strsql = "INSERT INTO ciniki_products (uuid, business_id, name, type, "
        . "source, flags, status, "
        . "barcode, supplier_business_id, supplier_product_id, "
        . "price, cost, msrp, "
        . "date_added, last_updated) VALUES ("
        . "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['name']) . "', "
        . "64, 0, 0, 1, "
        . "'" . ciniki_core_dbQuote($ciniki, $args['barcode']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['supplier_business_id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['supplier_product_id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['price']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['cost']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['msrp']) . "', "
        . "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) { 
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
        return $rc;
    }
    if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.40', 'msg'=>'Unable to add product'));
    }
    $product_id = $rc['insert_id'];

    //
    // Add all the fields to the change log
    //

    $changelog_fields = array(
        'uuid',
        'name',
        'type',
        'source',
        'type',
        'barcode',
        'supplier_business_id',
        'supplier_product_id',
        'price',
        'cost',
        'msrp',
        );
    foreach($changelog_fields as $field) {
        if( isset($args[$field]) && $args[$field] != '' ) {
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.products', 'ciniki_product_history', $args['business_id'], 
                1, 'ciniki_products', $product_id, $field, $args[$field]);
        }
    }

    //
    // Add the wine type and kit_length to the product details
    //
    $detail_fields = array(
        'wine_type'=>'wine_type',
        'kit_length'=>'kit_length',
        );
    foreach($detail_fields as $field => $detail_field) {
        if( isset($args[$field]) && $args[$field] != '' ) {
            $strsql = "INSERT INTO ciniki_product_details (product_id, detail_key, detail_value, date_added, last_updated) VALUES ("
                . "'" . ciniki_core_dbQuote($ciniki, $product_id) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $detail_field) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $args[$field]) . "', "
                . "UTC_TIMESTAMP(), UTC_TIMESTAMP() "
                . ")";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.products');
            if( $rc['stat'] != 'ok' ) { 
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
                return $rc;
            }
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.products', 
                'ciniki_product_history', $args['business_id'], 
                1, 'ciniki_product_details', $product_id, $detail_field, $args[$field]);
        }
    }

    //
    // Commit the database changes
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

    $ciniki['syncqueue'][] = array('push'=>'ciniki.products.product',
        'args'=>array('id'=>$product_id));

    return array('stat'=>'ok', 'id'=>$product_id);
}
?>
