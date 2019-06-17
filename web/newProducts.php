<?php
//
// Description
// -----------
// This function will return the list of new products.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get images for.
// limit:           The maximum number of images to return.
//
// Returns
// -------
//
function ciniki_products_web_newProducts($ciniki, $settings, $tnid, $limit) {

    $strsql = "SELECT ciniki_products.id, "
        . "name AS title, permalink, primary_image_id AS image_id, "
        . "short_description AS description, 'yes' AS is_details "
        . "FROM ciniki_products "
        . "WHERE ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_products.primary_image_id > 0 "
        . "AND start_date < UTC_TIMESTAMP() "
        . "AND (end_date = '0000-00-00 00:00:00' OR end_date > UTC_TIMESTAMP()) "
        . "AND (ciniki_products.webflags&0x01) > 0 "
        . "ORDER BY ciniki_products.start_date DESC "
        . "";
    if( $limit != '' && $limit > 0 && is_int($limit) ) {
        $strsql .= "LIMIT " . intval($limit) . " ";
    } else {
        $strsql .= "LIMIT 6";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'products', 'fname'=>'id',
            'fields'=>array('id', 'title', 'permalink', 'image_id', 'description', 'is_details')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( !isset($rc['products']) ) {
        return array('stat'=>'ok', 'products'=>array());
    }

    return array('stat'=>'ok', 'products'=>$rc['products']);
}
?>
