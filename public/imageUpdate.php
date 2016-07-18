<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to add the product to.
// name:                The name of the product.  
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_products_imageUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'product_image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product Image'), 
        'image_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Image'),
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Title'), 
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'), 
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Website Flags'), 
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.imageUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Get the existing image details
    //
    $strsql = "SELECT product_id, sequence, uuid, image_id FROM ciniki_product_images "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['product_image_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1477', 'msg'=>'Product image not found'));
    }
    $item = $rc['item'];

    if( isset($args['name']) ) {
        if( $args['name'] != '' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        } else {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $item['uuid']);
        }
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink FROM ciniki_product_images "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $item['product_id']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['product_image_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'image');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1478', 'msg'=>'You already have an image with this name, please choose another name'));
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
    // Update the product in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.products.image', $args['product_image_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
        return $rc;
    }

    //
    // Update the sequence
    //
    if( isset($args['sequence']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'imageUpdateSequences');
        $rc = ciniki_products_imageUpdateSequences($ciniki, $args['business_id'], $item['product_id'],
            $args['sequence'], $item['sequence']);
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

    return array('stat'=>'ok');
}
?>
