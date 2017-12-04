<?php
//
// Description
// -----------
// This function will validate the user making the request has the 
// proper permissions to access or change the data.  This function
// must be called by all public API functions to ensure security.
//
// Info
// ----
// Status:          beta
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant to check access for.
// method:              The requested method.
// product_id:          The ID of the product requested.
// 
// Returns
// -------
//
function ciniki_products_checkAccess(&$ciniki, $tnid, $method, $product_id=0) {
    //
    // Check if the tenant is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    $rc = ciniki_tenants_checkModuleAccess($ciniki, $tnid, 'ciniki', 'products');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( !isset($rc['ruleset']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.17', 'msg'=>'No permissions granted'));
    }
    $modules = $rc['modules'];

    //
    // Sysadmins are allowed full access
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
        return array('stat'=>'ok', 'modules'=>$modules);
    }

    //
    // Check if the tenant is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'getUserPermissions');
    $rc = ciniki_tenants_getUserPermissions($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $perms = $rc['perms'];

    //
    // Check the session user is a tenant owner
    //
    if( $tnid <= 0 ) {
        // If no tnid specified, then fail
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.18', 'msg'=>'Access denied'));
    }

    // 
    // Owners and Employees have access to everything
    //
    if( ($ciniki['tenant']['user']['perms']&0x03) > 0 ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'perms'=>$perms);
    }

    //
    // If the user is part of the salesreps, ensure they have access to request method
    //
    $salesreps_methods = array(
        'ciniki.products.productSearch',
        'ciniki.products.productStats',
        'ciniki.products.productList',
        'ciniki.products.categoryDetails',
        );
    if( in_array($method, $salesreps_methods) && ($perms&0x04) == 0x04 ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'perms'=>$perms);
    }

    //
    // By default, deny access
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.19', 'msg'=>'Access denied'));
    
/* OLD CODE
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    //
    // Find any users which are owners of the requested tnid
    //
    $strsql = "SELECT tnid, user_id FROM ciniki_tenant_users "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND package = 'ciniki' "
        . "AND status = 10 "
        . "AND (permission_group = 'owners' OR permission_group = 'employees') "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.20', 'msg'=>'Access denied', 'err'=>$rc['err']));
    }
    //
    // If the user has permission, return ok
    //
    if( !isset($rc['rows']) 
        || !isset($rc['rows'][0]) 
        || $rc['rows'][0]['user_id'] <= 0 
        || $rc['rows'][0]['user_id'] != $ciniki['session']['user']['id'] ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.21', 'msg'=>'Access denied'));
    }

    // 
    // At this point, we have ensured the user is a part of the tenant.
    //

    //
    // Check the product is attached to the tenant
    //
    if( $product_id > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
        $strsql = "SELECT tnid, id FROM ciniki_products "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
        $rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.products', 'products', 'product', array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.22', 'msg'=>'Access denied')));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.23', 'msg'=>'Access denied', 'err'=>$rc['err']));
        }
        if( $rc['num_rows'] != 1 
            || $rc['products'][0]['product']['tnid'] != $tnid
            || $rc['products'][0]['product']['id'] != $product_id ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.24', 'msg'=>'Access denied'));
        }
    }

    //
    // All checks passed, return ok
    //
    return array('stat'=>'ok', 'modules'=>$modules); */
}
?>
