<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_products_sync_objects($ciniki, &$sync, $business_id, $args) {
    
    $objects = array();
    $objects['product'] = array(
        'name'=>'Product',
        'table'=>'ciniki_products',
        'fields'=>array(
            'name'=>array(),
            'type'=>array(),
            'source'=>array(),
            'flags'=>array(),
            'status'=>array(),
            'barcode'=>array(),
            'supplier_business_id'=>array(),
            'supplier_product_id'=>array(),
            'price'=>array(),
            'cost'=>array(),
            'msrp'=>array(),
            ),
        'details'=>array('key'=>'product_id', 'table'=>'ciniki_product_details'),
        'history_table'=>'ciniki_product_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
