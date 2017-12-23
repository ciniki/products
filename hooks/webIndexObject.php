<?php
//
// Description
// -----------
// This function returns the index details for an object
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_products_hooks_webIndexObject($ciniki, $tnid, $args) {

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
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
            'description'=>$rc['item']['long_description'],
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
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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

        //
        // Get the audio for the product
        //
        $strsql = "SELECT a.id, a.uuid, a.type "
            . "FROM ciniki_product_audio AS pa "
            . "LEFT JOIN ciniki_audio AS a ON ("
                . "(pa.mp3_audio_id = a.id OR pa.wav_audio_id = a.id OR pa.ogg_audio_id = a.id ) "
                . "AND a.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE pa.product_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND pa.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'audio');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) ) {
            foreach($rc['rows'] as $row) {
                if( $row['type'] == 20 ) {
                    $object['ogg_audio_uuid'] = $row['uuid'];
                } elseif( $row['type'] == 30 ) {
                    $object['wav_audio_uuid'] = $row['uuid'];
                } elseif( $row['type'] == 40 ) {
                    $object['mp3_audio_uuid'] = $row['uuid'];
                }
            }
        }

        return array('stat'=>'ok', 'object'=>$object);
    }

    return array('stat'=>'ok');
}
?>
