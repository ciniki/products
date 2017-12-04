<?php
//
// Description
// ===========
// This function will update the sequences for slider audio.
//
// Arguments
// =========
// ciniki:
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_products_audioUpdateSequences($ciniki, $tnid, $product_id, $new_seq, $old_seq) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');

    //
    // Get the sequences
    //
    $strsql = "SELECT id, sequence AS number "
        . "FROM ciniki_product_audio "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
        . "";
    // Use the last_updated to determine which is in the proper position for duplicate numbers
    if( $new_seq < $old_seq || $old_seq == -1) {
        $strsql .= "ORDER BY sequence, last_updated DESC";
    } else {
        $strsql .= "ORDER BY sequence, last_updated ";
    }
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'sequence');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
        return $rc;
    }
    $cur_number = 1;
    if( isset($rc['rows']) ) {
        $sequences = $rc['rows'];
        foreach($sequences as $sid => $seq) {
            //
            // If the number is not where it's suppose to be, change
            //
            if( $cur_number != $seq['number'] ) {
                $strsql = "UPDATE ciniki_product_audio SET "
                    . "sequence = '" . ciniki_core_dbQuote($ciniki, $cur_number) . "' "
                    . ", last_updated = UTC_TIMESTAMP() "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND id = '" . ciniki_core_dbQuote($ciniki, $seq['id']) . "' "
                    . "";
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.products');
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.products');
                }
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.products', 
                    'ciniki_product_history', $tnid, 
                    2, 'ciniki_product_audio', $seq['id'], 'sequence', $cur_number);
                $ciniki['syncqueue'][] = array('push'=>'ciniki.products.audio', 
                    'args'=>array('id'=>$seq['id']));
                
            }
            $cur_number++;
        }
    }
    
    return array('stat'=>'ok');
}
?>
