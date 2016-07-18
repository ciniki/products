<?php
//
// Description
// -----------
// This function returns the array of status text for ciniki_products.status.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_products_productStatusMaps($ciniki) {
    
    $status_maps = array(
        '10'=>'Active',
        '60'=>'Inactive',
        );
    
    return array('stat'=>'ok', 'maps'=>$status_maps);
}
?>
