<?php
//
// Description
// -----------
// This function will add a new product to the products production module.
//
// Info
// ----
// Status:          defined
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_products_productAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'parent_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Parent'), 
        'name'=>array('required'=>'yes', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'code'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'default'=>'', 'name'=>'Code'),
        'type_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        'type_name_s'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
//      'type'=>array('required'=>'no', 'default'=>'1', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Type'),
        'category'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Category'),
        'permalink'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Permalink'),
        'sequence'=>array('required'=>'no', 'default'=>'1', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Sequence'), 
        'source'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Source'), 
        'flags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Flags'), 
        'status'=>array('required'=>'no', 'default'=>'10', 'blank'=>'yes', 'name'=>'Status'), 
        'barcode'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Barcode'), 
        'supplier_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Supplier'), 
        'supplier_product_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Supplier Product'), 
        'supplier_item_number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Supplier Item Number'), 
        'supplier_minimum_order'=>array('required'=>'no', 'default'=>'1', 'blank'=>'yes', 'name'=>'Supplier Minimum Order'), 
        'supplier_order_multiple'=>array('required'=>'no', 'default'=>'1', 'blank'=>'yes', 'name'=>'Supplier Order Multiple'), 
        'manufacture_min_time'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Manufacture Minimum Time'), 
        'manufacture_max_time'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Manufacture Maximum Time'), 
        'inventory_flags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Inventory Flags'), 
        'inventory_current_num'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Current Inventory Number'), 
        'inventory_reorder_num'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Current Reorder Level'), 
        'inventory_reorder_quantity'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Current Reorder Quantity'), 
        'price'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Price'), 
        'unit_discount_amount'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Discount Amount'),
        'unit_discount_percentage'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Discount Percentage'),
        'taxtype_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Tax'),
        'cost'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Cost'),
        'msrp'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'type'=>'currency', 'name'=>'MSRP'),
        'sell_unit'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Sell Unit'),
        'shipping_flags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Shipping Options'), 
        'shipping_weight'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Shipping Weight'), 
        'shipping_weight_units'=>array('required'=>'no', 'default'=>'10', 'blank'=>'yes', 'name'=>'Shipping Weight Units'), 
        'shipping_length'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Shipping Length'), 
        'shipping_width'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Shipping Width'), 
        'shipping_height'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Shipping Height'), 
        'shipping_size_units'=>array('required'=>'no', 'default'=>'10', 'blank'=>'yes', 'name'=>'Shipping Size Units'), 
        'primary_image_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Image'),
        'short_description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Brief Description'),
        'long_description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Long Description'),
        'start_date'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Start Date'),
        'end_date'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'End Date'),
        'webflags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Webflags'),
        // Details
        'detail01'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Detail 01'), 
        'detail02'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Detail 02'), 
        'detail03'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Detail 03'), 
        'detail04'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Detail 04'), 
        'detail05'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Detail 05'), 
        'detail06'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Detail 06'), 
        'detail07'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Detail 07'), 
        'detail08'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Detail 08'), 
        'detail09'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Detail 09'), 
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'), 
        'subcategories-11'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Sub-Categories'), 
        'subcategories-12'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Sub-Categories'), 
        'subcategories-13'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Sub-Categories'), 
        'subcategories-14'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Sub-Categories'), 
        'subcategories-15'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Sub-Categories'), 
        'tags'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Tags'), 

//        'wine_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Wine Type'), 
//        'kit_length'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Rack Length'), 
//        'winekit_oak'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Oak'), 
//        'winekit_body'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Body'), 
//        'winekit_sweetness'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sweetness'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['tnid'], 'ciniki.products.productAdd', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // If no type specified, return an error
    //
    if( (!isset($args['type_id']) || $args['type_id'] == '' || $args['type_id'] == 0) 
        && (!isset($args['type_name_s']) || $args['type_name_s'] == '' ) 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.84', 'msg'=>'You must specify a product type'));
    }

    //
    // Lookup the type_id if a type_name is specified
    //
    if( (!isset($args['type_id']) || $args['type_id'] == '' || $args['type_id'] == 0)  
        && isset($args['type_name_s']) ) {
        $strsql = "SELECT id "
            . "FROM ciniki_product_types "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND name_s = '" . ciniki_core_dbQuote($ciniki, $args['type_name_s']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'type');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['type']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.85', 'msg'=>'Invalid product type'));
        }
        $args['type_id'] = $rc['type']['id'];
    }

    if( $args['supplier_id'] == '' ) {
        $args['supplier_id'] = 0;
    }

    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, preg_replace('/#/', '-', $args['name']));
    }

    //
    // Check the permalink does not already exist
    //
    $strsql = "SELECT id "
        . "FROM ciniki_products "
        . "WHERE permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'product');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['product']) || (isset($rc['rows']) && count($rc['rows']) > 0) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.86', 'msg'=>'You already have a product with that name, please choose another'));
    }

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Add the product
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.products.product', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $product_id = $rc['id'];

    //
    // Add the wine type and kit_length to the product details
    //
//  $detail_fields = array(
//      'wine_type'=>'wine_type',
//      'kit_length'=>'kit_length',
//      'winekit_oak'=>'winekit_oak',
//      'winekit_body'=>'winekit_body',
//      'winekit_sweetness'=>'winekit_sweetness',
//      );
//  foreach($detail_fields as $field => $detail_field) {
//      if( isset($args[$field]) && $args[$field] != '' ) {
//          $strsql = "INSERT INTO ciniki_product_details (tnid, product_id, "
//              . "detail_key, detail_value, date_added, last_updated) VALUES ("
//              . "'" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "', "
//              . "'" . ciniki_core_dbQuote($ciniki, $product_id) . "', "
//              . "'" . ciniki_core_dbQuote($ciniki, $detail_field) . "', "
//              . "'" . ciniki_core_dbQuote($ciniki, $args[$field]) . "', "
//              . "UTC_TIMESTAMP(), UTC_TIMESTAMP() "
//              . ")";
//          $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.products');
//          if( $rc['stat'] != 'ok' ) { 
//              ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
//              return $rc;
//          }
//          $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.products', 
//              'ciniki_product_history', $args['tnid'], 
//              1, 'ciniki_product_details', $product_id, $detail_field, $args[$field]);
//      }
//  }

    //
    // Update the categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.products', 'tag', $args['tnid'],
            'ciniki_product_tags', 'ciniki_product_history',
            'product_id', $product_id, 10, $args['categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
            return $rc;
        }
    }

    //
    // Update the subcategories
    //
    for($i=11;$i<30;$i++) {
        if( isset($args['subcategories-'.$i]) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
            $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.products', 'tag', $args['tnid'],
                'ciniki_product_tags', 'ciniki_product_history',
                'product_id', $product_id, $i, $args['subcategories-'.$i]);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
                return $rc;
            }
        }
    }

    //
    // Update the tags
    //
    if( isset($args['tags']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.products', 'tag', $args['tnid'],
            'ciniki_product_tags', 'ciniki_product_history',
            'product_id', $product_id, 40, $args['tags']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
            return $rc;
        }
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.products');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'products');

    $ciniki['syncqueue'][] = array('push'=>'ciniki.products.product', 'args'=>array('id'=>$product_id));

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.products.product', 'object_id'=>$product_id));

    return array('stat'=>'ok', 'id'=>$product_id);
}
?>
