<?php
//
// Description
// -----------
// This method will return the list of PDF Catalogs for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get PDF Catalog for.
//
// Returns
// -------
//
function ciniki_products_pdfcatalogList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['tnid'], 'ciniki.products.pdfcatalogList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'maps');
    $rc = ciniki_products_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of catalogs
    //
    $strsql = "SELECT ciniki_product_pdfcatalogs.id, "
        . "ciniki_product_pdfcatalogs.name, "
        . "ciniki_product_pdfcatalogs.permalink, "
        . "ciniki_product_pdfcatalogs.sequence, "
        . "ciniki_product_pdfcatalogs.status, "
        . "ciniki_product_pdfcatalogs.status AS status_text, "
        . "ciniki_product_pdfcatalogs.flags, "
        . "ciniki_product_pdfcatalogs.num_pages "
        . "FROM ciniki_product_pdfcatalogs "
        . "WHERE ciniki_product_pdfcatalogs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sequence, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'catalogs', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'sequence', 'status', 'status_text', 'flags', 'num_pages'),
            'maps'=>array('status_text'=>$maps['pdfcatalog']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['catalogs']) ) {
        $catalogs = $rc['catalogs'];
    } else {
        $catalogs = array();
    }

    return array('stat'=>'ok', 'catalogs'=>$catalogs);
}
?>
