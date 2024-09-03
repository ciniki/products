<?php
//
// Description
// -----------
// This function will generate the gallery page for the website
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_products_web_processRequestSubCategoryProducts(&$ciniki, $settings, $tnid, $category, $subcategory) {

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
        . "ciniki_products.code, "
        . "ciniki_products.name AS title, "
        . "ciniki_products.type_id, "
        . "ciniki_products.permalink, "
        . "ciniki_products.sequence, "
        . "ciniki_products.primary_image_id AS image_id, "
        . "ciniki_products.price, "
        . "ciniki_products.unit_discount_amount, "
        . "ciniki_products.unit_discount_percentage, "
        . "ciniki_products.taxtype_id, "
        . "ciniki_products.inventory_flags, "
        . "ciniki_products.inventory_current_num, "
        . "ciniki_products.webflags, "
        . "ciniki_products.short_description AS description, "
        . "'yes' AS is_details "
        . "FROM ciniki_product_tags AS t1 "
        . "INNER JOIN ciniki_products ON ("
            . "t1.product_id = ciniki_products.id "
            . "AND ciniki_products.parent_id = 0 "
            . "AND ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_products.start_date < UTC_TIMESTAMP() "
            . "AND (ciniki_products.end_date = '0000-00-00 00:00:00' "
                . "OR ciniki_products.end_date > UTC_TIMESTAMP()"
                . ") "
            . "AND ciniki_products.status < 60 "
            . "AND (ciniki_products.webflags&$webflags) > 0 "
            . ") "
        . "INNER JOIN ciniki_product_tags AS t2 ON ("
            . "ciniki_products.id = t2.product_id "
            . "AND t2.permalink = '" . ciniki_core_dbQuote($ciniki, $subcategory['permalink']) . "' "
            . "AND t2.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( isset($category['tag_type']) && $category['tag_type'] > 0 ) {
        $strsql .= "AND t2.tag_type = '" . ciniki_core_dbQuote($ciniki, $category['tag_type']) . "' ";
    } else {
        $strsql .= "AND t2.tag_type > 10 AND t2.tag_type < 30 ";
    }
    $strsql .= ") "
        . "WHERE t1.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND t1.tag_type = 10 "
        . "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $category['permalink']) . "' "
        . "ORDER BY ciniki_products.sequence, ciniki_products.name ASC "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'type_id', 'code', 'title', 'permalink', 'sequence', 'image_id', 'description', 'is_details',
                'price', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id', 'inventory_flags', 'inventory_current_num', 'webflags',
            )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['products']) ) {
        $products = $rc['products'];
    } else {
        $products = array();
    }

    return array('stat'=>'ok', 'products'=>$products);
}
?>
