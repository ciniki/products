<?php
//
// Description
// -----------
// This function will load a product and all it's information.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_products_productLoad($ciniki, $business_id, $product_id, $args) {
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');
	$datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

	//
	// Load the status maps for the text description of each type
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'maps');
	$rc = ciniki_products_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	//
	// Get the basic product information
	//
	$strsql = "SELECT ciniki_products.id, "
		. "ciniki_products.parent_id, "
		. "ciniki_products.name, "
		. "ciniki_products.code, "
		. "ciniki_products.sequence, "
		. "ciniki_products.type_id, "
		. "ciniki_product_types.name_s AS type_text, "
		. "ciniki_product_types.object_def, "
//		. "type, type AS type_text, "
		. "ciniki_products.category, "
		. "ciniki_products.flags, "
		. "ciniki_products.status, ciniki_products.status AS status_text, "
		. "ciniki_products.barcode, "
		. "ciniki_products.supplier_id, "
//		. "ciniki_product_suppliers.name AS supplier_name, "
		. "ciniki_products.supplier_item_number, "
		. "ciniki_products.supplier_minimum_order, "
		. "ciniki_products.supplier_order_multiple, "
		. "ciniki_products.manufacture_min_time, "
		. "ciniki_products.manufacture_max_time, "
		. "ciniki_products.inventory_flags, "
		. "ciniki_products.inventory_current_num, "
		. "ciniki_products.inventory_reorder_num, "
		. "ciniki_products.inventory_reorder_quantity, "
		. "ciniki_products.price, "
		. "ciniki_products.cost, "
		. "ciniki_products.msrp, "
		. "ciniki_products.taxtype_id, "
		. "ciniki_products.sell_unit, "
		. "ciniki_products.shipping_flags, "
		. "ciniki_products.shipping_weight, "
		. "ciniki_products.shipping_weight_units, "
		. "ciniki_products.shipping_length, "
		. "ciniki_products.shipping_width, "
		. "ciniki_products.shipping_height, "
		. "ciniki_products.shipping_size_units, "
		. "ciniki_products.primary_image_id, "
		. "ciniki_products.short_description, "
		. "ciniki_products.long_description, "
		. "ciniki_products.start_date, "
		. "ciniki_products.end_date, "
		. "ciniki_products.webflags, "
		. "IF((ciniki_products.webflags&0x01)=1,'Hidden','Visible') AS webvisible, "
		. "ciniki_products.detail01, "
		. "ciniki_products.detail02, "
		. "ciniki_products.detail03, "
		. "ciniki_products.detail04, "
		. "ciniki_products.detail05, "
		. "ciniki_products.detail06, "
		. "ciniki_products.detail07, "
		. "ciniki_products.detail08, "
		. "ciniki_products.detail09 "
		. "FROM ciniki_products "
		. "LEFT JOIN ciniki_product_types ON (ciniki_products.type_id = ciniki_product_types.id "
			. "AND ciniki_product_types.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
