<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_products_hooks_uiSettings($ciniki, $tnid, $args) {

    $settings = array();

    //
    // Get the product types
    //
    $strsql = "SELECT id, name_s, name_p, object_def "
        . "FROM ciniki_product_types "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY name_s "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'types', 'fname'=>'id', 'fields'=>array('id', 'name_s', 'name_p', 'object_def')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['types']) ) { 
        $settings['types'] = array();
        foreach($rc['types'] as $tid => $type) {
            $object_def = unserialize($type['object_def']);
            $object_def['id'] = $type['id'];
            $settings['types'][] = array('type'=>$object_def);
        }
    }

    $rsp = array('stat'=>'ok', 'settings'=>$settings, 'menu_items'=>array(), 'settings_menu_items'=>array());  

    //
    // Check if full owner
    //
    if( isset($ciniki['tenant']['modules']['ciniki.products'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5800,
            'label'=>'Products', 
            'edit'=>array('app'=>'ciniki.products.main'),
            'add'=>array('app'=>'ciniki.products.edit', 'args'=>array('product_id'=>0)),
            'search'=>array(
                'method'=>'ciniki.products.productSearch',
                'args'=>array('status'=>1, 'reserved'=>'yes'),
                'container'=>'products',
                'cols'=>1,
                'cellValues'=>array(
                    '0'=>'d.product.name;',
                    '1'=>'d.product.inventory_current_num + ((d.product.rsv!=null&&parseFloat(d.product.rsv)>0)?\' <span class="subdue">[\' + d.product.rsv + \']</span>\':\'\')',
                    ),
                'noData'=>'No products found',
                'edit'=>array('method'=>'ciniki.products.product', 'args'=>array('product_id'=>'d.product.id;')),
                'submit'=>array('method'=>'ciniki.products.main', 'args'=>array('search'=>'search_str')),
                ),
            );
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.products', 0x04) ) {
            $menu_item['search']['headerValues'] = array('Product', 'Inventory [Reserved]');
            $menu_item['search']['cols'] = 2;
        }
        $rsp['menu_items'][] = $menu_item;
    } 

    //
    // Check if only a sales rep 
    //
    if( isset($ciniki['tenant']['modules']['ciniki.products'])
        && !isset($args['permissions']['owners']) 
        && !isset($args['permissions']['employees']) 
        && !isset($args['permissions']['resellers'])
        && isset($args['permissions']['salesreps'])
        ) {
        $menu_item = array(
            'priority'=>5800,
            'label'=>'Products', 
            'edit'=>array('app'=>'ciniki.products.inventory'),
            'search'=>array(
                'method'=>'ciniki.products.productSearch',
                'args'=>array('status'=>1, 'reserved'=>'yes'),
                'container'=>'products',
                'cols'=>1,
                'cellValues'=>array(
                    '0'=>'d.product.name;',
                    '1'=>'d.product.inventory_current_num + ((d.product.rsv!=null&&parseFloat(d.product.rsv)>0)?\' <span class="subdue">[\' + d.product.rsv + \']</span>\':\'\')',
                    ),
                'noData'=>'No products found',
                ),
            );
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.products', 0x04) ) {
            $menu_item['search']['headerValues'] = array('Product', 'Inventory [Reserved]');
            $menu_item['search']['cols'] = 2;
        }
        $rsp['menu_items'][] = $menu_item;
    } 

    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.products', 0x0200) 
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>5800, 'label'=>'Products', 'edit'=>array('app'=>'ciniki.products.settings'));
    }

    return $rsp;
}
?>
