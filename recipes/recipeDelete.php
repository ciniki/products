<?php
//
// Description
// ===========
// This function will remove a recipe from a product.  This will be called when the 
// the ciniki.recipes.recipeDelete method is called.
//
// Arguments
// ---------
// ciniki:      
// tnid:     The ID of the tenant making the API call.
// args:            The args passed to the ciniki_core_objectFishHooks function.
//
// Returns
// -------
//
function ciniki_products_recipes_recipeDelete($ciniki, $tnid, $args) {
    
    //
    // Check to make sure the required arguments are passed
    //
    if( !isset($args['recipe_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.133', 'msg'=>'Missing hook argument recipe_id'));
    }

    //
    // Get the list of products that link to the recipe being deleted
    //
    $strsql = "SELECT id, uuid, product_id "
        . "FROM ciniki_product_recipes "
        . "WHERE recipe_id = '" . ciniki_core_dbQuote($ciniki, $args['recipe_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'recipe');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $refs = $rc['rows'];
        foreach($refs as $rid => $ref) {
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.products.recipe', 
                $ref['id'], $ref['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.134', 'msg'=>'Unable to remove product recipe reference', 'err'=>$rc['err']));
            }
        }
    }
}
?>
