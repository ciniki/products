<?php
//
// Description
// -----------
// This method will return the categories and suppliers with product counts for each.
//
// Arguments
// ---------
// 
// Returns
// -------
// <categories>
//      <category name="Red Wines" num_products="45"/>
// </categories>
// <suppliers>
//      <supplier id="1" name="Red Wines" num_products="45"/>
// </suppliers>
//
function ciniki_products_productStats($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'25', 'name'=>'Limit'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productStats', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

//  ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
//  $date_format = ciniki_users_dateFormat($ciniki);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    $rsp = array('stat'=>'ok');

    //
    // Get the list of categories and counts
    //
    $strsql = "SELECT tag_name AS name, ciniki_product_tags.permalink, "
// Don't want the name, it's only for the website
//      . "IFNULL(ciniki_product_categories.name, '') AS cat_name, "
        . "COUNT(ciniki_products.id) AS num_products "
        . "FROM ciniki_product_tags "
        . "LEFT JOIN ciniki_products ON ("
            . "ciniki_product_tags.product_id = ciniki_products.id "
            . "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_product_categories ON ("
            . "ciniki_product_tags.permalink = ciniki_product_categories.category "
            . "AND ciniki_product_categories.subcategory = '' "
            . "AND ciniki_product_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_product_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_product_tags.tag_type = 10 "
        . "GROUP BY ciniki_product_tags.tag_name "
        . "ORDER BY ciniki_product_categories.sequence, ciniki_product_tags.tag_name "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'categories', 'fname'=>'name', 'name'=>'category',
            'fields'=>array('name', 'permalink', 'product_count'=>'num_products')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        $rsp['categories'] = array();
    } else {
        $rsp['categories'] = $rc['categories'];
    }

    //
    // If there are no categories, then get the list of products
    //
    if( count($rsp['categories']) == 0 ) {
        //
        // Get the list of products
        //
        $strsql = "SELECT ciniki_products.id, "
            . "ciniki_products.code, "
            . "ciniki_products.name, "
            . "IF((inventory_flags&0x01)=1,inventory_current_num,'') AS inventory_current_num "
            . "FROM ciniki_products "
            . "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY code, name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'products', 'fname'=>'id', 'name'=>'product',
                'fields'=>array('id', 'code', 'name', 'inventory_current_num')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['products']) ) {
            $rsp['products'] = array();
        } else {
            $rsp['products'] = $rc['products'];
        }
        //
        // Get the reserved quantities for the products
        //
        if( isset($args['reserved']) && $args['reserved'] == 'yes' && count($rsp['products']) > 0 ) {
            $product_ids = array();
            foreach($rsp['products'] as $pid => $product) {
                $product_ids[] = $product['product']['id'];
                $rsp['products'][$pid]['product']['rsv'] = 0;
                $rsp['products'][$pid]['product']['bo'] = '';
            }
            $product_ids = array_unique($product_ids);
            if( isset($ciniki['business']['modules']['ciniki.sapos']) ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getReservedQuantities');
                $rc = ciniki_sapos_getReservedQuantities($ciniki, $args['business_id'], 
                    'ciniki.products.product', $product_ids, 0);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $quantities = $rc['quantities'];
                foreach($rsp['products'] as $pid => $product) {
                    if( isset($quantities[$product['product']['id']]) ) {
                        $rsp['products'][$pid]['product']['rsv'] = (float)$quantities[$product['product']['id']]['quantity_reserved'];
                        $bo = $rsp['products'][$pid]['product']['rsv'] - $product['product']['inventory_current_num'];
                        if( $bo > 0 ) {
                            $rsp['products'][$pid]['product']['bo'] = $bo;
                        }
                    }
                }
            }
        }

    } 
    else {
        //
        // Check for any un-categorized products
        //
        $strsql = "SELECT COUNT(ciniki_products.id) AS num_products, ciniki_product_tags.tag_name "
            . "FROM ciniki_products "
            . "LEFT JOIN ciniki_product_tags ON ("
                . "ciniki_products.id = ciniki_product_tags.product_id "
                . "AND ciniki_product_tags.tag_type = 10 "
                . ") "
            . "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ISNULL(tag_name) "
            . "GROUP BY tag_name "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'uncategorized');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['uncategorized']) ) {
            $rsp['categories'][] = array('category'=>array('name'=>'Uncategorized', 'permalink'=>'', 'product_count'=>$rc['uncategorized']['num_products']));
        }
    }

    //
    // Get the list of suppliers and counts
    //
    $strsql = "SELECT ciniki_products.supplier_id, "
        . "IFNULL(ciniki_product_suppliers.name, '') AS name, "
        . "COUNT(ciniki_products.id) AS num_products "
        . "FROM ciniki_products "
        . "LEFT JOIN ciniki_product_suppliers ON (ciniki_products.supplier_id = ciniki_product_suppliers.id "
            . "AND ciniki_product_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    if( isset($args['status']) && $args['status'] != '' ) {
        $strsql .= "AND ciniki_products.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    $strsql .= "GROUP BY ciniki_products.supplier_id ";
    $strsql .= "ORDER BY ciniki_product_suppliers.name "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'suppliers', 'fname'=>'name', 'name'=>'supplier',
            'fields'=>array('id'=>'supplier_id', 'name', 'product_count'=>'num_products')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['suppliers']) ) {
        $rsp['suppliers'] = array();
    } else {
        $rsp['suppliers'] = $rc['suppliers'];
    }

    return $rsp;
}
?>
