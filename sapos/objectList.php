<?php
//
// Description
// ===========
// This method returns the list of objects that can be returned
// as invoice items.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_products_sapos_objectList($ciniki, $business_id) {

    $objects = array(
        //
        // this object should only be added to carts
        //
        'ciniki.products.product' => array(
            'name' => 'Product',
            ),
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
