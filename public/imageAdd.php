<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_products_imageAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'), 
        'name'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Title'), 
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Sequence'), 
        'permalink'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Permalink'), 
        'webflags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Website Flags'), 
        'image_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Image'),
        'description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.imageAdd', $args['product_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    } 

    //
    // Get a UUID for use in permalink
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1472', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
    }
    $args['uuid'] = $rc['uuid'];

    //
    // Determine the permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        if( isset($args['name']) && $args['name'] != '' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        } else {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['uuid']);
        }
    }

    //
    // Check the permalink doesn't already exist
    //
    $strsql = "SELECT id, name, permalink FROM ciniki_product_images "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'image');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1473', 'msg'=>'You already have an image with this name, please choose another name'));
    }


    if( $args['product_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1474', 'msg'=>'No product specified'));
    }

    //
    // Get the next sequence
    //
    $adjust_sequence = 'yes';
    if( $args['sequence'] == 0 ) {
        $strsql = "SELECT MAX(sequence) AS sequence "
            . "FROM ciniki_product_images "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'max');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['max']['sequence']) && $rc['max']['sequence'] > 0 ) {
            $args['sequence'] = $rc['max']['sequence'] + 1;
            $adjust_sequence = 'no';
        }
    }

    //
    // Start a transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the product image to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.products.image', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
        return $rc;
    }
    $image_id = $rc['id'];

    //
    // Update the sequence
    //
    if( isset($args['sequence']) && $adjust_sequence == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'imageUpdateSequences');
        $rc = ciniki_products_imageUpdateSequences($ciniki, $args['business_id'], $image_id, 
            $args['sequence'], -1);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
            return $rc;
        }
    }

    //
    // Commit the changes to the database
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

    return array('stat'=>'ok', 'id'=>$image_id);
}
?>
