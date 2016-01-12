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
	// Get the details for the category
	//
    error_log('test-001');
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
    error_log('test-002');
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
		$rsp['details']['image_id'] = $rc['tag']['image_id'];
		if( $rc['tag']['description'] != '' ) {
			$rsp['details']['content'] = $rc['tag']['description'];
		} else {
			$rsp['details']['content'] = $rc['tag']['synopsis'];
		}
	}

	//
	// Check for subcategories
	//
    error_log('test-003');
	$strsql = "SELECT t2.tag_type, t2.tag_name AS name, "
		. "t2.permalink, "
		. "IFNULL(ciniki_product_categories.name, '') AS cat_name, "
		. "IFNULL(ciniki_product_categories.primary_image_id, 0) AS image_id, "
		. "IFNULL(ciniki_product_categories.synopsis, '') AS synopsis, "
		. "ciniki_products.type_id, "
		. "COUNT(ciniki_products.id) AS num_products "
		. "FROM ciniki_product_tags AS t1 "
		. "LEFT JOIN ciniki_product_tags AS t2 ON ("
			. "t1.product_id = t2.product_id "
			. "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND t2.tag_type > 10 "
			. "AND t2.tag_type < 30 "
			. ") "
		. "LEFT JOIN ciniki_product_categories ON ("
			. "t1.permalink = ciniki_product_categories.category "
			. "AND t2.permalink = ciniki_product_categories.subcategory "
			. "AND ciniki_product_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "LEFT JOIN ciniki_products ON ("
			. "t2.product_id = ciniki_products.id "
			. "AND ciniki_products.parent_id = 0 "
			. "AND (ciniki_products.webflags&0x01) > 0 "
			. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE t1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category_permalink']) . "' "
		. "AND t1.tag_type = 10 "
		. "GROUP BY type_id, t2.tag_type, t2.tag_name "
		. "ORDER BY type_id, t2.tag_type, IFNULL(ciniki_product_categories.sequence, 999), t2.tag_name "
		. "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'product_types', 'fname'=>'type_id', 'name'=>'product_type',
			'fields'=>array('id'=>'type_id')),
		array('container'=>'types', 'fname'=>'tag_type', 'name'=>'type',
			'fields'=>array('tag_type', 'name')),
		array('container'=>'categories', 'fname'=>'name', 'name'=>'category',
			'fields'=>array('name', 'cat_name', 'permalink', 'image_id', 'synopsis', 'num_products')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
    error_log('test-004');
	if( isset($rc['product_types']) && count($rc['product_types']) > 0 ) {
		$product_types = $rc['product_types'];
		//
		// Load the product_type_definitions
		//
		$strsql = "SELECT id, name_s, name_p, object_def "
			. "FROM ciniki_product_types "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY id "
			. "";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'types', 'fname'=>'id',
				'fields'=>array('id', 'name_s', 'name_p', 'object_def')),
			));
		$types = isset($rc['types'])?$rc['types']:array();
		$object_defs = array();
		// Prep the object defs
		foreach($types as $type_id => $type) {
			$object_defs[$type_id] = unserialize($type['object_def']);
		}
	
		//
		// Go through all the product types, and build a type array
		// based on the sub-category names
		//
		$types = array();
		foreach($product_types as $ptid => $ptype) {
			// Check of the product type exists
			if( isset($object_defs[$ptype['id']]) ) {
				$odef = $object_defs[$ptype['id']]['parent'];
				foreach($ptype['types'] as $tid => $type) {
					if( isset($odef['subcategories-' . $type['tag_type']]['pname']) ) {
						$sub_cat_name = $odef['subcategories-' . $type['tag_type']]['pname'];
					} else {
						$sub_cat_name = 'Sub-Categories';
					}
					if( !isset($types[$sub_cat_name]) ) {
						$types[$sub_cat_name] = array('name'=>$sub_cat_name, 'categories'=>$type['categories']);
					} else {
						foreach($type['categories'] as $new_id => $new_cat) {
							// Check for existing category name
							$found = 'no';
							foreach($types[$sub_cat_name]['categories'] as $old_id => $old_cat) {
								if( $old_cat['name'] == $new_cat['name'] ) {
									$types[$sub_cat_name]['categories'][$old_id]['num_products'] += $new_cat['num_products'];
									$found = 'yes';
									break;
								}
							}
							if( $found == 'no' ) {
								$types[$sub_cat_name]['categories'][] = $type['categories'][$new_id];
							}
						}
					}
				}
			}
		}

    error_log('test-005');
		//
		// Check if there's more than one sub-category type
		//
		$rsp['subcategorytypes'] = array();
		foreach($types as $tid => $type) {
			foreach($type['categories'] as $cnum => $cat) {
				//
				// Check for the overrides in the category
				//
				if( $cat['cat_name'] != '' ) {
					$type['categories'][$cnum]['name'] = $cat['cat_name'];

				}
				if( $cat['image_id'] > 0 ) {
					$type['categories'][$cnum]['image_id'] = $cat['image_id'];
					continue;
				} 

				//
				// Look for the highlight image, or the most recently added image
				//
				//$strsql = "SELECT ciniki_products.primary_image_id, ciniki_images.image "
				$strsql = "SELECT ciniki_products.primary_image_id "
					. "FROM ciniki_product_tags, ciniki_products, ciniki_images "
					. "WHERE ciniki_product_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND ciniki_product_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $cat['permalink']) . "' "
					// FIXME: Change to use proper tag_type, based on category and subcategory permalinks
//					. "AND ciniki_product_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $type['tag_type']) . "' "
					. "AND ciniki_product_tags.tag_type > 10 "
					. "AND ciniki_product_tags.tag_type < 30 "
					. "AND ciniki_product_tags.product_id = ciniki_products.id "
					. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND ciniki_products.primary_image_id = ciniki_images.id "
					. "AND ciniki_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND ciniki_products.start_date < UTC_TIMESTAMP() "
					. "AND (ciniki_products.end_date = '0000-00-00 00:00:00' "
						. "OR ciniki_products.end_date > UTC_TIMESTAMP()"
						. ") "
					. "AND (ciniki_products.webflags&0x01) > 0 "
					. "ORDER BY (ciniki_products.webflags&0x20) DESC, (ciniki_products.webflags&0x10) DESC, "
					. "ciniki_products.date_added DESC "
					. "LIMIT 1";
                error_log($strsql);
				$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'image');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( isset($rc['image']) ) {
					$type['categories'][$cnum]['image_id'] = $rc['image']['primary_image_id'];
				} else {
					$type['categories'][$cnum]['image_id'] = 0;
				}
			}
			$rsp['subcategorytypes'][] = $type;
		}
    error_log('test-006');
	} else {
		$rsp['subcategories'] = array();
	}

	//
	// Check for subcategories
	//
