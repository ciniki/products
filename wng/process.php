<?php
//
// Description
// -----------
// This function will return the blocks for the website.
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_products_wng_process(&$ciniki, $tnid, &$request, $section) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.products']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.89', 'msg'=>"I'm sorry, the section you requested does not exist."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.181', 'msg'=>"No section specified."));
    }

    if( $section['ref'] == 'ciniki.products.pricelist' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'wng', 'pricelistProcess');
        return ciniki_products_wng_pricelistProcess($ciniki, $tnid, $request, $section);
    }

    return array('stat'=>'ok');
}
?>
