<?php
//
// Description
// -----------
// This function will return a list of categories for the web product page.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
// <categories>
//      <category name="Portraits" image_id="349" />
//      <category name="Landscape" image_id="418" />
//      ...
// </categories>
//
function ciniki_products_web_categories($ciniki, $settings, $tnid) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    $rsp = array('stat'=>'ok');

    $strsql = "SELECT ciniki_product_tags.tag_name AS name, "
        . "IFNULL(ciniki_product_categories.name, '') AS cat_name, "
        . "IFNULL(ciniki_product_categories.primary_image_id, 0) AS primary_image_id, "
        . "IFNULL(ciniki_product_categories.synopsis, '') AS synopsis, "
//      . "IFNULL(ciniki_product_categories.sequence, 999) AS sort_order, "
        . "ciniki_product_tags.permalink, "
        . "'yes' AS is_details, "
        . "COUNT(ciniki_products.id) AS num_products "
        . "FROM ciniki_product_tags "
        . "LEFT JOIN ciniki_products ON ("
            . "ciniki_product_tags.product_id = ciniki_products.id "
            . "AND ciniki_products.parent_id = 0 "
            . "AND ciniki_products.start_date < UTC_TIMESTAMP() "
            . "AND (ciniki_products.end_date = '0000-00-00 00:00:00' "
                . "OR ciniki_products.end_date > UTC_TIMESTAMP()"
                . ") "
            . "AND (ciniki_products.webflags&0x01) > 0 "
            . "AND ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_product_categories ON ("
            . "ciniki_product_tags.permalink = ciniki_product_categories.category "
            . "AND ciniki_product_categories.subcategory = '' "
            . "AND ciniki_product_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_product_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_product_tags.tag_type = 10 "
        . "AND ciniki_product_tags.tag_name <> '' "
        . "GROUP BY ciniki_product_tags.tag_name "
        . "ORDER BY IFNULL(ciniki_product_categories.sequence, 99), ciniki_product_tags.tag_name "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'categories', 'fname'=>'name', 
            'fields'=>array('name', 'cat_name', 'title'=>'cat_name', 'permalink', 'image_id'=>'primary_image_id', 'num_products', 'synopsis', 'is_details')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) && count($rc['categories']) > 0 ) {
        $rsp['categories'] = $rc['categories'];

        //
        // Load highlight images
        //
        foreach($rsp['categories'] as $cnum => $cat) {
            //
            // Remove empty categories
            //
            if( $cat['num_products'] < 1 ) {
                unset($rsp['categories'][$cnum]);
                continue;
            }

            if( $cat['cat_name'] != '' ) {
                $rsp['categories'][$cnum]['name'] = $cat['cat_name'];
            }
            unset($rsp['categories'][$cnum]['cat_name']);

            //
            // Look for the highlight image, or the most recently added image
            //
            if( $cat['image_id'] == 0 ) {
                $strsql = "SELECT ciniki_products.primary_image_id "
                    . "FROM ciniki_product_tags, ciniki_products, ciniki_images "
                    . "WHERE ciniki_product_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND ciniki_product_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $cat['permalink']) . "' "
                    . "AND ciniki_product_tags.product_id = ciniki_products.id "
                    . "AND ciniki_products.parent_id = 0 "
                    . "AND ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND ciniki_products.start_date < UTC_TIMESTAMP() "
                    . "AND (ciniki_products.end_date = '0000-00-00 00:00:00' "
                        . "OR ciniki_products.end_date > UTC_TIMESTAMP()"
                        . ") "
                    . "AND ciniki_products.primary_image_id = ciniki_images.id "
                    . "AND (ciniki_products.webflags&0x01) > 0 "
                    . "ORDER BY (ciniki_products.webflags&0x10) DESC, "
                    . "ciniki_products.date_added DESC "
                    . "LIMIT 1";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'image');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['image']) ) {
                    $rsp['categories'][$cnum]['image_id'] = $rc['image']['primary_image_id'];
                } else {
                    $rsp['categories'][$cnum]['image_id'] = 0;
                }
            }
        }
    }
    //
    // If there were no categories, get the product list
    //
    else {
        $strsql = "SELECT ciniki_products.id, "
            . "ciniki_products.name AS title, "
            . "ciniki_products.permalink, "
            . "ciniki_products.primary_image_id AS image_id, "
            . "ciniki_products.price, "
            . "ciniki_products.short_description AS description, "
            . "'yes' AS is_details, "
            . "IF(ciniki_images.last_updated > ciniki_products.last_updated, "
                . "UNIX_TIMESTAMP(ciniki_images.last_updated), "
                . "UNIX_TIMESTAMP(ciniki_products.last_updated)) AS last_updated "
            . "FROM ciniki_products "
            . "LEFT JOIN ciniki_images ON ("
                . "ciniki_products.primary_image_id = ciniki_images.id "
                . "AND ciniki_images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_products.parent_id = 0 "
                . "AND ciniki_products.start_date < UTC_TIMESTAMP() "
                . "AND (ciniki_products.end_date = '0000-00-00 00:00:00' OR ciniki_products.end_date > UTC_TIMESTAMP()) "
                . "AND (ciniki_products.webflags&0x01) = 0x01 "
            . "ORDER BY ciniki_products.name ASC "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'products', 'fname'=>'title', 
                'fields'=>array('title', 'permalink', 'image_id', 'description', 
                    'is_details', 'last_updated')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) ) {
            $rsp['products'] = $rc['products'];
        } else {
            $rsp['products'] = array();
        }
    }

    return $rsp;
}
?>
