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
function ciniki_products_web_subcategoryDetails($ciniki, $settings, $business_id, $args) {

	$rsp = array('stat'=>'ok', 'details'=>array());

	//
	// Get the category name
	//
	$strsql = "SELECT ciniki_product_tags.tag_name, "
		. "IFNULL(ciniki_product_categories.name, '') AS name, "
		. "IFNULL(ciniki_product_categories.sequence, 0) AS sequence, "
		. "IFNULL(ciniki_product_categories.primary_image_id, 0) AS image_id, "
		. "IFNULL(ciniki_product_categories.synopsis, '') AS synopsis, "
		. "IFNULL(ciniki_product_categories.description, '') AS description "
		. "FROM ciniki_product_tags "
		. "LEFT JOIN ciniki_product_categories ON ("
			. "ciniki_product_tags.permalink = ciniki_product_categories.category "
			. "AND ciniki_product_categories.subcategory = '' "
			. "AND ciniki_product_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_product_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_product_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category_permalink']) . "' "
		. "AND ciniki_product_tags.tag_type = 10 "
		. "LIMIT 1 "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'tag');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['tag']) ) {
		if( $rc['tag']['name'] != '' ) {
			$rsp['details']['category_title'] = $rc['tag']['name'];
		} else {
			$rsp['details']['category_title'] = $rc['tag']['tag_name'];
		}
//		$rsp['details']['image_id'] = $rc['tag']['image_id'];
//		if( $rc['tag']['description'] != '' ) {
//			$rsp['details']['description'] = $rc['tag']['description'];
//		} else {
//			$rsp['details']['description'] = $rc['tag']['synopsis'];
//		}
	}

	//
	// Get the sub-category name and details
	//
	$strsql = "SELECT t2.tag_name, "
		. "IFNULL(ciniki_product_categories.name, '') AS name, "
		. "IFNULL(ciniki_product_categories.sequence, 0) AS sequence, "
		. "IFNULL(ciniki_product_categories.primary_image_id, 0) AS image_id, "
		. "IFNULL(ciniki_product_categories.synopsis, '') AS synopsis, "
		. "IFNULL(ciniki_product_categories.description, '') AS description "
		. "FROM ciniki_product_tags AS t1 "
		. "LEFT JOIN ciniki_product_tags AS t2 ON ("
			. "t1.product_id = t2.product_id "
			. "AND t2.permalink = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_permalink']) . "' "
			. "AND t2.tag_type > 10 "
			. "AND t2.tag_type < 30 "
			. "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "LEFT JOIN ciniki_product_categories ON ("
			. "t1.permalink = ciniki_product_categories.category "
			. "AND t2.permalink = ciniki_product_categories.subcategory "
			. "AND ciniki_product_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE t1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category_permalink']) . "' "
		. "AND t1.tag_type = 10 "
		. "AND NOT ISNULL(t2.tag_name) "
		. "LIMIT 1 "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'tag');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['tag']) ) {
		if( $rc['tag']['name'] != '' ) {
			$rsp['details']['subcategory_title'] = $rc['tag']['name'];
		} else {
			$rsp['details']['subcategory_title'] = $rc['tag']['tag_name'];
		}
		$rsp['details']['image_id'] = $rc['tag']['image_id'];
		if( $rc['tag']['description'] != '' ) {
			$rsp['details']['content'] = $rc['tag']['description'];
		} else {
			$rsp['details']['content'] = $rc['tag']['synopsis'];
		}
	}

	//
	// Get the subcategory name
	//
/*	$strsql = "SELECT tag_type, tag_name "
		. "FROM ciniki_product_tags "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_permalink']) . "' "
		. "AND tag_type > 10 "
		. "AND tag_type < 30 "
		. "LIMIT 1 "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'tag');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$tag_type = '';
	if( isset($rc['tag']) ) {
		$rsp['details']['subcategory_title'] = $rc['tag']['tag_name'];
		$tag_type = $rc['tag']['tag_type'];
		if( $rc['tag']['name'] != '' ) {
			$rsp['details']['subcategory_title'] = $rc['tag']['name'];
		} else {
			$rsp['details']['category_title'] = $rc['tag']['tag_name'];
		}
		$rsp['details']['image_id'] = $rc['tag']['image_id'];
		if( $rc['tag']['description'] != '' ) {
			$rsp['details']['description'] = $rc['tag']['description'];
		} else {
			$rsp['details']['description'] = $rc['tag']['synopsis'];
		}
	}
*/
	//
	// Get the list of products for this sub-category
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
		. "LEFT JOIN ciniki_product_tags AS t2 ON ("
			. "t1.product_id = t2.product_id "
			. "AND t2.permalink = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_permalink']) . "' "
			. "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND t2.tag_type > 10 "
			. "AND t2.tag_type < 30 "
			. ") "
		. "LEFT JOIN ciniki_products ON ("
			. "t2.product_id = ciniki_products.id "
			. "AND ciniki_products.parent_id = 0 "
			. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_products.start_date < UTC_TIMESTAMP() "
			. "AND (ciniki_products.end_date = '0000-00-00 00:00:00' "
				. "OR ciniki_products.end_date > UTC_TIMESTAMP()"
				. ") "
			. "AND (ciniki_products.webflags&0x01) > 0 "
			. ") "
		. "LEFT JOIN ciniki_images ON ("
			. "ciniki_products.primary_image_id = ciniki_images.id "
			. "AND ciniki_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE t1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND t1.tag_type = 10 "
		. "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category_permalink']) . "' "
		. "ORDER BY ciniki_products.name ASC "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
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
