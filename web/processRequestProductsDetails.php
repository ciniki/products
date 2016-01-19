<?php
//
// Description
// -----------
// This function gets extra details and information for products. It's best not to join all the tables
// at the same time if the information is not required on the webpage, so this allows extra information
// to be obtained when required.
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_products_web_processRequestProductsDetails(&$ciniki, $settings, $business_id, $products, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    $product_ids = array_keys($products);

    if( !isset($args['object_defs']) ) {
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
    } else {
        $object_defs = $args['object_defs'];
    }

    //
    // Look for audio samples
    //
    if( isset($args['audio']) && ($args['audio'] == 'required' || $args['audio'] == 'yes') ) {
        //
        // Check for any audio attached to products
        //
        $strsql = "SELECT ciniki_product_audio.id, "
            . "ciniki_product_audio.product_id, "
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
            . "WHERE ciniki_product_audio.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_product_audio.product_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $product_ids) . ") "
            . "AND (ciniki_product_audio.webflags&0x01) = 1 "
            . "ORDER BY ciniki_product_audio.product_id, ciniki_product_audio.sequence, ciniki_product_audio.name, "
                . "ciniki_product_audio.date_added, ciniki_audio.type DESC "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'products', 'fname'=>'product_id', 'fields'=>array()),
            array('container'=>'audio', 'fname'=>'id',
                'fields'=>array('id', 'name', 'sequence', 'webflags', 
                    'mp3_audio_id', 'wav_audio_id', 'ogg_audio_id', 'description')),
            array('container'=>'formats', 'fname'=>'audio_id',
                'fields'=>array('id'=>'audio_id', 'uuid'=>'audio_uuid', 'type'=>'audio_type', 
                    'original_filename', 'extension'),
                'maps'=>array('extension'=>array('20'=>'ogg', '30'=>'wav', '40'=>'mp3')),
                ),
            ));
