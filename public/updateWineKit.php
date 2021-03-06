<?php
//
// Description
// -----------
// Update the product information for a wine kit.
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
function ciniki_products_updateWineKit(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'source'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Source'),
        'barcode'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Barcode'), 
        'supplier_tnid'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Supplier'), 
        'supplier_product_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Supplier Product'), 
        'price'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Price'), 
        'cost'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Cost'), 
        'msrp'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'MSRP'), 
        'wine_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Wine Type'), 
        'kit_length'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Rack Length'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['tnid'], 'ciniki.products.updateWineKit', $args['product_id']); 
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Add the product to the database
    //
    $strsql = "UPDATE ciniki_products SET last_updated = UTC_TIMESTAMP()";

    //
    // Add all the fields to the change log
    //

    $changelog_fields = array(
        'name',
        'category_id',
        'sales_category_id',
        'source',
        'barcode',
        'supplier_tnid',
        'supplier_product_id',
        'price',
        'cost',
        'msrp',
        );
    foreach($changelog_fields as $field) {
        if( isset($args[$field]) ) {
            $strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.products', 
                'ciniki_product_history', $args['tnid'], 
                2, 'ciniki_products', $args['product_id'], $field, $args[$field]);
        }
    }
    $strsql .= "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' ";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) { 
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
        return $rc;
    }
    if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.132', 'msg'=>'Unable to add product'));
    }

    //
    // Update the details
    //
    $detail_fields = array(
        'wine_type'=>'wine_type',
        'kit_length'=>'kit_length',
        );
    foreach($detail_fields as $field => $detail_field) {
        if( isset($args[$field]) ) {
            $strsql = "INSERT INTO ciniki_product_details (tnid, product_id, "
                . "detail_key, detail_value, date_added, last_updated) VALUES ("
                . "'" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $detail_field) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $args[$field]) . "', "
                . "UTC_TIMESTAMP(), UTC_TIMESTAMP() "
                . ") "
                . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' "
                . ", last_updated = UTC_TIMESTAMP() "
                . "";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.products');
            if( $rc['stat'] != 'ok' ) { 
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
                return $rc;
            }
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.products', 
                'ciniki_product_history', $args['tnid'], 
                2, 'ciniki_product_details', $args['product_id'], $detail_field, $args[$field]);
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
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'products');

    $ciniki['syncqueue'][] = array('push'=>'ciniki.products.product',
        'args'=>array('id'=>$args['product_id']));

    return array('stat'=>'ok');
}
?>
