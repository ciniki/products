<?php
//
// Description
// -----------
// This function will generate the gallery page for the website
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_products_web_processRequestSubCategoryProducts(&$ciniki, $settings, $business_id, $category, $subcategory) {

    $strsql = "SELECT ciniki_products.id, "
        . "ciniki_products.name AS title, "
        . "ciniki_products.type_id, "
        . "ciniki_products.permalink, "
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
            . "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_products.start_date < UTC_TIMESTAMP() "
            . "AND (ciniki_products.end_date = '0000-00-00 00:00:00' "
                . "OR ciniki_products.end_date > UTC_TIMESTAMP()"
                . ") "
            . "AND (ciniki_products.webflags&0x01) > 0 "
            . ") "
        . "INNER JOIN ciniki_product_tags AS t2 ON ("
            . "ciniki_products.id = t2.product_id "
            . "AND t2.permalink = '" . ciniki_core_dbQuote($ciniki, $subcategory['permalink']) . "' "
            . "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
    if( isset($category['tag_type']) && $category['tag_type'] > 0 ) {
        $strsql .= "AND t2.tag_type = '" . ciniki_core_dbQuote($ciniki, $category['tag_type']) . "' ";
    } else {
        $strsql .= "AND t2.tag_type > 10 AND t2.tag_type < 30 ";
    }
    $strsql .= ") "
        . "WHERE t1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND t1.tag_type = 10 "
        . "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $category['permalink']) . "' "
        . "ORDER BY ciniki_products.name ASC "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'type_id', 'title', 'permalink', 'image_id', 'description', 'is_details',
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
