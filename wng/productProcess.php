<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_products_wng_productProcess(&$ciniki, $tnid, &$request, $section) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'contentProcess');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'urlProcess');

    $blocks = array();
    $s = isset($section['settings']) ? $section['settings'] : array();

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    //
    // Load the product pricing
    //
    $strsql = "SELECT products.id, "
        . "products.name, "
        . "products.permalink, "
        . "products.code, "
        . "products.flags, "
        . "products.status, "
        . "products.webflags, "
        . "products.primary_image_id, "
        . "products.short_description AS synopsis, "
        . "products.long_description AS description, "
        . "products.price, "
        . "prices.id AS price_id, "
        . "prices.name AS price_name, "
        . "prices.available_to, "
        . "prices.unit_amount, "
        . "prices.webflags AS price_webflags "
        . "FROM ciniki_products AS products "
        . "LEFT JOIN ciniki_product_prices AS prices ON ("
            . "products.id = prices.product_id "
            . "AND (prices.webflags&0x01) = 0 " // Visible
            . "AND prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    if( isset($s['product-id']) ) {
        $strsql .= "WHERE products.id = '" . ciniki_core_dbQuote($ciniki, $s['product-id']) . "' ";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.185', 'msg'=>'No product specified'));
    }
    $strsql .= "AND (products.webflags&0x01) = 0x01 " //visible
            . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'title'=>'name', 'name', 'permalink', 'code', 'flags', 'status', 'webflags', 'unit_amount'=>'price',
                'image-id'=>'primary_image_id', 'synopsis', 'description',
                ),
            ),
        array('container'=>'prices', 'fname'=>'price_id', 
            'fields'=>array('id'=>'price_id', 'name'=>'price_name', 'available_to', 'code', 'flags', 'status', 'webflags'=>'price_webflags', 'unit_amount'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.183', 'msg'=>'Unable to load products', 'err'=>$rc['err']));
    }
    if( !isset($rc['products'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.186', 'msg'=>'No product found'));
    }
    $product = $rc['products'][0];

    $blocks[] = [
        'type' => 'contentphoto',
        'title' => $product['name'],
        'content' => $product['description'] != '' ? $product['description'] : $product['synopsis'],
        'image-size' => 'medium',
        'image-id' => $product['image-id'],
        ];

    if( isset($product['prices']) ) {
        $prices = [];
        foreach($product['prices'] as $pid => $price ) {
            $price['object'] = 'ciniki.products.product';
            $price['object_id'] = $product['id'];
            $price['price_id'] = $price['id'];
            $price['limited_units'] = 'no';
            $price['no-cart-msg'] = '&nbsp;';
            // Remove members only pricing that should not appear on website
            if( ($price['available_to']&0x21) == 0x20  // Available to members, NOT public
                && ($price['webflags']&0x20) == 0    // Show members price disabled
                && (!isset($request['session']['customer']['member_status']) ||  $request['session']['customer']['member_status'] != 10)
                ) {
                continue;
            }
            if( ($product['webflags']&0x02) == 0x02 ) { // Sell Online
                if( ($price['available_to']&0x01) == 0x01 ) { // Available to public
                    $price['cart'] = 'yes';
                } elseif( ($price['available_to']&0x22) == 0x20 
                    && isset($request['session']['customer']['member_status'])
                    && $request['session']['customer']['member_status'] == 10 
                    ) {
                    $price['cart'] = 'yes';
                }
            }
            $prices[] = $price;
        }
        $blocks[] = array(
            'type' => 'pricelist', 
            'title' => 'Purchase',
            'prices' => $prices,
            );
    }
    



    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
