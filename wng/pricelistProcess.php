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
function ciniki_products_wng_pricelistProcess(&$ciniki, $tnid, &$request, $section) {

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
    // Load the event details
    //
    $product_ids = [];
    for($i = 1; $i < 50; $i++) {
        if( isset($s["product-id-{$i}"]) && $s["product-id-{$i}"] != '' && $s["product-id-{$i}"] > 0 ) {
            $product_ids[] = $s["product-id-{$i}"];
        }
    }
    
    if( count($product_ids) < 1 ) {
        return array('stat'=>'ok');
    }

    //
    // Load the product pricing
    //
    $strsql = "SELECT products.id, "
        . "products.name, "
        . "products.code, "
        . "products.price "
        . "FROM ciniki_products AS products "
        . "WHERE id IN (" . ciniki_core_dbQuoteIDs($ciniki, $product_ids) . ") "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'prices', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'code', 'unit_amount'=>'price')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.183', 'msg'=>'Unable to load prices', 'err'=>$rc['err']));
    }
    $products = isset($rc['prices']) ? $rc['prices'] : array();
    foreach($products as $pid => $price) {
    }
    $prices = [];
    for($i = 1; $i < 50; $i++) {
        if( isset($s["product-id-{$i}"]) && $s["product-id-{$i}"] != '' && $s["product-id-{$i}"] > 0 
            && isset($products[$s["product-id-{$i}"]])
            ) {
            $price = $products[$s["product-id-{$i}"]];
            $price['object'] = 'ciniki.products.product';
            $price['object_id'] = $price['id'];
            $price['price_id'] = 0;
            $price['limited_units'] = 'no';
            $price['cart'] = 'yes';
            $prices[] = $price;
        }
    }

    $blocks[] = array(
        'type' => 'pricelist', 
        'title' => isset($s['title']) ? $s['title'] : '',
        'prices' => $prices,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
