<?php
//
// Description
// ===========
// This function will lookup an item that is being added to a shopping cart online.  This function
// has extra checks to make sure the requested item is available to the customer.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_products_sapos_cartItemLookup($ciniki, $tnid, $customer, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.135', 'msg'=>'No product specified.'));
    }

    //
    // Lookup the requested product if specified along with a price_id
    //
    if( $args['object'] == 'ciniki.products.product' && isset($args['price_id']) && $args['price_id'] > 0 ) {
        $strsql = "SELECT ciniki_products.id, "
            . "ciniki_products.parent_id, "
//          . "IF(ciniki_products.code<>'',CONCAT_WS(' - ', ciniki_products.code, ciniki_products.name), ciniki_products.name) AS name, "
            . "ciniki_products.code, "
            . "ciniki_products.name, "
            . "ciniki_products.flags AS product_flags, "
            . "ciniki_product_prices.id AS price_id, "
            . "ciniki_product_prices.name AS price_name, "
            . "ciniki_product_prices.pricepoint_id, "
            . "ciniki_product_prices.available_to, "
            . "ciniki_product_prices.unit_amount, "
            . "ciniki_product_prices.unit_discount_amount, "
            . "ciniki_product_prices.unit_discount_percentage, "
            . "inventory_flags, "
            . "inventory_current_num, "
            . "shipping_flags, "
            . "ciniki_product_prices.taxtype_id, "
            . "ciniki_product_types.object_def "
            . "FROM ciniki_product_prices "
            . "LEFT JOIN ciniki_products ON ("
                . "ciniki_product_prices.product_id = ciniki_products.id "
                . "AND ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_products.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_product_types ON ("
                . "ciniki_products.type_id = ciniki_product_types.id "
                . "AND ciniki_product_types.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_product_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_product_prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'products', 'fname'=>'id',
                'fields'=>array('id', 'price_id', 'parent_id', 'code', 'description'=>'name', 'product_flags',
                    'pricepoint_id', 'available_to',
                    'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
                    'inventory_flags', 'inventory_current_num', 'shipping_flags',
                    'taxtype_id')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['products']) || count($rc['products']) < 1 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.136', 'msg'=>'No product found.'));        
        }
        $product = array_pop($rc['products']);
    }

    elseif( $args['object'] == 'ciniki.products.product' && isset($args['object_id']) && $args['object_id'] > 0 ) {
        $strsql = "SELECT ciniki_products.id, "
            . "ciniki_products.parent_id, "
//          . "IF(ciniki_products.code<>'',CONCAT_WS(' - ', ciniki_products.code, ciniki_products.name), ciniki_products.name) AS name, "
            . "ciniki_products.code, "
            . "ciniki_products.name, "
            . "ciniki_products.flags AS product_flags, "
//            . "ciniki_product_prices.id AS price_id, "
//            . "ciniki_product_prices.name AS price_name, "
            . "0 AS pricepoint_id, "
            . "1 AS available_to, "
            . "ciniki_products.price AS unit_amount, "
            . "ciniki_products.unit_discount_amount, "
            . "ciniki_products.unit_discount_percentage, "
            . "inventory_flags, "
            . "inventory_current_num, "
            . "shipping_flags, "
            . "ciniki_products.taxtype_id, "
            . "ciniki_product_types.object_def "
            . "FROM ciniki_products "
//            . "LEFT JOIN ciniki_products ON ("
//                . "ciniki_product_prices.product_id = ciniki_products.id "
//                . "AND ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//                . "AND ciniki_products.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
//                . ") "
            . "LEFT JOIN ciniki_product_types ON ("
                . "ciniki_products.type_id = ciniki_product_types.id "
                . "AND ciniki_product_types.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_products.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
//            . "WHERE ciniki_product_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//            . "AND ciniki_product_prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'products', 'fname'=>'id',
                'fields'=>array('id', 'parent_id', 'code', 'description'=>'name', 'product_flags',
                    'pricepoint_id', 'available_to',
                    'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
                    'inventory_flags', 'inventory_current_num', 'shipping_flags',
                    'taxtype_id')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['products']) || count($rc['products']) < 1 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.136', 'msg'=>'No product found.'));        
        }
        $product = array_pop($rc['products']);
    }

    if( isset($product) ) {
        //
        // Check the pricepoint_id is valid for this customer, only if specified
        //
        if( $product['pricepoint_id'] > 0 ) {
            if( !isset($customer['pricepoint']['id']) || $customer['pricepoint']['id'] == 0 ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.137', 'msg'=>"I'm sorry, but this product is not available to you."));
            }
            if( $product['pricepoint_id'] != $customer['pricepoint']['id'] ) {
                if( !isset($customer['pricepoint']['sequence']) || $customer['pricepoint']['sequence'] == '' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.138', 'msg'=>"I'm sorry, but this product is not available to you."));
                }
                // Get the sequence for this pricepoint and see if it's lower than customers pricepoint_sequence
                $strsql = "SELECT sequence "
                    . "FROM ciniki_customer_pricepoints "
                    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $product['pricepoint_id']) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'pricepoint');
                if( $rc['stat'] != 'ok' ) { 
                    return $rc;
                }
                if( !isset($rc['pricepoint']) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.139', 'msg'=>"I'm sorry but we seem to be having difficulty updating your shopping cart.  Please call customer support."));
                }
                if( $rc['pricepoint']['sequence'] > $customer['pricepoint']['sequence'] ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.140', 'msg'=>"I'm sorry, but this product is not available to you."));
                }
            }
        }

        //
        // Check the available_to is correct for the specified customer
        //
//        file_put_contents("/tmp/bt", print_r(debug_backtrace(), true));
        if( isset($product['available_to']) && ($product['available_to']&0xF0) > 0 ) {
            if( ($product['available_to']&$customer['price_flags']) == 0 ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.141', 'msg'=>"I'm sorry, but this product is not available to you."));
            }
        }

        // Check if product has inventory or unlimited
        if( ($product['inventory_flags']&0x01) > 0 ) {
            if( ($product['inventory_flags']&0x02) > 0 ) {
                $product['limited_units'] = 'no';
                $product['flags'] = 0x46;   // Inventoried and backorder available
            } else {
                $product['limited_units'] = 'yes';
                $product['flags'] = 0x42;   // Inventoried, update when shipping
            }
            $product['units_available'] = $product['inventory_current_num'];
            if( $product['inventory_current_num'] <= 0 && ($product['flags']&0x46) == 0x46 ) {
                $product['flags'] |= 0x0100;
            }
        } else {
            $product['limited_units'] = 'no';
            $product['units_available'] = 0;
        }

        // Check if product is a promotional item
        if( ($product['product_flags']&0x04) > 0 ) {
            $product['flags'] |= 0x4000;
        }

        //
        // Check if the shipping_flags are set to pickup only
        //
        if( ($product['shipping_flags']&0x03) == 0x02 ) {
            // Turn on shipping required for item
            $product['flags'] &= ~0x40;
        }

        return array('stat'=>'ok', 'item'=>$product);
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.142', 'msg'=>'No product specified.'));        
}
?>