//		. "LEFT JOIN ciniki_product_suppliers ON (ciniki_products.supplier_id = ciniki_product_suppliers.id "
//			. "AND ciniki_product_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
//			. ") "
		. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_products.id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'products', 'fname'=>'id', 'name'=>'product',
			'fields'=>array('id', 'parent_id', 'name', 'code', 'type_id', 'type_text', 'object_def',
				'category', 'flags', 'status', 'status_text',
				'supplier_id', 'supplier_item_number', 
				'supplier_minimum_order', 'supplier_order_multiple',
				'manufacture_min_time', 'manufacture_max_time', 
				'inventory_flags', 'inventory_current_num', 
				'inventory_reorder_num', 'inventory_reorder_quantity',
				'barcode', 'price', 'cost', 'msrp', 'taxtype_id', 'sell_unit',
				'shipping_flags', 'shipping_weight', 'shipping_weight_units',
				'shipping_length', 'shipping_width', 'shipping_height', 'shipping_size_units',
				'primary_image_id',
				'short_description', 'long_description', 'start_date', 'end_date',
				'webflags', 'webvisible',
				'detail01', 'detail02', 'detail03', 'detail04', 'detail05',
				'detail06', 'detail07', 'detail08', 'detail09'),
			'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				'end_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				),
			'maps'=>array('status_text'=>$maps['product']['status']),
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['products'][0]['product']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1471', 'msg'=>'Unable to find the specified product'));
	}
	$product = $rc['products'][0]['product'];

	$object_def = unserialize($product['object_def']);
	$product['object_def'] = json_encode($object_def);
	$pc = $product['parent_id']==0?'parent':'child';

	//
	// Format shipping values
	//
	$product['shipping_flags_text'] = '';
	if( ($product['shipping_flags']&0x01) > 0 ) { 
		$product['shipping_flags_text'] .= ($product['shipping_flags_text']!=null?', ':'') . 'Shipping';
	}
	if( ($product['shipping_flags']&0x02) > 0 ) { 
		$product['shipping_flags_text'] .= ($product['shipping_flags_text']!=null?', ':'') . 'Pickup';
	}
	$product['shipping_weight'] = (float)$product['shipping_weight'];
	$product['shipping_length'] = (float)$product['shipping_length'];
	$product['shipping_width'] = (float)$product['shipping_width'];
	$product['shipping_height'] = (float)$product['shipping_height'];

	if( isset($object_def[$pc]['products']['shipping_weight']) ) {
		$product['shipping_package'] = '';
		$product['shipping_package'] .= (float)$product['shipping_weight'];
		if( $product['shipping_weight'] > 1 ) {
			switch($product['shipping_weight_units']) {
				case 10: $product['shipping_package'] .= ' lbs'; break;
				case 20: $product['shipping_package'] .= ' kgs'; break;
			}
		} else {
			switch($product['shipping_weight_units']) {
				case 10: $product['shipping_package'] .= ' lb'; break;
				case 20: $product['shipping_package'] .= ' kg'; break;
			}
		}
		$product['shipping_package'] .= ' ' . (float)$product['shipping_length'] . 'x' . (float)$product['shipping_width'] . 'x' . (float)$product['shipping_height'] . ' ';
		switch($product['shipping_size_units']) {
			case 10: $product['shipping_package'] .= ' in'; break;
			case 20: $product['shipping_package'] .= ' cm'; break;
		}
		if( $product['shipping_weight'] == 0 && $product['shipping_length'] == 0 && $product['shipping_width'] == 0 && $product['shipping_height'] == 0 ) {
			$product['shipping_package'] = 'Not Setup';
		}
	}

	//
	// Format the webflags_text string
	//
	$product['flags_text'] = '';
	if( ($product['flags']&0x04) > 0 ) {
		$product['flags_text'] .= ($product['flags_text']!=''?', ':'') . 'Promotional Item';
	}
	$product['webflags_text'] = '';
    if( ($product['webflags']&0x01) > 0 ) { 
        $product['webflags_text'] .= 'Visible';
    } elseif( ($product['webflags']&0x0f00) == 0 ) { 
        $product['webflags_text'] .= 'Hidden';
    } else {
        if( ($product['webflags']&0x0100) > 0 ) { 
            $product['webflags_text'] .= 'Visible to customers';
        }
        if( ($product['webflags']&0x0200) > 0 ) { 
            $product['webflags_text'] .= 'Visible to members';
        }
        if( ($product['webflags']&0x0400) > 0 ) { 
            $product['webflags_text'] .= 'Visible to dealers';
        }
        if( ($product['webflags']&0x0800) > 0 ) { 
            $product['webflags_text'] .= 'Visible to distributors';
        }
    }
	if( ($product['webflags']&0x02) > 0 ) {
		$product['webflags_text'] .= ($product['webflags_text']!=''?', ':'') . 'Sold Online';
	}
	if( ($product['webflags']&0x04) > 0 ) {
		$product['webflags_text'] .= ($product['webflags_text']!=''?', ':'') . 'Price Hidden';
	} else {
		$product['webflags_text'] .= ($product['webflags_text']!=''?', ':'') . 'Price Visible';
	}

	$product['manufacture_times'] = '';
	if( $product['manufacture_min_time'] != '' && $product['manufacture_max_time'] != '' 
		&& $product['manufacture_max_time'] > 0 ) {
		$product['manufacture_times'] = $product['manufacture_min_time'] . ' - ' . $product['manufacture_max_time'] . ' minutes';
	}

	//
	// If a supplier is specified, get the name
	//
	if( $product['supplier_id'] > 0 ) {
		$strsql = "SELECT id, name "
			. "FROM ciniki_product_suppliers "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $product['supplier_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'supplier');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['supplier']) ) {
			$product['supplier_name'] = $rc['supplier']['name'];
		}
	}

	//
	// Get the product details
	//
//	$strsql = "SELECT detail_key, detail_value FROM ciniki_product_details "
//		. "WHERE product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' ";
 //   ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuery');
  //  ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFetchHashRow');
