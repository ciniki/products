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
function ciniki_products_web_productDetails($ciniki, $settings, $business_id, $args) {
	//
	// Load currency and timezone settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');


	$modules = array();
	if( isset($ciniki['business']['modules']) ) {
		$modules = $ciniki['business']['modules'];
	}

	//
	// Get the product details
	//
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
		. "ciniki_product_types.object_def "
//		. "ciniki_product_images.image_id, "
//		. "ciniki_product_images.name AS image_name, "
//		. "ciniki_product_images.permalink AS image_permalink, "
//		. "ciniki_product_images.description AS image_description, "
//		. "UNIX_TIMESTAMP(ciniki_product_images.last_updated) AS image_last_updated "
		. "FROM ciniki_products "
		. "LEFT JOIN ciniki_product_types ON ("
			. "ciniki_products.type_id = ciniki_product_types.id "
			. "AND ciniki_product_types.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
//		. "LEFT JOIN ciniki_product_images ON ("
//			. "ciniki_products.id = ciniki_product_images.product_id "
//			. "AND (ciniki_product_images.webflags&0x01) = 0 "
//			. ") "
		. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_products.permalink = '" . ciniki_core_dbQuote($ciniki, $args['product_permalink']) . "' "
		. "AND ciniki_products.start_date < UTC_TIMESTAMP() "
		. "AND (ciniki_products.end_date = '0000-00-00 00:00:00' "
			. "OR ciniki_products.end_date > UTC_TIMESTAMP()"
			. ") "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artclub', array(
		array('container'=>'products', 'fname'=>'id', 
			'fields'=>array('id', 'name', 'permalink', 'image_id'=>'primary_image_id', 
			'short_description', 'long_description', 'webflags',
			'price', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id',
			'inventory_flags', 'inventory_current_num', 'object_def')),
//		array('container'=>'images', 'fname'=>'image_id', 
//			'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
//				'description'=>'image_description', 
//				'last_updated'=>'image_last_updated')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['products']) || count($rc['products']) < 1 ) {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'1506', 'msg'=>"I'm sorry, but we can't find the product you requested."));
	}
	$product = array_pop($rc['products']);
	$product['object_def'] = unserialize($product['object_def']);

	//
	// Get the number of unit unshipped in purchase orders
	//
	$reserved_quantity = 0;
	if( isset($ciniki['business']['modules']['ciniki.sapos']) ) {
		$cur_invoice_id = 0;
		if( isset($ciniki['session']['cart']['sapos_id']) && $ciniki['session']['cart']['sapos_id'] > 0 ) {
			$cur_invoice_id = $ciniki['session']['cart']['sapos_id'];
		}
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getReservedQuantities');
		$rc = ciniki_sapos_getReservedQuantities($ciniki, $business_id, 
			'ciniki.products.product', array($product['id']), $cur_invoice_id);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['quantities'][$product['id']]) ) {
			$reserved_quantity = $rc['quantities'][$product['id']]['quantity_reserved'];
		}
	}

	// If complex pricing is NOT specified
	if( !isset($product['object_def']['parent']['prices']['unit_amount']) ) {
		//
		// Setup the shopping cart prices for the product
		//
		$product['prices'] = array();

		//
		// Check if the product is sold online
		//
		if( ($product['webflags']&0x02) == 0 || ($product['webflags']&0x02) > 0 ) {
			$product['prices']['1'] = array(
				'id'=>0,
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
				if( ($product['inventory_flags']&0x02) > 0 ) {
					// Backordering available for this product
					$product['prices']['1']['limited_units'] = 'no';
				} else {
					$product['prices']['1']['limited_units'] = 'yes';
				}
				$product['prices']['1']['units_available'] = $product['inventory_current_num'] - $reserved_quantity;
			}
		}
	} else {
		//
		// Check if any prices are attached to the product
		//
		if( isset($ciniki['session']['customer']['price_flags']) ) {
			$price_flags = $ciniki['session']['customer']['price_flags'];
		} else {
			$price_flags = 0x01;
		}
		//
		// If the customer has a pricepoint set, then get the applicable prices for that customer
		//
//		print "<pre>" . print_r($ciniki['session'], true) . "</pre>";
		if( isset($ciniki['session']['customer']['pricepoint']['id']) 
			&& $ciniki['session']['customer']['pricepoint']['id'] > 0 
			) {
			//
			// Get all prices, regardless of pricepoint
			//
			$strsql = "SELECT ciniki_product_prices.id, "
				. "ciniki_product_prices.name, "
				. "ciniki_product_prices.pricepoint_id, "
				. "ciniki_customer_pricepoints.sequence AS pricepoint_sequence, "
				. "ciniki_customer_pricepoints.flags AS pricepoint_flags, "
				. "ciniki_product_prices.available_to, "
				. "ciniki_product_prices.unit_amount, "
				. "ciniki_product_prices.unit_discount_amount, "
				. "ciniki_product_prices.unit_discount_percentage "
				. "FROM ciniki_product_prices "
				. "LEFT JOIN ciniki_customer_pricepoints ON ("
					. "ciniki_product_prices.pricepoint_id = ciniki_customer_pricepoints.id "
					. "AND ciniki_customer_pricepoints.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. ") "
				. "WHERE ciniki_product_prices.product_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
				. "AND ciniki_product_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND (ciniki_product_prices.webflags&0x01) = 0 "
				. "AND ((ciniki_product_prices.available_to&$price_flags) > 0 OR (webflags&available_to&0xF0) > 0) "
				. "";
			if( ($ciniki['session']['customer']['pricepoint']['flags']&0x01) == 0 ) {
				$strsql .= "AND (ciniki_product_prices.pricepoint_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['pricepoint']['id']) . "' "
					. " OR ciniki_product_prices.pricepoint_id = 0 "
					. ") ";
			}
			$strsql .= "ORDER BY ciniki_customer_pricepoints.sequence ASC, ciniki_product_prices.name "
				. "";
			$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
				array('container'=>'prices', 'fname'=>'id',
					'fields'=>array('id', 'name', 'pricepoint_id', 'pricepoint_sequence', 'available_to', 
						'unit_amount', 'unit_discount_amount', 'unit_discount_percentage')),
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['prices']) ) {
				$product['prices'] = $rc['prices'];
			}

			//
			// Find the product price that matches the customers pricepoint_id or the previous pricepoint,
			// which should be the higher price amount
			//
			$prev_pid = -1;
			$pricepoint_found = 'no';
			if( isset($product['prices']) ) {
				foreach($product['prices'] as $pid => $price) {
					if( $price['pricepoint_sequence'] > $ciniki['session']['customer']['pricepoint']['sequence'] ) {
						unset($product['prices'][$pid]);
						continue;
					}
					if( $price['pricepoint_id'] == $ciniki['session']['customer']['pricepoint']['id'] ) {
						$product['prices'] = array($pid=>$price);
						$pricepoint_found = 'yes';
						break;
					}
					$prev_pid = $pid;
				}
				if( $pricepoint_found == 'no' && $prev_pid > -1 ) {
					$product['prices'] = array($prev_pid=>$product['prices'][$prev_pid]);
				}
//				print "<pre>" . print_r($product['prices'], true) . "</pre>";
			}
		} else {
			//
			// Get only those prices with no pricepoint set
			//
			$strsql = "SELECT ciniki_product_prices.id, "
				. "ciniki_product_prices.name, "
				. "ciniki_product_prices.pricepoint_id, "
				. "ciniki_product_prices.available_to, "
				. "ciniki_product_prices.unit_amount, "
				. "ciniki_product_prices.unit_discount_amount, "
				. "ciniki_product_prices.unit_discount_percentage "
				. "FROM ciniki_product_prices "
				. "WHERE ciniki_product_prices.product_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
				. "AND ciniki_product_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND ciniki_product_prices.pricepoint_id = 0 "
				. "AND (ciniki_product_prices.webflags&0x01) = 0 "
				// Find only prices that are available to customer OR visible on website

				. "AND ((ciniki_product_prices.available_to&$price_flags) > 0 "
					// Use available to with webflags to make sure the price is available to that group
					// then make sure one is turned on
					. "OR (webflags&available_to&0xF0) > 0) "
				. "ORDER BY ciniki_product_prices.name "
				. "";
			$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
				array('container'=>'prices', 'fname'=>'id',
					'fields'=>array('id', 'name', 'available_to', 
						'unit_amount', 'unit_discount_amount', 'unit_discount_percentage')),
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['prices']) ) {
				$product['prices'] = $rc['prices'];
			}
		}
		
		//
		// Format the list of prices
		//
		if( isset($product['prices']) && count($product['prices']) > 0 ) {
			foreach($product['prices'] as $pid => $price) {
				// Check if online registrations enabled
				if( ($product['webflags']&0x02) > 0 && ($price['available_to']&$price_flags) > 0 ) {
					$product['prices'][$pid]['cart'] = 'yes';
				} else {
					$product['prices'][$pid]['cart'] = 'no';
				}
				$product['prices'][$pid]['object'] = 'ciniki.products.product';
				$product['prices'][$pid]['object_id'] = $product['id'];
				$product['prices'][$pid]['price_id'] = $price['id'];
				if( ($product['inventory_flags']&0x02) > 0 ) {
					// Backordering available for this product
					$product['prices'][$pid]['limited_units'] = 'no';
				} else {
					$product['prices'][$pid]['limited_units'] = 'yes';
				}
				$product['prices'][$pid]['units_inventory'] = $product['inventory_current_num'];
				$product['prices'][$pid]['units_available'] = $product['inventory_current_num'] - $reserved_quantity;
				$product['prices'][$pid]['unit_amount_display'] = numfmt_format_currency(
					$intl_currency_fmt, $price['unit_amount'], $intl_currency);
			}
		} else {
			$product['prices'] = array();
		}
	}

	//
	// Get any images 
	//
	$strsql = "SELECT id, image_id, name, permalink, sequence, webflags, description, "
		. "UNIX_TIMESTAMP(last_updated) AS last_updated "
		. "FROM ciniki_product_images "
		. "WHERE product_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (webflags&0x01) = 1 "		// Visible images
		. "ORDER BY sequence, date_added, name "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'images', 'fname'=>'id', 'name'=>'image',
			'fields'=>array('id', 'image_id', 'title'=>'name', 'permalink', 'sequence', 'webflags', 
				'description', 'last_updated')),
		));
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	if( isset($rc['images']) ) {
		$product['images'] = $rc['images'];
	} else {
		$product['images'] = array();
	}

	//
	// Check if any audio attached to product
	//
	$strsql = "SELECT ciniki_product_audio.id, "
		. "ciniki_product_audio.name, "
		. "ciniki_product_audio.sequence, "
		. "ciniki_product_audio.webflags, "
		. "ciniki_product_audio.mp3_audio_id, "
		. "ciniki_product_audio.wav_audio_id, "
		. "ciniki_product_audio.ogg_audio_id, "
		. "ciniki_audio.id AS audio_id, "
		. "ciniki_audio.original_filename, "
		. "ciniki_audio.type AS audio_type, "
		. "ciniki_audio.type AS extension, "
		. "ciniki_audio.uuid AS audio_uuid, "
		. "ciniki_product_audio.description "
		. "FROM ciniki_product_audio "
		. "LEFT JOIN ciniki_audio ON ("
			. "(ciniki_product_audio.mp3_audio_id = ciniki_audio.id "
				. "OR ciniki_product_audio.wav_audio_id = ciniki_audio.id "
				. "OR ciniki_product_audio.ogg_audio_id = ciniki_audio.id "
				. ") "
			. "AND ciniki_audio.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_product_audio.product_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
		. "AND ciniki_product_audio.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (ciniki_product_audio.webflags&0x01) = 1 "
		. "ORDER BY ciniki_product_audio.sequence, ciniki_product_audio.name, "
			. "ciniki_product_audio.date_added, ciniki_audio.type DESC "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'audio', 'fname'=>'id',
			'fields'=>array('id', 'name', 'sequence', 'webflags', 
				'mp3_audio_id', 'wav_audio_id', 'ogg_audio_id', 'description')),
		array('container'=>'formats', 'fname'=>'audio_id',
			'fields'=>array('id'=>'audio_id', 'uuid'=>'audio_uuid', 'type'=>'audio_type', 
				'original_filename', 'extension'),
			'maps'=>array('extension'=>array('20'=>'ogg', '30'=>'wav', '40'=>'mp3')),
			),
		));
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	if( isset($rc['audio']) ) {
		$product['audio'] = $rc['audio'];
	} else {
		$product['audio'] = array();
	}

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
			// Check for a relationship where the requested product is the primary, 
			// OR where the product is the secondary and it's a cross linked relationship_type
			. "WHERE ((ciniki_product_relationships.product_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
					. "OR (ciniki_product_relationships.related_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
						. "AND ciniki_product_relationships.relationship_type = 10) "
					. ") "
				. ") "
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

	//
	// Get all the categories, sub-categories and tags associated with this product 
	// for use in the share-buttons
	//
	$strsql = "SELECT tag_name "
		. "FROM ciniki_product_tags "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND product_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
		. "ORDER BY tag_type "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
	$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.products', 'tags', 'tag_name');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['tags']) ) {
		$product['social-tags'] = array();
		foreach($rc['tags'] as $tid => $tag) {
			$product['social-tags'][] = preg_replace("/[^a-zA-Z0-9]/", '', $tag);
		}
	} else {
		$product['social-tags'] = array();
	}

	//
	// If specified, get the category title
	//
	if( isset($args['category_permalink']) && $args['category_permalink'] != '' ) {
		$strsql = "SELECT t1.tag_name, c1.name "
			. "FROM ciniki_product_tags AS t1 "
			. "LEFT JOIN ciniki_product_categories AS c1 ON ("
				. "t1.permalink = c1.category "
				. "AND c1.subcategory = '' "
				. "AND c1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE t1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category_permalink']) . "' "
			. "AND t1.product_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
			. "AND t1.tag_type = 10 "
			. "LIMIT 1 "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'tag');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['tag']) ) {
			if( $rc['tag']['name'] != '' ) {
				$product['category_title'] = $rc['tag']['name'];
			} else {
				$product['category_title'] = $rc['tag']['tag_name'];
			}
		}
	}

	if( isset($args['subcategory_permalink']) && $args['subcategory_permalink'] != '' ) {
		$strsql = "SELECT tag_name "
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
		if( isset($rc['tag']) ) {
			$product['subcategory_title'] = $rc['tag']['tag_name'];
		}
	}


	return array('stat'=>'ok', 'product'=>$product);
}
?>