//    				print "<pre>" . print_r($rc, true) . "</pre>";
        if( $rc['stat'] == 'ok' ) {  
            foreach($products as $pid => $product) {
                if( isset($rc['products'][$pid]['audio']) ) {
                    $products[$pid]['audio'] = $rc['products'][$pid]['audio'];
                } elseif( $args['audio'] == 'required' ) {
                    unset($products[$pid]);
                } else {
                    $products[$pid]['audio'] = array();
                }
            }
        }
    }

    //
    // If images, get the last_updated for use in caching images
    //
    if( isset($args['image']) && $args['image'] == 'yes' ) {
        $strsql = "SELECT ciniki_products.id, "
            . "IF(ciniki_images.last_updated > ciniki_products.last_updated, "
                . "UNIX_TIMESTAMP(ciniki_images.last_updated), "
                . "UNIX_TIMESTAMP(ciniki_products.last_updated)) AS last_updated "
            . "FROM ciniki_products "
            . "LEFT JOIN ciniki_images ON ("
                . "ciniki_products.primary_image_id = ciniki_images.id "
                . "AND ciniki_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_products.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $product_ids) . ") "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'products', 'fname'=>'id', 'fields'=>array('last_updated')),
            ));
        if( $rc['stat'] == 'ok' ) {  
            foreach($products as $pid => $product) {
                $products[$pid]['last_updated'] = $rc['products'][$pid]['last_updated'];
            }
        }
    }

    //
    // Get the list of prices
    //
    if( isset($args['prices']) && ($args['prices'] == 'yes' || $args['prices'] == 'required') ) {
        //
        // Load currency and timezone settings
        //
/*        ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
        $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $intl_timezone = $rc['settings']['intl-default-timezone'];
        $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
        $intl_currency = $rc['settings']['intl-default-currency'];
*/
        //
        // Get any complex prices for products
        //
		if( isset($ciniki['session']['customer']['price_flags']) ) {
			$price_flags = $ciniki['session']['customer']['price_flags'];
		} else {
			$price_flags = 0x01;
		}
		//
		// If the customer has a pricepoint set, then get the applicable prices for that customer
		//
		if( isset($ciniki['session']['customer']['pricepoint']['id']) && $ciniki['session']['customer']['pricepoint']['id'] > 0 ) {
			//
			// Get all prices, regardless of pricepoint
			//
			$strsql = "SELECT ciniki_product_prices.id, "
				. "ciniki_product_prices.product_id, "
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
				. "WHERE ciniki_product_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND ciniki_product_prices.product_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $product_ids) . ") "
				. "AND (ciniki_product_prices.webflags&0x01) = 0 "
				. "AND ((ciniki_product_prices.available_to&$price_flags) > 0 OR (webflags&available_to&0xF0) > 0) "
				. "";
			// Check if pricepoints should be restricted to only those available to the customer, or
			// if we should get all and decide on best one for the customer.
			if( ($ciniki['session']['customer']['pricepoint']['flags']&0x01) == 0 ) {
				$strsql .= "AND (ciniki_product_prices.pricepoint_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['pricepoint']['id']) . "' "
					. " OR ciniki_product_prices.pricepoint_id = 0 "
					. ") ";
			}
			$strsql .= "ORDER BY ciniki_product_prices.product_id, ciniki_customer_pricepoints.sequence ASC, ciniki_product_prices.name "
				. "";
			$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
                array('container'=>'products', 'fname'=>'product_id', 'fields'=>array()),
				array('container'=>'prices', 'fname'=>'id',
					'fields'=>array('id', 'name', 'pricepoint_id', 'pricepoint_sequence', 'available_to', 
						'unit_amount', 'unit_discount_amount', 'unit_discount_percentage')),
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['products']) ) {
				$prices = $rc['products'];
			}
        } else {
			//
			// Get only those prices with no pricepoint set
			//
			$strsql = "SELECT ciniki_product_prices.id, "
				. "ciniki_product_prices.product_id, "
				. "ciniki_product_prices.name, "
				. "ciniki_product_prices.pricepoint_id, "
				. "ciniki_product_prices.available_to, "
				. "ciniki_product_prices.unit_amount, "
				. "ciniki_product_prices.unit_discount_amount, "
				. "ciniki_product_prices.unit_discount_percentage "
				. "FROM ciniki_product_prices "
				. "WHERE ciniki_product_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND ciniki_product_prices.product_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $product_ids) . ") "
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
                array('container'=>'products', 'fname'=>'product_id', 'fields'=>array()),
				array('container'=>'prices', 'fname'=>'id',
					'fields'=>array('id', 'name', 'available_to', 
						'unit_amount', 'unit_discount_amount', 'unit_discount_percentage')),
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['products']) ) {
				$prices = $rc['products'];
			}
        }

        //
        // Get any reserved quantities
        //
        $reserved_quantities = array();
        if( isset($ciniki['business']['modules']['ciniki.sapos']) ) {
            $cur_invoice_id = 0;
            if( isset($ciniki['session']['cart']['sapos_id']) && $ciniki['session']['cart']['sapos_id'] > 0 ) {
                $cur_invoice_id = $ciniki['session']['cart']['sapos_id'];
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getReservedQuantities');
            $rc = ciniki_sapos_getReservedQuantities($ciniki, $business_id, 'ciniki.products.product', $product_ids, $cur_invoice_id);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['quantities']) ) {
                $reserved_quantities = $rc['quantities'];
                //$reserved_quantity = $rc['quantities'][$product['id']]['quantity_reserved'];
            }
        }

        //
        // Check each product and see if should be simple or complex pricing
        //
        foreach($products as $pid => $product) {
            $products[$pid]['prices'] = array();

//            print "<pre>" . print_r($object_defs[$product['type_id']], true) . "</pre>";
            // Simple pricing
            if( !isset($object_defs[$product['type_id']]['parent']['prices']['unit_amount']) ) {
                //
                // Check if the price is visible or product is sold online
                //
                if( ($product['webflags']&0x04) == 0 || ($product['webflags']&0x02) > 0 ) {
                    $products[$pid]['prices']['1'] = array(
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
                        'available_to'=>0x01,
                        );

                    // Check if product is to be sold online
                    if( ($product['webflags']&0x02) > 0 ) {
                        $products[$pid]['prices']['1']['cart'] = 'yes';
                    }
                    // Check if product has inventory or unlimited
                    if( ($product['inventory_flags']&0x01) > 0 ) {
                        if( ($product['inventory_flags']&0x02) > 0 ) {
                            // Backordering available for this product
                            $products[$pid]['prices']['1']['limited_units'] = 'no';
                        } else {
                            $products[$pid]['prices']['1']['limited_units'] = 'yes';
                        }
                        $products[$pid]['prices']['1']['units_available'] = $product['inventory_current_num'];
                    }
                }
            }
            
            // Complex pricing
            elseif( isset($ciniki['session']['customer']['pricepoint']['id']) && $ciniki['session']['customer']['pricepoint']['id'] > 0 ) {
                //
                // Find the product price that matches the customers pricepoint_id or the previous pricepoint,
                // which should be the higher price amount
                //
                $prev_price_id = -1;
                $pricepoint_found = 'no';
                if( isset($prices[$pid]['prices']) ) {
                    foreach($prices[$pid]['prices'] as $price_id => $price) {
                        if( $price['pricepoint_sequence'] > $ciniki['session']['customer']['pricepoint']['sequence'] ) {
                            unset($prices[$pid]['prices'][$price_id]);
                            continue;
                        }
                        if( $price['pricepoint_id'] == $ciniki['session']['customer']['pricepoint']['id'] ) {
                            $products[$pid]['prices'] = array($price_id=>$price);
                            $pricepoint_found = 'yes';
                            break;
                        }
                        $prev_pid = $pid;
                    }
                    if( $pricepoint_found == 'no' && $prev_price_id > -1 ) {
                        $products[$pid]['prices'] = array($prev_price_id=>$prices[$pid]['prices'][$prev_price_id]);
                    }
    //				print "<pre>" . print_r($product['prices'], true) . "</pre>";
                }
            } 
            elseif( isset($prices[$pid]['prices']) ) {
                $products[$pid]['prices'] = $prices[$pid]['prices'];
            }


            //
            // Format the list of prices
            //
            if( isset($products[$pid]['prices']) && count($products[$pid]['prices']) > 0 ) {
                foreach($products[$pid]['prices'] as $price_id => $price) {
                    // Check if online registrations enabled
                    if( ($products[$pid]['webflags']&0x02) > 0 && ($price['available_to']&$price_flags) > 0 ) {
                        $products[$pid]['prices'][$price_id]['cart'] = 'yes';
                    } else {
                        $products[$pid]['prices'][$price_id]['cart'] = 'no';
                    }
                    $products[$pid]['prices'][$price_id]['object'] = 'ciniki.products.product';
                    $products[$pid]['prices'][$price_id]['object_id'] = $pid;
                    $products[$pid]['prices'][$price_id]['price_id'] = $price['id'];
                    if( ($products[$pid]['inventory_flags']&0x02) > 0 ) {
                        // Backordering available for this product
                        $products[$pid]['prices'][$price_id]['limited_units'] = 'no';
                    } else {
                        $products[$pid]['prices'][$price_id]['limited_units'] = 'yes';
                    }
                    $products[$pid]['prices'][$price_id]['units_inventory'] = $products[$pid]['inventory_current_num'];
                    $products[$pid]['prices'][$price_id]['units_available'] = $products[$pid]['inventory_current_num'];
                    if( isset($reserved_quantities[$pid]['quantity_reserved']) && $reserved_quantities[$pid]['quantity_reserved'] > 0 ) {
                        $products[$pid]['prices'][$price_id]['units_available'] -= $reserved_quantities[$pid]['quantity_reserved'];
                    }
//                    $products[$pid]['prices'][$price_id]['unit_amount_display'] = numfmt_format_currency($intl_currency_fmt, $price['unit_amount'], $intl_currency);
                }
            }
            elseif( $args['prices'] != 'required' ) {
                $products[$pid]['prices'] = array();
            }

            if( $args['prices'] == 'required' && count($products[$pid]['prices']) == 0 ) {
                unset($products[$pid]);
            }
        }
//        print "<pre>" . print_r($products, true) . "</pre>";
    }

    return array('stat'=>'ok', 'products'=>$products);
}
?>            
