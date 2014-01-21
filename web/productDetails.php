<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_products_web_productDetails($ciniki, $settings, $business_id, $permalink) {

	$strsql = "SELECT ciniki_products.id, "
		. "ciniki_products.name, "
		. "ciniki_products.permalink, "
		. "ciniki_products.short_description, "
		. "ciniki_products.long_description, "
		. "ciniki_products.primary_image_id, "
		. "ciniki_product_images.image_id, "
		. "ciniki_product_images.name AS image_name, "
		. "ciniki_product_images.permalink AS image_permalink, "
		. "ciniki_product_images.description AS image_description, "
		. "UNIX_TIMESTAMP(ciniki_product_images.last_updated) AS image_last_updated "
		. "FROM ciniki_products "
		. "LEFT JOIN ciniki_product_images ON ("
			. "ciniki_products.id = ciniki_product_images.product_id "
			. "AND (ciniki_product_images.webflags&0x01) = 0 "
			. ") "
		. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_products.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artclub', array(
		array('container'=>'products', 'fname'=>'id', 
			'fields'=>array('id', 'name', 'permalink', 'image_id'=>'primary_image_id', 
			'short_description', 'long_description')),
		array('container'=>'images', 'fname'=>'image_id', 
			'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
				'description'=>'image_description', 
				'last_updated'=>'image_last_updated')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['products']) || count($rc['products']) < 1 ) {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'1506', 'msg'=>"I'm sorry, but we can't find the product you requested."));
	}
	$product = array_pop($rc['products']);

	//
	// Check if any files are attached to the product
	//
	$strsql = "SELECT id, name, extension, permalink, description "
		. "FROM ciniki_product_files "
		. "WHERE ciniki_product_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_product_files.product_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'files', 'fname'=>'id', 
			'fields'=>array('id', 'name', 'extension', 'permalink', 'description')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['files']) ) {
		$product['files'] = $rc['files'];
	}

	return array('stat'=>'ok', 'product'=>$product);
}
?>
