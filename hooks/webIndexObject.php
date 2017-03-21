<?php
//
// Description
// -----------
// This function returns the index details for an object
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_products_hooks_webIndexObject($ciniki, $business_id, $args) {

    if( !isset($args['object']) || $args['object'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.12', 'msg'=>'No object specified'));
    }

    if( !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.13', 'msg'=>'No object ID specified'));
    }

    //
    // Setup the base_url for use in index
    //
    if( isset($args['base_url']) ) {
        $base_url = $args['base_url'];
    } else {
        $base_url = '/products';
    }

    if( $args['object'] == 'ciniki.products.product' ) {
        $strsql = "SELECT id, code, name, permalink, price, webflags, status, "
            . "primary_image_id, short_description, long_description "
            . "FROM ciniki_products "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.14', 'msg'=>'Object not found'));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.products.15', 'msg'=>'Object not found'));
        }

        //
        // Check if item is visible on website
        //
        if( ($rc['item']['webflags']&0x01) == 0 ) {
            return array('stat'=>'ok');
        }
        if( $rc['item']['status'] != '10' ) {
            return array('stat'=>'ok');
        }
        $object = array(
            'label'=>'Products',
            'title'=>$rc['item']['name'],
            'subtitle'=>'',
            'meta'=>'',
            'primary_image_id'=>$rc['item']['primary_image_id'],
            'synopsis'=>$rc['item']['short_description'],
            'object'=>'ciniki.products.product',
            'object_id'=>$rc['item']['id'],
            'primary_words'=>$rc['item']['code'] . ' ' . $rc['item']['name'],
            'secondary_words'=>$rc['item']['short_description'],
            'tertiary_words'=>$rc['item']['long_description'],
            'weight'=>10000,
            'url'=>$base_url . '/' . $rc['item']['permalink']
            );
        
        //
        // Get the categories for the product
        //
        $strsql = "SELECT DISTINCT tag_type, tag_name "
            . "FROM ciniki_product_tags "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'tag');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) ) {
            foreach($rc['rows'] as $row) {
                if( $row['tag_type'] == 10 ) {
                    $object['label'] = $row['tag_name'];
                }
                $object['primary_words'] .= ' ' . $row['tag_name'];
            }
        }

        return array('stat'=>'ok', 'object'=>$object);
    }

    return array('stat'=>'ok');
}
?>
