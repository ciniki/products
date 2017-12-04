<?php
//
// Description
// -----------
// This method returns the details about a category.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the relationship from.
// relationship_id:     The ID of the relationship to get.
// 
// Returns
// -------
//
function ciniki_products_categoryGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'category'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
        'subcategory'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Category'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['tnid'], 'ciniki.products.categoryGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Build the query to get the details about a category
    //
    $strsql = "SELECT id, name, subname, sequence, tag_type, display, subcategorydisplay, productdisplay, primary_image_id, "
        . "synopsis, description "
        . "FROM ciniki_product_categories "
        . "WHERE category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
        . "AND subcategory = '" . ciniki_core_dbQuote($ciniki, $args['subcategory']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'category');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.49', 'msg'=>'Unable to find category', 'err'=>$rc['err']));
    }
    if( !isset($rc['category']) ) {
        //
        // Setup the default entry
        //
        $category = array('id'=>0,
            'name'=>'',
            'subname'=>'',
            'sequence'=>'',
            'tag_type'=>'0',
            'display'=>'',
            'subcategorydisplay'=>'',
            'productdisplay'=>'',
            'primary_image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
            );
    } else {
        $category = $rc['category'];
    }

    //
    // Load the product type definitions
    //
    $strsql = "SELECT id, name_s, name_p, object_def "
        . "FROM ciniki_product_types "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
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
    // Get the list of tag types available
    //
    $strsql = "SELECT t2.tag_type, t2.tag_name AS name, "
        . "t2.permalink, "
        . "IFNULL(ciniki_product_categories.name, '') AS cat_name, "
        . "IFNULL(ciniki_product_categories.primary_image_id, 0) AS image_id, "
        . "IFNULL(ciniki_product_categories.synopsis, '') AS synopsis, "
        . "ciniki_products.type_id, "
        . "COUNT(ciniki_products.id) AS num_products "
        . "FROM ciniki_product_tags AS t1 "
        . "LEFT JOIN ciniki_product_tags AS t2 ON ("
            . "t1.product_id = t2.product_id "
            . "AND t2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND t2.tag_type > 10 AND t2.tag_type < 30 "
        . ") "
        . "LEFT JOIN ciniki_product_categories ON ("
            . "t1.permalink = ciniki_product_categories.category "
            . "AND t2.permalink = ciniki_product_categories.subcategory "
            . "AND ciniki_product_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_products ON ("
            . "t2.product_id = ciniki_products.id "
            . "AND ciniki_products.parent_id = 0 "
            . "AND (ciniki_products.webflags&0x01) > 0 "
            . "AND ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE t1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
        . "AND t1.tag_type = 10 "
        . "GROUP BY type_id, t2.tag_type, t2.tag_name "
        . "ORDER BY type_id, t2.tag_type, IFNULL(ciniki_product_categories.sequence, 999), t2.tag_name "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'product_types', 'fname'=>'type_id', 'name'=>'product_type',
            'fields'=>array('id'=>'type_id')),
        array('container'=>'types', 'fname'=>'tag_type', 'name'=>'type',
            'fields'=>array('tag_type', 'name')),
        array('container'=>'categories', 'fname'=>'name', 'name'=>'category',
            'fields'=>array('name', 'cat_name', 'permalink', 'image_id', 'synopsis', 'num_products')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tag_types = array();
    if( isset($rc['product_types']) ) {
        $product_types = $rc['product_types'];
        foreach($product_types as $ptid => $ptype) {
            // Check of the product type exists
            if( isset($object_defs[$ptype['id']]) ) {
                $odef = $object_defs[$ptype['id']]['parent'];
                foreach($ptype['types'] as $tid => $type) {
                    if( isset($odef['subcategories-' . $type['tag_type']]['pname']) ) {
                        $sub_cat_name = $odef['subcategories-' . $type['tag_type']]['pname'];
                    } else {
                        $sub_cat_name = 'Sub-Categories';
                    }
                    $tag_types[$tid] = $sub_cat_name;
                }
            }
        }
    }
    

    return array('stat'=>'ok', 'category'=>$category, 'tag_types'=>$tag_types);
}
?>
