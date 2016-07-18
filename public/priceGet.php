<?php
//
// Description
// ===========
// This method will return all the information about an product price.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the product is attached to.
// price_id:        The ID of the price to get the details for.
// 
// Returns
// -------
//
function ciniki_products_priceGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'price_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
        'customer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        'invoice'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.priceGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    $strsql = "SELECT ciniki_product_prices.id, "
        . "ciniki_product_prices.product_id, "
        . "ciniki_product_prices.name, "
        . "ciniki_product_prices.pricepoint_id, "
        . "IFNULL(ciniki_customer_pricepoints.name, '') AS pricepoint_id_text, "
        . "ciniki_product_prices.available_to, "
        . "ciniki_product_prices.min_quantity, "
        . "ciniki_product_prices.unit_amount, "
        . "ciniki_product_prices.unit_discount_amount, "
        . "ciniki_product_prices.unit_discount_percentage, "
        . "ciniki_product_prices.taxtype_id, "
        . "ciniki_product_prices.start_date, "
        . "ciniki_product_prices.end_date, "
        . "ciniki_product_prices.webflags "
        . "FROM ciniki_product_prices "
        . "LEFT JOIN ciniki_customer_pricepoints ON ("
            . "ciniki_product_prices.pricepoint_id = ciniki_customer_pricepoints.id "
            . "AND ciniki_customer_pricepoints.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_product_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_product_prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'prices', 'fname'=>'id', 'name'=>'price',
            'fields'=>array('id', 'product_id', 'name', 'pricepoint_id', 'pricepoint_id_text', 'available_to',
                'min_quantity', 'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
                'taxtype_id', 'start_date', 'end_date', 'webflags'),
            'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'end_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['prices']) || !isset($rc['prices'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1805', 'msg'=>'Unable to find price'));
    }
    $price = $rc['prices'][0]['price'];

    $price['unit_discount_percentage'] = (float)$price['unit_discount_percentage'];
    $price['unit_amount'] = numfmt_format_currency($intl_currency_fmt,
        $price['unit_amount'], $intl_currency);
    $price['unit_discount_amount'] = numfmt_format_currency($intl_currency_fmt,
        $price['unit_discount_amount'], $intl_currency);

    return array('stat'=>'ok', 'price'=>$price);
}
?>
