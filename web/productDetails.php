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

	$modules = array();
	if( isset($ciniki['business']['modules']) ) {
		$modules = $ciniki['business']['modules'];
	}

	$strsql = "SELECT ciniki_products.id, "
		. "ciniki_products.name, "
		. "ciniki_products.permalink, "
		. "ciniki_products.short_description, "
		. "ciniki_products.long_description, "
		. "ciniki_products.webflags, "
		. "ciniki_products.price, "
		. "ciniki_products.unit_discount_amount, "
		. "ciniki_products.unit_discount_percentage, "
		. "ciniki_products.taxtype_id, "
		. "ciniki_products.inventory_flags, "
		. "ciniki_products.inventory_current_num, "
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
			'short_description', 'long_description', 'webflags',
			'price', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id',
			'inventory_flags', 'inventory_current_num')),
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
	// Setup the shopping cart prices for the product
	//
	$product['prices'] = array();

	//
	// Check if the product is sold online
	//
	if( ($product['webflags']&0x04) == 0 || ($product['webflags']&0x02) > 0 ) {
		$product['prices']['1'] = array(
			'object'=>'ciniki.products.product',
			'object_id'=>$product['id'],
			'name'=>'Price',
			'unit_amount'=>$product['price'],
			'unit_discount_amount'=>$product['unit_discount_amount'],
			'unit_discount_percentage'=>$product['unit_discount_percentage'],
			'taxtype_id'=>$product['taxtype_id'],
			'cart'=>'no',
			'limited_units'=>'no',
			'units_available'=>0,
			);

		// Check if product is to be sold online
		if( ($product['webflags']&0x02) > 0 ) {
			$product['prices']['1']['cart'] = 'yes';
		}
		// Check if product has inventory or unlimited
		if( ($product['inventory_flags']&0x01) > 0 ) {
			$product['prices']['1']['limited_units'] = 'yes';
			$product['prices']['1']['units_available'] = $product['inventory_current_num'];
		}
	}

	//
	// **FUTURE** add sub table for price options
	//


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

	//
	// Check if any similar products
	//
	if( isset($modules['ciniki.products']['flags']) 
		&& ($modules['ciniki.products']['flags']&0x01) > 0
		) {
		$strsql = "SELECT ciniki_products.id, "
			. "ciniki_products.name, "
			. "ciniki_products.permalink, "
			. "ciniki_products.short_description, "
			. "ciniki_products.long_description, "
			. "ciniki_products.primary_image_id, "
			. "ciniki_products.short_description, "
			. "'yes' AS is_details, "
			. "UNIX_TIMESTAMP(ciniki_products.last_updated) AS last_updated "
			. "FROM ciniki_product_relationships "
			. "LEFT JOIN ciniki_products ON ((ciniki_product_relationships.product_id = ciniki_products.id "
					. "OR ciniki_product_relationships.related_id = ciniki_products.id) "
				. "AND ciniki_products.id <> '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
				. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE (ciniki_product_relationships.product_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
				. "OR ciniki_product_relationships.related_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
				. ") "
			. "AND ciniki_product_relationships.relationship_type = 10 "
			. "AND ciniki_product_relationships.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ""; 
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id',
				'fields'=>array('id', 'image_id'=>'primary_image_id', 'title'=>'name', 'permalink', 
					'description'=>'short_description', 'is_details', 'last_updated')),
			));
		if( $rc['stat'] == 'ok' && isset($rc['products']) ) {
			$product['similar'] = $rc['products'];
		}
	}

	//
	// Check for any recipes
	//
	if( isset($modules['ciniki.products']['flags']) 
		&& ($modules['ciniki.products']['flags']&0x02) > 0 
		&& isset($modules['ciniki.recipes']) ) {
		$strsql = "SELECT ciniki_recipes.id, "
			. "ciniki_recipes.name, "
			. "ciniki_recipes.permalink, "
			. "ciniki_recipes.image_id, "
			. "ciniki_recipes.description, "
			. "'yes' AS is_details, "
			. "UNIX_TIMESTAMP(ciniki_recipes.last_updated) AS last_updated "
			. "FROM ciniki_product_refs "
			. "LEFT JOIN ciniki_recipes ON (ciniki_product_refs.object_id = ciniki_recipes.id "
				. "AND ciniki_recipes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_product_refs.product_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
			. "AND ciniki_product_refs.object = 'ciniki.recipes.recipe' "
			. "AND ciniki_product_refs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ""; 
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'recipes', 'fname'=>'id',
				'fields'=>array('id', 'image_id', 'title'=>'name', 'permalink',
					'description', 'is_details', 'last_updated')),
			));
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['recipes']) ) {
			$product['recipes'] = $rc['recipes'];
		}
	}

	return array('stat'=>'ok', 'product'=>$product);
}
?>
