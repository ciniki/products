<?php
//
// Description
// -----------
// This method will merge two products into one.
//
// Returns
// -------
//
function ciniki_products_productMerge($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'primary_product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Primary Product'),
        'secondary_product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Secondary Product'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productMerge', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // get the active modules for the business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'getActiveModules');
    $rc = ciniki_businesses_getActiveModules($ciniki, $args['business_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Merge wine orders
    //
    if( isset($modules['ciniki.wineproduction']) ) {
        $updated = 0;
        //
        // Get the list of orders attached to the secondary product
        //
        $strsql = "SELECT id "
            . "FROM ciniki_wineproductions "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_product_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.wineproductions', 'wineproduction');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'899', 'msg'=>'Unable to find wine production orders', 'err'=>$rc['err']));
        }
        $wineproductions = $rc['rows'];
        foreach($wineproductions as $i => $row) {
            $strsql = "UPDATE ciniki_wineproductions "
                . "SET product_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_product_id']) . "' "
                . ", last_updated = UTC_TIMESTAMP() "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND id = '" . $row['id'] . "' "
                . "";
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.wineproductions');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'900', 'msg'=>'Unable to update wine production orders', 'err'=>$rc['err']));
            }
            if( $rc['num_affected_rows'] == 1 ) {
                // Record update as merge action
                $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.wineproductions', 
                    'ciniki_wineproduction_history', $args['business_id'],
                    4, 'ciniki_wineproductions', $row['id'], 'product_id', $args['primary_product_id']);
            }
            $updated = 1;
        }

        if( $updated == 1 ) {
            //
            // Update the last_change date in the business modules
            // Ignore the result, as we don't want to stop user updates if this fails.
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
            ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'wineproduction');
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

    return array('stat'=>'ok');
}
?>
