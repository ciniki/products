<?php
//
// Description
// -----------
// This function will search the product database for items
// that can be added to the cart of the customer.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get images for.
// limit:           The maximum number of images to return.
//
// Returns
// -------
//
function ciniki_products_web_searchProducts($ciniki, $settings, $tnid, $args) {

    //
    // Get tenant/user settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $ciniki['request']['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    $args['search_str'] = preg_replace('/ /', '\1%\2', trim($args['search_str']));

    //
    // Check if any prices are attached to the product
    //
    if( isset($ciniki['session']['customer']['price_flags']) ) {
        $price_flags = $ciniki['session']['customer']['price_flags'];
    } else {
        $price_flags = 0x01;
    }

    if( isset($ciniki['session']['customer']['pricepoint']['id'])
        && $ciniki['session']['customer']['pricepoint']['id'] > 0
        ) {
        $pricepoint = 'yes';
    } else {
        $pricepoint = 'no';
    }

    $webflags = 0x01;
    if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
        $webflags |= 0x0100;
    }
    if( isset($ciniki['session']['customer']['member_status']) && $ciniki['session']['customer']['member_status'] == 10 ) {
        $webflags |= 0x0200;
    }
    if( isset($ciniki['session']['customer']['dealer_status']) && $ciniki['session']['customer']['dealer_status'] == 10 ) {
        $webflags |= 0x0400;
    }
    if( isset($ciniki['session']['customer']['distributor_status']) && $ciniki['session']['customer']['distributor_status'] == 10 ) {
        $webflags |= 0x0800;
    }

    $strsql = "SELECT ciniki_products.id, "
