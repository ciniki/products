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
function ciniki_products_wng_categoryProcess(&$ciniki, $tnid, &$request, $section) {

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
        . "products.long_description AS description "
//        . "products.price, "
//        . "prices.id AS price_id, "
//        . "prices.name AS price_name, "
//        . "prices.available_to, "
//        . "prices.unit_amount, "
//        . "prices.webflags "
        . "FROM ciniki_product_tags AS categories "
        . "INNER JOIN ciniki_products AS products ON ("
            . "categories.product_id = products.id "
            . "AND (products.webflags&0x01) = 0x01 " //visible
            . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
//        . "LEFT JOIN ciniki_product_prices AS prices ON ("
//            . "products.id = prices.product_id "
//            . "AND (prices.webflags&0x01) = 0 " // Visible
//            . "AND prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//            . ") "
        . "WHERE categories.tag_name = '" . ciniki_core_dbQuote($ciniki, $s['category']) . "' "
        . "AND categories.tag_type = 10 "   // Category
        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'title'=>'name', 'name', 'permalink', 'code', 'flags', 'status', 'webflags', 
                'image-id'=>'primary_image_id', 'synopsis', 'description',
                ),
            ),
//        array('container'=>'prices', 'fname'=>'price_id', 
//            'fields'=>array('id'=>'price_id', 'name'=>'price_name', 'code', 'flags', 'status', 'webflags', 'unit_amount'=>'price'),
//            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.183', 'msg'=>'Unable to load products', 'err'=>$rc['err']));
    }
    $products = isset($rc['products']) ? $rc['products'] : array();

    foreach($products as $pid => $product) {
        $products[$pid]['url'] = $request['page']['path'] . '/' . $product['permalink'];
        $products[$pid]['link-text'] = 'Purchase';
        if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
            && $request['uri_split'][($request['cur_uri_pos']+1)] == $product['permalink']
            ) {
            $section['settings']['product-id'] = $product['id'];
            $request['cur_uri_pos']++;
            ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'wng', 'productProcess');
            $rc = ciniki_products_wng_productProcess($ciniki, $tnid, $request, $section);
            if( isset($rc['blocks']) ) {
                return array('stat'=>'ok', 'blocks'=>$rc['blocks'], 'stop'=>'yes', 'clear'=>'yes');
            }
        }
    }

    $blocks[] = [
        'type' => 'flexcards',
        'title' => isset($s['title']) ? $s['title'] : '',
        'items' => $products,
        ];

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
