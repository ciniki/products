<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the product to.
// product_audio_id:	The ID of the product audio to get.
//
// Returns
// -------
//
function ciniki_products_audioGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'product_audio_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product Audio'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.audioGet', 0); 
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
		. "WHERE ciniki_product_audio.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_product_audio.id = '" . ciniki_core_dbQuote($ciniki, $args['product_audio_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'audio', 'fname'=>'id', 'name'=>'audio',
			'fields'=>array('id', 'name', 'permalink', 'sequence', 'webflags', 
				'mp3_audio_id', 'wav_audio_id', 'ogg_audio_id', 'description')),
		array('container'=>'formats', 'fname'=>'audio_id', 'name'=>'format',
			'fields'=>array('id'=>'audio_id', 'original_filename')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['audio']) ) {
		return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'1851', 'msg'=>'Unable to find audio'));
	}
	$audio = $rc['audio'][0]['audio'];
	if( isset($audio['formats']) ) {
		foreach($audio['formats'] as $fid => $format) {
			if( $format['format']['id'] == $audio['mp3_audio_id'] ) {
				$audio['mp3_audio_id_filename'] = $format['format']['original_filename'];
			}
			if( $format['format']['id'] == $audio['wav_audio_id'] ) {
				$audio['wav_audio_id_filename'] = $format['format']['original_filename'];
			}
			if( $format['format']['id'] == $audio['ogg_audio_id'] ) {
				$audio['ogg_audio_id_filename'] = $format['format']['original_filename'];
			}
		}
	}
	
	return array('stat'=>'ok', 'audio'=>$audio);
}
?>