//      . "IF(ciniki_products.code<>'',CONCAT_WS(' - ', ciniki_products.code, ciniki_products.name), ciniki_products.name) AS name, "
        . "ciniki_products.code, "
        . "ciniki_products.name, "
        . "ciniki_products.inventory_flags, "
        . "ciniki_products.inventory_current_num, "
        . "ciniki_product_prices.id AS price_id, "
        . "ciniki_product_prices.name AS price_name, "
        . "ciniki_product_prices.pricepoint_id, ";
    if( $pricepoint == 'yes' ) {
        $strsql .= "ciniki_customer_pricepoints.sequence AS pricepoint_sequence, "
        . "ciniki_customer_pricepoints.flags AS pricepoint_flags, ";
    }
    $strsql .= "ciniki_product_prices.available_to, "
        . "ciniki_product_prices.unit_amount, "
        . "ciniki_product_prices.unit_discount_amount, "
        . "ciniki_product_prices.unit_discount_percentage "
        . "FROM ciniki_products "
        . "LEFT JOIN ciniki_product_prices ON ("
            . "ciniki_products.id = ciniki_product_prices.product_id "
            . "AND (ciniki_product_prices.webflags&0x01) = 0 "
            // Find only prices available to customer OR visible on website
            . "AND ((ciniki_product_prices.available_to&$price_flags) > 0 "
                // Use available to with webflags to make sure the price is available to that group
                // then make sure one is turned on
                . "OR (ciniki_product_prices.webflags&ciniki_product_prices.available_to&0xF0) > 0 "
                . ") "
            . "AND ciniki_product_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    if( $pricepoint == 'yes' ) {
        $strsql .= "LEFT JOIN ciniki_customer_pricepoints ON ("
            . "ciniki_product_prices.pricepoint_id = ciniki_customer_pricepoints.id "
            . "AND ciniki_customer_pricepoints.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    }
    $strsql .= "WHERE ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_products.start_date < UTC_TIMESTAMP() "
        . "AND (ciniki_products.end_date = '0000-00-00 00:00:00' OR ciniki_products.end_date > UTC_TIMESTAMP()) "
        // Make sure product is visible and for sale online
        . "AND (ciniki_products.webflags&$webflags) > 0 "
        . "AND (ciniki_products.webflags&0x02) = 2 "
        . "AND (ciniki_products.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['search_str']) . "%' "
            . "OR ciniki_products.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['search_str']) . "%' "
            . "OR ciniki_products.code LIKE '" . ciniki_core_dbQuote($ciniki, $args['search_str']) . "%' "
            . ") ";
    // Check if pricepoints should be restricted to only those available to the customer, or
    // if we should get all and decide on best one for the customer.
    if( isset($ciniki['session']['customer']['pricepoint']['flags']) 
        && ($ciniki['session']['customer']['pricepoint']['flags']&0x01) == 0 ) {
        $strsql .= "AND (ciniki_product_prices.pricepoint_id = '" . ciniki_core_dbQuote($ciniki, $ciniki    ['session']['customer']['pricepoint']['id']) . "' "
            . " OR ciniki_product_prices.pricepoint_id = 0 "
            . ") ";
    }
    if( $pricepoint == 'yes' ) {
        $strsql .= "ORDER BY ciniki_products.name, ciniki_customer_pricepoints.sequence ASC, ciniki_product_prices.name "; 
    } else {
        $strsql .= "ORDER BY ciniki_products.name, ciniki_product_prices.name ";
    }

    if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 && preg_match("/^[0-9]+$/",$args['limit']) ) {
        $strsql .= "LIMIT " . intval($args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 15";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'products', 'fname'=>'id', 'name'=>'product',
            'fields'=>array('id', 'code', 'name', 'inventory_flags', 'inventory_available'=>'inventory_current_num')),
        array('container'=>'prices', 'fname'=>'price_id', 'name'=>'price',
            'fields'=>array('id'=>'price_id', 'name'=>'price_name', 'pricepoint_id', 'pricepoint_sequence', 'available_to',
                'unit_amount', 'unit_discount_amount', 'unit_discount_percentage')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( !isset($rc['products']) ) {
        return array('stat'=>'ok', 'products'=>array());
    }

    //
    // Determine the price for the product the customer should see
    //
    $products = $rc['products'];
    $product_ids = array();
    foreach($products as $pid => $product) {
        $product_ids[] = $product['product']['id'];

        //
        // Decide on the best price for the customer to see
        //
        if( $pricepoint == 'yes' ) {
            $prev_prid = -1;
            $pricepoint_found = 'no';
            foreach($product['product']['prices'] as $prid => $price) {
                $price = $price['price'];
                if( $price['pricepoint_sequence'] > $ciniki['session']['customer']['pricepoint']['sequence'] ) {
                    unset($product['product']['prices'][$prid]);
                    continue;
                }
                if( $price['pricepoint_id'] == $ciniki['session']['customer']['pricepoint']['id'] ) {
        //          $product['prices'] = array($prid=>$price);
                    $products[$pid]['product']['unit_amount'] = $price['unit_amount'];
                    $products[$pid]['product']['price_id'] = $price['id'];
                    // Check if product is for sale and available to customer
                    if( ($price['available_to']&$price_flags) > 0 ) {
                        $products[$pid]['product']['cart'] = 'yes';
                    } else {
                        $products[$pid]['product']['cart'] = 'no';
                    }
                    $pricepoint_found = 'yes';
                    break;
                }
                $prev_prid = $prid;
            }
            if( $pricepoint_found == 'no' && $prev_prid > -1 ) {
                $products[$pid]['product']['unit_amount'] = $product['product']['prices'][$prev_prid]['price']['unit_amount'];
                $products[$pid]['product']['price_id'] = $product['product']['prices'][$prev_prid]['price']['id'];
            }
        } else {
            $product[$pid]['product']['unit_amount'] = 0;
            if( isset($product['product']['prices']) ) {
                foreach($product['product']['prices'] as $pic => $price) {
                    // FIXME: Apply discounts
                    if( $price['price']['unit_amount'] < $product['product']['price'] ) {
                        $products[$pid]['product']['unit_amount'] = $price['price']['unit_amount'];
                        $products[$pid]['product']['price_id'] = $price['price']['id'];
                    }
                }
            }
        }
        unset($products[$pid]['product']['prices']);
    }

    //
    // Get the reserved quantities for each product
    //
    if( isset($ciniki['tenant']['modules']['ciniki.sapos']) ) {
        $cur_invoice_id = 0;
        if( isset($ciniki['session']['cart']['sapos_id']) && $ciniki['session']['cart']['sapos_id'] > 0 ) {
            $cur_invoice_id = $ciniki['session']['cart']['sapos_id'];
        }
        $product_ids = array_unique($product_ids);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getReservedQuantities');
        $rc = ciniki_sapos_getReservedQuantities($ciniki, $tnid, 
            'ciniki.products.product', $product_ids, $cur_invoice_id);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $quantities = $rc['quantities'];
        foreach($products as $pid => $product) {
            if( isset($quantities[$product['product']['id']]) ) {
                $products[$pid]['product']['inventory_available'] -= $quantities[$product['product']['id']]['quantity_reserved'];
                if( $products[$pid]['product']['inventory_available'] < 0 ) {
                    $products[$pid]['product']['inventory_available'] = 0;
                }
            }
            // Format price
            if( isset($products[$pid]['product']['unit_amount']) ) {
                $products[$pid]['product']['price'] = numfmt_format_currency($intl_currency_fmt, $products[$pid]['product']['unit_amount'], $intl_currency);
            }
        }
    }


    return array('stat'=>'ok', 'products'=>$products);
}
?>
