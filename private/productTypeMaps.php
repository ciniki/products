<?php
//
// Description
// -----------
// This function returns the array of status text for ciniki_products.type.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_products_productTypeMaps($ciniki) {
    
    $status_maps = array(
        '1'=>'Generic',
        '64'=>'Wine Kit',
        '65'=>'Craft',
        );
    
    return array('stat'=>'ok', 'maps'=>$status_maps);
}
?>
