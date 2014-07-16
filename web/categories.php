<?php
//
// Description
// -----------
// This function will return a list of categories for the web product page.
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure.
// business_id:		The ID of the business to get events for.
//
// Returns
// -------
// <categories>
// 		<category name="Portraits" image_id="349" />
// 		<category name="Landscape" image_id="418" />
//		...
// </categories>
//
function ciniki_products_web_categories($ciniki, $settings, $business_id) {

	$strsql = "SELECT ciniki_product_tags.tag_name AS name, "
		. "ciniki_product_tags.permalink, "
		. "COUNT(ciniki_products.id) AS num_products "
		. "FROM ciniki_product_tags, ciniki_products "
		. "WHERE ciniki_product_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_product_tags.product_id = ciniki_products.id "
		. "AND ciniki_product_tags.tag_type = 10 "
		. "AND (ciniki_products.webflags&0x01) > 0 "
		. "AND ciniki_product_tags.tag_name <> '' "
		. "GROUP BY ciniki_product_tags.tag_name "
		. "ORDER BY ciniki_product_tags.tag_name "
		. "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'categories', 'fname'=>'name', 
			'fields'=>array('name', 'permalink', 'num_products')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['categories']) ) {
		return array('stat'=>'ok');
	}
	$categories = $rc['categories'];

	//
	// Load highlight images
	//
	foreach($categories as $cnum => $cat) {
		//
		// Look for the highlight image, or the most recently added image
		//
		$strsql = "SELECT ciniki_products.primary_image_id, ciniki_images.image "
			. "FROM ciniki_product_tags, ciniki_products, ciniki_images "
			. "WHERE ciniki_product_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_product_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $cat['permalink']) . "' "
			. "AND ciniki_product_tags.product_id = ciniki_products.id "
			. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_products.primary_image_id = ciniki_images.id "
			. "AND (ciniki_products.webflags&0x01) > 0 "
			. "ORDER BY (ciniki_products.webflags&0x10) DESC, "
			. "ciniki_products.date_added DESC "
			. "LIMIT 1";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'image');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['image']) ) {
			$categories[$cnum]['image_id'] = $rc['image']['primary_image_id'];
		} else {
			$categories[$cnum]['image_id'] = 0;
		}
	}

	return array('stat'=>'ok', 'categories'=>$categories);	
}
?>
