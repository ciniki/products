<?php
//
// Description
// -----------
// This method returns a list of sub categories, or sub-category types if there
// are more than one type.  A list of products not in any sub-category will also be sent.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_products_categoryDetails($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.categoryDetails', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

//  ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
//  $date_format = ciniki_users_dateFormat($ciniki);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    $rsp = array('stat'=>'ok', 'details'=>array());

    //
    // Get the details for the category
    //
    $strsql = "SELECT ciniki_product_tags.tag_name, "
        . "IFNULL(ciniki_product_categories.name, '') AS name "
        . "FROM ciniki_product_tags "
        . "LEFT JOIN ciniki_product_categories ON ("
            . "ciniki_product_tags.permalink = ciniki_product_categories.category "
            . "AND ciniki_product_categories.subcategory = '' "
            . "AND ciniki_product_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_product_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_product_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
        . "AND ciniki_product_tags.tag_type = 10 "
        . "LIMIT 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'tag');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tag']) ) {
//      if( $rc['tag']['name'] != '' ) {
//          $rsp['details']['category_title'] = $rc['tag']['name'];
//      } else {
            $rsp['details']['category_title'] = $rc['tag']['tag_name'];
//      }
    }

    //
    // Check for subcategories
    //
    if( isset($args['category']) && $args['category'] != '' ) {
        $strsql = "SELECT t2.tag_type, t2.tag_name AS name, "
            . "t2.permalink, "
            . "IFNULL(ciniki_product_categories.name, '') AS cat_name, "
            . "ciniki_products.type_id, "
            . "COUNT(ciniki_products.id) AS num_products "
            . "FROM ciniki_product_tags AS t1 "
            . "LEFT JOIN ciniki_product_tags AS t2 ON ("
                . "t1.product_id = t2.product_id "
                . "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND t2.tag_type > 10 "
                . "AND t2.tag_type < 30 "
                . ") "
            . "LEFT JOIN ciniki_product_categories ON ("
                . "t1.permalink = ciniki_product_categories.category "
                . "AND t2.permalink = ciniki_product_categories.subcategory "
                . "AND ciniki_product_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_products ON ("
                . "t2.product_id = ciniki_products.id "
                . "AND ciniki_products.parent_id = 0 "
                . "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE t1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
            . "AND t1.tag_type = 10 "
            . "GROUP BY type_id, t2.tag_type, t2.tag_name "
            . "ORDER BY type_id, t2.tag_type, t2.tag_name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'product_types', 'fname'=>'type_id', 'name'=>'product_type',
                'fields'=>array('id'=>'type_id')),
            array('container'=>'types', 'fname'=>'tag_type', 'name'=>'type',
                'fields'=>array('tag_type', 'name')),
            array('container'=>'categories', 'fname'=>'name', 'name'=>'category',
                'fields'=>array('name', 'permalink', 'cat_name', 'num_products')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['product_types']) && count($rc['product_types']) > 0 ) {
            $product_types = $rc['product_types'];
            //
            // Load the product_type_definitions
            //
            $strsql = "SELECT id, name_s, name_p, object_def "
                . "FROM ciniki_product_types "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "ORDER BY id "
                . "";
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
                array('container'=>'types', 'fname'=>'id',
                    'fields'=>array('id', 'name_s', 'name_p', 'object_def')),
                ));
            $types = isset($rc['types'])?$rc['types']:array();
            $object_defs = array();
            // Prep the object defs
            foreach($types as $type_id => $type) {
                $object_defs[$type_id] = unserialize($type['object_def']);
            }
        
            //
            // Go through all the product types, and build a type array
            // based on the sub-category names
            //
            $types = array();
            foreach($product_types as $ptid => $ptype) {
                $ptype = $ptype['product_type'];
                // Check of the product type exists
                if( isset($object_defs[$ptype['id']]) ) {
                    $odef = $object_defs[$ptype['id']]['parent'];
                    foreach($ptype['types'] as $tid => $type) {
                        $type = $type['type'];
                        if( isset($odef['subcategories-' . $type['tag_type']]['pname']) ) {
                            $sub_cat_name = $odef['subcategories-' . $type['tag_type']]['pname'];
                        } else {
                            $sub_cat_name = 'Sub-Categories';
                        }
                        if( !isset($types[$sub_cat_name]) ) {
                            $types[$sub_cat_name] = array('name'=>$sub_cat_name, 'categories'=>$type['categories']);
                        } else {
                            foreach($type['categories'] as $new_id => $new_cat) {
                                $new_cat = $new_cat['category'];
                                // Check for existing category name
                                $found = 'no';
                                foreach($types[$sub_cat_name]['categories'] as $old_id => $old_cat) {
                                    $old_cat = $old_cat['category'];
                                    if( $old_cat['name'] == $new_cat['name'] ) {
                                        $types[$sub_cat_name]['categories'][$old_id]['category']['num_products'] += $new_cat['num_products'];
                                        $found = 'yes';
                                        break;
                                    }
                                }
                                if( $found == 'no' ) {
                                    $types[$sub_cat_name]['categories'][] = $type['categories'][$new_id];
                                }
                            }
                        }
                    }
                }
            }

            //
            // Check if there's more than one sub-category type
            //
            $rsp['subcategorytypes'] = array();
            foreach($types as $tname => $type) {
                $rsp['subcategorytypes'][] = array('type'=>$type);
            }
        } else {
            $rsp['subcategories'] = array();
        }
    }

    //
    // Check for any products that are not in a sub category
    //
    $strsql = "SELECT ciniki_products.id, "
        . "ciniki_products.code, "
        . "ciniki_products.name "
        . "FROM ciniki_product_tags AS t1 "
        . "LEFT JOIN ciniki_products ON ("
            . "t1.product_id = ciniki_products.id "
            . "AND ciniki_products.parent_id = 0 "
            . "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_product_tags AS t2 ON ("
            . "ciniki_products.id = t2.product_id "
            . "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND t2.tag_type > 10 "
            . "AND t2.tag_type < 30 "
            . ") "
        . "WHERE t1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND t1.tag_type = 10 "
        . "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
        . "AND ISNULL(t2.tag_name) "
        . "ORDER BY ciniki_products.name ASC "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'products', 'fname'=>'id', 'name'=>'product',
            'fields'=>array('id', 'code', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['products']) ) {
        $rsp['products'] = $rc['products'];
    } else {
        $rsp['products'] = array();
    }

    return $rsp;
}
?>
