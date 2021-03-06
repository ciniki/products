<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_products_flags($ciniki, $modules) {
    $flags = array();
    // 0x01
    $flags[] = array('flag'=>array('bit'=>'1', 'name'=>'Similar Products'));
    if( isset($modules['ciniki.recipes']) ) {
        $flags[] = array('flag'=>array('bit'=>'2', 'name'=>'Recommended Recipes'));
    }
    $flags[] = array('flag'=>array('bit'=>'3', 'name'=>'Inventory'));
    $flags[] = array('flag'=>array('bit'=>'4', 'name'=>'Suppliers'));
    // 0x10
    $flags[] = array('flag'=>array('bit'=>'5', 'name'=>'Promotional Products'));
    $flags[] = array('flag'=>array('bit'=>'6', 'name'=>'Inventory Notes'));
//  $flags[] = array('flag'=>array('bit'=>'7', 'name'=>''));
    $flags[] = array('flag'=>array('bit'=>'8', 'name'=>'PDF Catalogs'));
    // 0x0100
    $flags[] = array('flag'=>array('bit'=>'9', 'name'=>'Dropbox'));
    $flags[] = array('flag'=>array('bit'=>'10', 'name'=>'Invoice Description Codes'));
//  $flags[] = array('flag'=>array('bit'=>'11', 'name'=>''));
//  $flags[] = array('flag'=>array('bit'=>'12', 'name'=>''));
    // 0x1000
    $flags[] = array('flag'=>array('bit'=>'13', 'name'=>'Audio Samples'));
//  $flags[] = array('flag'=>array('bit'=>'14', 'name'=>''));
//  $flags[] = array('flag'=>array('bit'=>'15', 'name'=>''));
//  $flags[] = array('flag'=>array('bit'=>'16', 'name'=>''));

    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
