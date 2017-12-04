<?php
//
// Description
// -----------
// This method will return the history for a field that is part of a relationship.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the history for.
// relationship_id:     The ID of the relationship to get the history for.
// field:               The field to get the history for.
//
//                      relationship_type
//                      related_id
//                      date_started
//                      date_ended
//                      notes
//
// Returns
// -------
//  <history>
//      <action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//      ...
//  </history>
//  <users>
//      <user id="1" name="users.display_name" />
//      ...
//  </users>
//
function ciniki_products_relationshipHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'relationship_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Relationship'), 
        'product_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Product'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['tnid'], 'ciniki.products.relationshipHistory', $args['relationship_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['field'] == 'date_started'
        || $args['field'] == 'date_ended' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
        return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.products', 'ciniki_product_history', $args['tnid'], 
            'ciniki_product_relationships', $args['relationship_id'], $args['field'], 'date');
    }

    if( $args['field'] == 'product_id' || $args['field'] == 'related_id' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFkId');
        return ciniki_core_dbGetModuleHistoryFkId($ciniki, 'ciniki.products', 'ciniki_product_history', 
            $args['tnid'], 'ciniki_product_relationships', 
            $args['relationship_id'], $args['field'], 'ciniki_products', 'id', 'ciniki_products.name');
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.products', 'ciniki_product_history', $args['tnid'], 'ciniki_product_relationships', $args['relationship_id'], $args['field']);
}
?>
