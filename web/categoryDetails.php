<?php
//
// Description
// -----------
// This function will return the details for a category.
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure.
// business_id:		The ID of the business to get events for.
// type:			The list to return, either by category or year.
//
//					- category
//					- year
//
// type_name:		The name of the category or year to list.
//
// Returns
// -------
//
function ciniki_products_web_categoryDetails($ciniki, $settings, $business_id, $args) {

	$rsp = array('stat'=>'ok', 'details'=>array());

	//
	// FIXME: Check for the category intro/picture/etc
	//
	$strsql = "SELECT tag_name "
		. "FROM ciniki_product_tags "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['category_permalink']) . "' "
		. "AND tag_type = 10 "
		. "LIMIT 1 "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'tag');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['tag']) ) {
		$rsp['details']['category_title'] = $rc['tag']['tag_name'];
	}

	//
	// Check for subcategories
	//
	$strsql = "SELECT t2.tag_name AS name, "
		. "t2.permalink, "
		. "COUNT(ciniki_products.id) AS num_products "
		. "FROM ciniki_product_tags AS t1, ciniki_product_tags AS t2, ciniki_products "
		. "WHERE t1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category_permalink']) . "' "
		. "AND t1.tag_type = 10 "
		. "AND t1.product_id = t2.product_id "
		. "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND t2.product_id = ciniki_products.id "
		. "AND t2.tag_type = 11 "
		. "AND (ciniki_products.webflags&0x01) > 0 "
		. "AND t2.tag_name <> '' "
		. "GROUP BY t2.tag_name "
		. "ORDER BY t2.tag_name "
		. "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'categories', 'fname'=>'name', 
			'fields'=>array('name', 'permalink', 'num_products')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['categories']) ) {
		$rsp['subcategories'] = $rc['categories'];
		//
		// Load highlight images
		//
		foreach($rsp['subcategories'] as $cnum => $cat) {
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
				. "ORDER BY (ciniki_products.webflags&0x20) DESC, (ciniki_products.webflags&0x10) DESC, "
				. "ciniki_products.date_added DESC "
				. "LIMIT 1";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'image');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['image']) ) {
				$rsp['subcategories'][$cnum]['image_id'] = $rc['image']['primary_image_id'];
			} else {
				$rsp['subcategories'][$cnum]['image_id'] = 0;
			}
		}
	} else {
		$rsp['subcategories'] = array();
	}

	
	//
	// Check for any products that are not in a sub category
	//
	$strsql = "SELECT ciniki_products.id, "
		. "ciniki_products.name AS title, "
		. "ciniki_products.permalink, "
		. "ciniki_products.primary_image_id AS image_id, "
		. "ciniki_products.price, "
		. "ciniki_products.short_description AS description, "
		. "'yes' AS is_details, "
		. "IF(ciniki_images.last_updated > ciniki_products.last_updated, "
			. "UNIX_TIMESTAMP(ciniki_images.last_updated), "
			. "UNIX_TIMESTAMP(ciniki_products.last_updated)) AS last_updated "
		. "FROM ciniki_product_tags AS t1 "
		. "LEFT JOIN ciniki_products ON ("
			. "t1.product_id = ciniki_products.id "
			. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND (ciniki_products.webflags&0x01) > 0 "
			. ") "
		. "LEFT JOIN ciniki_product_tags AS t2 ON ("
			. "ciniki_products.id = t2.product_id "
			. "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND t2.tag_type = 11 "
			. ") "
		. "LEFT JOIN ciniki_images ON ("
			. "ciniki_products.primary_image_id = ciniki_images.id "
			. "AND ciniki_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE t1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND t1.tag_type = 10 "
		. "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category_permalink']) . "' "
		. "AND ISNULL(t2.tag_name) "
		. "ORDER BY ciniki_products.name ASC "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'products', 'fname'=>'title', 
			'fields'=>array('title', 'permalink', 'image_id', 'description', 
				'is_details', 'last_updated')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['products']) ) {
		$rsp['products'] = $rc['products'];
	} else {
		$rsp['products'] = array();
	}

	return $rsp;
}
?>
