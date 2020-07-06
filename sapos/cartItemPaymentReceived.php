<?php
//
// Description
// ===========
// This function removes the item from inventory upon payment
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_products_sapos_cartItemPaymentReceived($ciniki, $tnid, $customer, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.47', 'msg'=>'No product specified.'));
    }

    if( !isset($args['price_id']) || $args['price_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.48', 'msg'=>'No product specified.'));
    }
    if( !isset($args['invoice_id']) || $args['invoice_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.49', 'msg'=>'No product specified.'));
    }
    if( !isset($args['quantity']) || $args['quantity'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.50', 'msg'=>'No quantity specified.'));
    }

    if( $args['object'] == 'ciniki.products.product' ) {
        //
        // Get the product details
        //
        $strsql = "SELECT ciniki_products.id, "
            . "ciniki_products.parent_id, "
            . "ciniki_products.code, "
            . "ciniki_products.name, "
            . "ciniki_products.flags AS product_flags, "
            . "ciniki_products.price AS unit_amount, "
            . "ciniki_products.unit_discount_amount, "
            . "ciniki_products.unit_discount_percentage, "
            . "inventory_flags, "
            . "inventory_current_num, "
            . "shipping_flags, "
            . "ciniki_product_types.object_def "
            . "FROM ciniki_products "
            . "LEFT JOIN ciniki_product_types ON ("
                . "ciniki_products.type_id = ciniki_product_types.id "
                . "AND ciniki_product_types.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_products.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'products', 'fname'=>'id',
                'fields'=>array('id', 'parent_id', 'code', 'product_flags',
                    'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
                    'inventory_flags', 'inventory_current_num', 'shipping_flags',
                    'object_def')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['products']) || count($rc['products']) < 1 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.136', 'msg'=>'No product found.'));        
        }
        $product = array_pop($rc['products']);

        //
        // Check if the shipping_flags are enabled and are set to pickup only
        //
        if( ($product['inventory_flags']&0x01) > 0 ) {
            //
            // Remove items from inventory
            //
            $update_args = array('inventory_current_num' => ($product['inventory_current_num'] - $args['quantity']));

            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.products.product', $product['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.178', 'msg'=>'Unable to update the product'));
            }
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