/*	$strsql = "SELECT t2.tag_type, t2.tag_name AS name, "
		. "t2.permalink, "
		. "IFNULL(ciniki_product_categories.name, '') AS cat_name, "
		. "IFNULL(ciniki_product_categories.primary_image_id, 0) AS image_id, "
		. "IFNULL(ciniki_product_categories.synopsis, '') AS synopsis, "
		. "COUNT(ciniki_products.id) AS num_products "
		. "FROM ciniki_product_tags AS t1 "
		. "LEFT JOIN ciniki_product_tags AS t2 ON ("
			. "t1.product_id = t2.product_id "
			. "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND t2.tag_type > 10 "
			. "AND t2.tag_type < 30 "
			. ") "
		. "LEFT JOIN ciniki_product_categories ON ("
			. "t2.tag_type = ciniki_product_categories.tag_type "
			. "AND t2.tag_name <> '' "
			. "AND t2.permalink = ciniki_product_categories.permalink "
			. "AND ciniki_product_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "LEFT JOIN ciniki_products ON ("
			. "t2.product_id = ciniki_products.id "
			. "AND ciniki_products.parent_id = 0 "
			. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_products.start_date < UTC_TIMESTAMP() "
			. "AND (ciniki_products.end_date = '0000-00-00 00:00:00' "
				. "OR ciniki_products.end_date > UTC_TIMESTAMP()"
				. ") "
			. ") "
		. "WHERE t1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category_permalink']) . "' "
		. "AND t1.tag_type = 10 "
		. "AND (ciniki_products.webflags&0x01) > 0 "
		. "GROUP BY t2.tag_type, t2.tag_name "
		. "ORDER BY t2.tag_type, t2.tag_name "
		. "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'types', 'fname'=>'tag_type', 
			'fields'=>array('tag_type')),
		array('container'=>'categories', 'fname'=>'name', 
			'fields'=>array('name', 'permalink', 'cat_name', 'image_id', 'synopsis', 'num_products')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['types']) && count($rc['types']) > 0 ) {
		$types = $rc['types'];
		//
		// Load highlight images
		//
		foreach($types as $tid => $type) {
			foreach($type['categories'] as $cnum => $cat) {
				//
				// Check for the overrides in the category
				//
				if( $cat['cat_name'] != '' ) {
					$types[$tid]['categories'][$cnum]['name'] = $cat['cat_name'];
				}
				if( $cat['image_id'] > 0 ) {
					$types[$tid]['categories'][$cnum]['image_id'] = $cat['image_id'];
					continue;
				} 

				//
				// Look for the highlight image, or the most recently added image
				//
				$strsql = "SELECT ciniki_products.primary_image_id, ciniki_images.image "
					. "FROM ciniki_product_tags, ciniki_products, ciniki_images "
					. "WHERE ciniki_product_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND ciniki_product_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $cat['permalink']) . "' "
					. "AND ciniki_product_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $tid) . "' "
					. "AND ciniki_product_tags.product_id = ciniki_products.id "
					. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND ciniki_products.primary_image_id = ciniki_images.id "
					. "AND ciniki_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND ciniki_products.start_date < UTC_TIMESTAMP() "
					. "AND (ciniki_products.end_date = '0000-00-00 00:00:00' "
						. "OR ciniki_products.end_date > UTC_TIMESTAMP()"
						. ") "
					. "AND (ciniki_products.webflags&0x01) > 0 "
					. "ORDER BY (ciniki_products.webflags&0x20) DESC, (ciniki_products.webflags&0x10) DESC, "
					. "ciniki_products.date_added DESC "
					. "LIMIT 1";
				$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'image');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( isset($rc['image']) ) {
					$types[$tid]['categories'][$cnum]['image_id'] = $rc['image']['primary_image_id'];
				} else {
					$types[$tid]['categories'][$cnum]['image_id'] = 0;
				}
			}
		}
		
		//
		// Check if there's more than one sub-category type
		//
		if( count($types) == 1 ) {
			$rsp['subcategories'] = array_pop($types);
			$rsp['subcategories'] = $rsp['subcategories']['categories'];
		} else {
			$rsp['subcategorytypes'] = $types;
		}
	} else {
		$rsp['subcategories'] = array();
	}
*/
	
    error_log('test-007');
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
			. "AND ciniki_products.parent_id = 0 "
			. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_products.start_date < UTC_TIMESTAMP() "
			. "AND (ciniki_products.end_date = '0000-00-00 00:00:00' "
				. "OR ciniki_products.end_date > UTC_TIMESTAMP()"
				. ") "
			. "AND (ciniki_products.webflags&0x01) > 0 "
			. ") "
		. "LEFT JOIN ciniki_product_tags AS t2 ON ("
			. "ciniki_products.id = t2.product_id "
			. "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND t2.tag_type > 10 "
			. "AND t2.tag_type < 30 "
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
    error_log('test-008');

	return $rsp;
}
?>
