<?php
//
// Description
// -----------
// This method will return the list of product categories.
//
// Arguments
// ---------
// 
// Returns
// -------
// <categories>
//      <category name="Red Wines"/>
// </categories>
//
function ciniki_products_productCategories($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'25', 'name'=>'Limit'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['tnid'], 'ciniki.products.productCategories', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

//  ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
//  $date_format = ciniki_users_dateFormat($ciniki);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    //
    // Get the list of

    $strsql = "SELECT category AS name, "
//      . "IF(category='','Uncategorized',category) AS name, "
        . "COUNT(id) AS num_products "
        . "FROM ciniki_products "
        . "WHERE ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['status']) && $args['status'] != '' ) {
        $strsql .= "AND ciniki_products.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    $strsql .= "GROUP BY category ";
    $strsql .= "ORDER BY ciniki_products.category "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'categories', 'fname'=>'name', 'name'=>'category',
            'fields'=>array('name', 'product_count'=>'num_products')),
        ));
    if( $rc != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        return array('stat'=>'ok', 'categories'=>array());
    }
    $categories = $rc['categories'];

    return array('stat'=>'ok', 'categories'=>$categories);
}
?>
