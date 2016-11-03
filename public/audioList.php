<?php
//
// Description
// -----------
// This method will return the list of all audio files for products in the system.
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
//
function ciniki_products_audioList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.audioList', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT ciniki_product_audio.id, "
        . "ciniki_products.id AS product_id, "
        . "ciniki_products.code AS product_code, "
        . "ciniki_products.name AS product_name, "
        . "ciniki_product_audio.name, "
        . "ciniki_product_audio.permalink, "
        . "ciniki_product_audio.sequence, "
        . "ciniki_product_audio.webflags, "
        . "ciniki_product_audio.mp3_audio_id, "
        . "ciniki_product_audio.wav_audio_id, "
        . "ciniki_product_audio.ogg_audio_id, "
        . "ciniki_audio.id AS audio_id, "
        . "ciniki_audio.original_filename, "
        . "ciniki_product_audio.description "
        . "FROM ciniki_product_audio "
        . "LEFT JOIN ciniki_audio ON ("
            . "(ciniki_product_audio.mp3_audio_id = ciniki_audio.id "
                . "OR ciniki_product_audio.wav_audio_id = ciniki_audio.id "
                . "OR ciniki_product_audio.ogg_audio_id = ciniki_audio.id "
                . ") "
            . "AND ciniki_audio.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_products ON ("
            . "ciniki_product_audio.product_id = ciniki_products.id "
            . "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_product_audio.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY ciniki_products.code, ciniki_products.name, ciniki_product_audio.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'audio', 'fname'=>'id', 
            'fields'=>array('id', 'product_id', 'product_code', 'product_name', 'name', 'permalink', 'sequence', 'webflags', 
                'mp3_audio_id', 'wav_audio_id', 'ogg_audio_id', 'description')),
        array('container'=>'formats', 'fname'=>'audio_id', 
            'fields'=>array('id'=>'audio_id', 'original_filename')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['audio']) ) {
        return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.products.46', 'msg'=>'Unable to find audio'));
    }
    $audio = $rc['audio'];
    foreach($audio as $aid => $a) {
        if( isset($a['formats']) ) {
            foreach($a['formats'] as $fid => $format) {
                if( $format['id'] == $a['wav_audio_id'] ) { 
                    $audio[$aid]['wav_audio_filename'] = $format['original_filename'];
                } elseif( $format['id'] == $a['mp3_audio_id'] ) { 
                    $audio[$aid]['mp3_audio_filename'] = $format['original_filename'];
                } elseif( $format['id'] == $a['ogg_audio_id'] ) { 
                    $audio[$aid]['ogg_audio_filename'] = $format['original_filename'];
                }
            }
            unset($audio[$aid]['formats']);
        }
    }
    
    return array('stat'=>'ok', 'audio'=>$audio);
}
?>