//	$rc = ciniki_core_dbQuery($ciniki, $strsql, 'ciniki.products');
//	if( $rc['stat'] != 'ok' ) {
//		return $rc;
//	}
//	$result_handle = $rc['handle'];
//
//	$result = ciniki_core_dbFetchHashRow($ciniki, $result_handle);
//	while( isset($result['row']) ) {
//		$product[$result['row']['detail_key']] = $result['row']['detail_value'];
//		$result = ciniki_core_dbFetchHashRow($ciniki, $result_handle);
//	}

	//
	// Get the prices for the product
	//
	if( isset($args['prices']) && $args['prices'] == 'yes' ) {
		//
		// Get the price list for the product
		//
		$strsql = "SELECT ciniki_product_prices.id, ciniki_product_prices.name, "
			. "ciniki_product_prices.available_to, "
			. "ciniki_product_prices.available_to AS available_to_text, "
			. "ciniki_product_prices.unit_amount, "
			. "ciniki_product_prices.unit_discount_amount, "
			. "ciniki_product_prices.unit_discount_percentage, "
			. "pricepoint_id, IFNULL(ciniki_customer_pricepoints.name, '') AS pricepoint_id_text "
			. "FROM ciniki_product_prices "
			. "LEFT JOIN ciniki_customer_pricepoints ON ("
				. "ciniki_product_prices.pricepoint_id = ciniki_customer_pricepoints.id "
				. "AND ciniki_customer_pricepoints.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_product_prices.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
			. "AND ciniki_product_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "ORDER BY ciniki_customer_pricepoints.sequence, ciniki_product_prices.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'prices', 'fname'=>'id', 'name'=>'price',
				'fields'=>array('id', 'name', 'available_to', 'available_to_text', 'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
					'pricepoint_id', 'pricepoint_id_text'),
				'flags'=>array('available_to_text'=>$maps['price']['available_to'])),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['prices']) ) {
			$product['prices'] = $rc['prices'];
			foreach($product['prices'] as $pid => $price) {
				$product['prices'][$pid]['price']['unit_amount_display'] = numfmt_format_currency(
					$intl_currency_fmt, $price['price']['unit_amount'], $intl_currency);
			}
		} else {
			$product['prices'] = array();
		}
	}

	//
	// Get the categories and tags for the product
	//
	if( isset($object_def[$pc]['categories']) 
		|| isset($object_def[$pc]['subcategories-11']) 
		|| isset($object_def[$pc]['subcategories-12']) 
		|| isset($object_def[$pc]['subcategories-13']) 
		|| isset($object_def[$pc]['subcategories-14']) 
		|| isset($object_def[$pc]['subcategories-15']) 
		|| isset($object_def[$pc]['tags']) 
		) {
		$strsql = "SELECT tag_type, tag_name AS lists "
			. "FROM ciniki_product_tags "
			. "WHERE product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "ORDER BY tag_type, tag_name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
				'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['tags']) ) {
			foreach($rc['tags'] as $tags) {
				if( isset($object_def[$pc]['categories']) && $tags['tags']['tag_type'] == 10 ) {
					$product['categories'] = $tags['tags']['lists'];
				} elseif( isset($object_def[$pc]['subcategories-11']) && $tags['tags']['tag_type'] == 11 ) {
					$product['subcategories-11'] = $tags['tags']['lists'];
				} elseif( isset($object_def[$pc]['subcategories-12']) && $tags['tags']['tag_type'] == 12 ) {
					$product['subcategories-12'] = $tags['tags']['lists'];
				} elseif( isset($object_def[$pc]['subcategories-13']) && $tags['tags']['tag_type'] == 13 ) {
					$product['subcategories-13'] = $tags['tags']['lists'];
				} elseif( isset($object_def[$pc]['subcategories-14']) && $tags['tags']['tag_type'] == 14 ) {
					$product['subcategories-14'] = $tags['tags']['lists'];
				} elseif( isset($object_def[$pc]['subcategories-15']) && $tags['tags']['tag_type'] == 15 ) {
					$product['subcategories-15'] = $tags['tags']['lists'];
				} elseif( isset($object_def[$pc]['tags']) && $tags['tags']['tag_type'] == 20 ) {
					$product['tags'] = $tags['tags']['lists'];
				}
			}
		}
	}

	//
	// Get the images for the product
	//
	if( isset($args['images']) && $args['images'] == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
		$strsql = "SELECT ciniki_product_images.id, "
			. "ciniki_product_images.image_id, "
			. "ciniki_product_images.name, "
			. "ciniki_product_images.sequence, "
			. "ciniki_product_images.webflags, "
			. "ciniki_product_images.description "
			. "FROM ciniki_product_images "
			. "WHERE ciniki_product_images.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
			. "AND ciniki_product_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "ORDER BY ciniki_product_images.sequence, ciniki_product_images.date_added, ciniki_product_images.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'images', 'fname'=>'id', 'name'=>'image',
				'fields'=>array('id', 'image_id', 'name', 'sequence', 'webflags', 'description')),
			));
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['images']) ) {
			$product['images'] = $rc['images'];
			foreach($product['images'] as $img_id => $img) {
				if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
					$rc = ciniki_images_loadCacheThumbnail($ciniki, $business_id, $img['image']['image_id'], 75);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$product['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
				}
			}
		} else {
			$product['images'] = array();
		}
	}

	//
	// Get the audio for the product
	//
	if( isset($args['audio']) && $args['audio'] == 'yes' ) {
		$strsql = "SELECT ciniki_product_audio.id, "
			. "ciniki_product_audio.name, "
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
				. "AND ciniki_audio.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_product_audio.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
			. "AND ciniki_product_audio.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "ORDER BY ciniki_product_audio.sequence, ciniki_product_audio.name, ciniki_product_audio.date_added "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'audio', 'fname'=>'id', 'name'=>'audio',
				'fields'=>array('id', 'name', 'sequence', 'webflags', 
					'mp3_audio_id', 'wav_audio_id', 'ogg_audio_id', 'description')),
			array('container'=>'files', 'fname'=>'audio_id', 'name'=>'file',
				'fields'=>array('id'=>'audio_id', 'original_filename')),
			));
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['audio']) ) {
			$product['audio'] = $rc['audio'];
			foreach($product['audio'] as $aid => $audio) {
				if( isset($audio['audio']['files']) ) {
					foreach($audio['audio']['files'] as $fid => $file) {
						if( $file['file']['id'] == $audio['audio']['mp3_audio_id'] ) {
							$product['audio'][$aid]['audio']['mp3_audio_id_filename'] = $file['file']['original_filename'];
						}
						if( $file['file']['id'] == $audio['audio']['wav_audio_id'] ) {
							$product['audio'][$aid]['audio']['wav_audio_id_filename'] = $file['file']['original_filename'];
						}
						if( $file['file']['id'] == $audio['audio']['ogg_audio_id'] ) {
							$product['audio'][$aid]['audio']['ogg_audio_id_filename'] = $file['file']['original_filename'];
						}
					}
				}
			}
		} else {
			$product['audio'] = array();
		}
	}

	//
	// Get the files for the product
	//
	if( isset($args['files']) && $args['files'] == 'yes' ) {
		$strsql = "SELECT id, name, extension, permalink "
			. "FROM ciniki_product_files "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_product_files.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'files', 'fname'=>'id', 'name'=>'file',
				'fields'=>array('id', 'name', 'extension', 'permalink')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['files']) ) {
			$product['files'] = $rc['files'];
		} else {
			$product['files'] = array();
		}
	}

	//
	// Check if similar products is enabled and requested
	//
	if( isset($args['similar']) && $args['similar'] == 'yes' 
		&& ($ciniki['business']['modules']['ciniki.products']['flags']&0x01) > 0 
		) {
		$strsql = "SELECT ciniki_products.id, ciniki_product_relationships.id AS relationship_id, "
			. "ciniki_products.name "
			. "FROM ciniki_product_relationships "
			. "LEFT JOIN ciniki_products ON ((ciniki_product_relationships.product_id = ciniki_products.id "
					. "OR ciniki_product_relationships.related_id = ciniki_products.id) "
				. "AND ciniki_products.id <> '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
				. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE (ciniki_product_relationships.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
				. "OR (ciniki_product_relationships.related_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
					. "AND ciniki_product_relationships.relationship_type = 10) "
				. ") "
			. "AND ciniki_product_relationships.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ""; 
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id', 'name'=>'product',
				'fields'=>array('id', 'relationship_id', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['products']) ) {
			$product['similar'] = $rc['products'];
		}
	}

	if( isset($args['recipes']) && $args['recipes'] == 'yes' 
		&& isset($modules['ciniki.recipes'])
		&& ($modules['ciniki.products']['flags']&0x02) > 0 ) {
		$strsql = "SELECT ciniki_recipes.id, "
			. "ciniki_product_refs.id AS ref_id, "
			. "ciniki_recipes.name "
			. "FROM ciniki_product_refs "
			. "LEFT JOIN ciniki_recipes ON (ciniki_product_refs.object_id = ciniki_recipes.id "
				. "AND ciniki_recipes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_product_refs.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
			. "AND ciniki_product_refs.object = 'ciniki.recipes.recipe' "
			. "AND ciniki_product_refs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ""; 
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'recipes', 'fname'=>'id', 'name'=>'recipe',
				'fields'=>array('id', 'ref_id', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['recipes']) ) {
			$product['recipes'] = $rc['recipes'];
		}
	}

	//
	// Format the prices
	//
	$product['price'] = numfmt_format_currency($intl_currency_fmt, $product['price'], $intl_currency);
	$product['cost'] = numfmt_format_currency($intl_currency_fmt, $product['cost'], $intl_currency);
	$product['msrp'] = numfmt_format_currency($intl_currency_fmt, $product['msrp'], $intl_currency);

	return array('stat'=>'ok', 'product'=>$product);
}
?>
