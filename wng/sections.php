<?php
//
// Description
// -----------
// Return the list of sections available from the products module
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_products_wng_sections(&$ciniki, $tnid, $args) {

    //
    // Check to make sure blog module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.products']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.88', 'msg'=>'Module not enabled'));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Get the list of products
    //
    $dt = new DateTime('now', new DateTimeZone($intl_timezone));
    $strsql = "SELECT products.id, "
        . "products.name "
        . "FROM ciniki_products AS products "
        . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'products', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.182', 'msg'=>'Unable to load products', 'err'=>$rc['err']));
    }
    $products = isset($rc['products']) ? $rc['products'] : array();

    array_unshift($products, ['id'=>0, 'name'=>'None']);

    //
    // The latest blog section
    //
    $sections['ciniki.products.pricelist'] = array(
        'name' => 'Price List',
        'module' => 'Products',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'product-id-1' => array('label'=>'Product 1', 'type'=>'select', 
                'complex_options' => array('value'=>'id', 'name'=>'name'), 
                'options'=>$products,
                ),
            'product-id-2' => array('label'=>'Product 2', 'type'=>'select', 
                'complex_options' => array('value'=>'id', 'name'=>'name'), 
                'options'=>$products,
                ),
            'product-id-3' => array('label'=>'Product 3', 'type'=>'select', 
                'complex_options' => array('value'=>'id', 'name'=>'name'), 
                'options'=>$products,
                ),
            'product-id-4' => array('label'=>'Product 4', 'type'=>'select', 
                'complex_options' => array('value'=>'id', 'name'=>'name'), 
                'options'=>$products,
                ),
            'product-id-5' => array('label'=>'Product 5', 'type'=>'select', 
                'complex_options' => array('value'=>'id', 'name'=>'name'), 
                'options'=>$products,
                ),
            'product-id-6' => array('label'=>'Product 6', 'type'=>'select', 
                'complex_options' => array('value'=>'id', 'name'=>'name'), 
                'options'=>$products,
                ),
            'product-id-7' => array('label'=>'Product 7', 'type'=>'select', 
                'complex_options' => array('value'=>'id', 'name'=>'name'), 
                'options'=>$products,
                ),
            'product-id-8' => array('label'=>'Product 8', 'type'=>'select', 
                'complex_options' => array('value'=>'id', 'name'=>'name'), 
                'options'=>$products,
                ),
            'product-id-9' => array('label'=>'Product 9', 'type'=>'select', 
                'complex_options' => array('value'=>'id', 'name'=>'name'), 
                'options'=>$products,
                ),
            ),
        );
        
    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
