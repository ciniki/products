<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get options for.
//
// args:            The possible arguments for profiles
//
//
// Returns
// -------
//
function ciniki_products_hooks_webOptions(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.products']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.16', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Get the settings from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'tnid', $tnid, 'ciniki.web', 'settings', 'page');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        $settings = array();
    } else {
        $settings = $rc['settings'];
    }

    $pages = array();

    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.products', 0x80) ) {
        $pages['ciniki.pdfcatalogs'] = array('name'=>'Catalogs', 'options'=>array(
            array('label'=>'Thumbnail Format',
                'setting'=>'page-pdfcatalogs-thumbnail-format', 
                'type'=>'toggle',
                'value'=>(isset($settings['page-pdfcatalogs-thumbnail-format'])?$settings['page-pdfcatalogs-thumbnail-format']:'square-cropped'),
                'toggles'=>array(
                    array('value'=>'square-cropped', 'label'=>'Cropped'),
                    array('value'=>'square-padded', 'label'=>'Padded'),
                    ),
                ),
            array('label'=>'Thumbnail Padding Color',
                'setting'=>'page-pdfcatalogs-thumbnail-padding-color', 
                'type'=>'colour',
                'value'=>(isset($settings['page-pdfcatalogs-thumbnail-padding-color'])?$settings['page-pdfcatalogs-thumbnail-padding-color']:'#ffffff'),
                ),
            ));
    }

    $pages['ciniki.products'] = array('name'=>'Products', 'options'=>array(
        array('label'=>'Display Product Codes',
            'setting'=>'page-products-code', 
            'type'=>'toggle',
            'value'=>(isset($settings['page-products-code'])?$settings['page-products-code']:'no'),
            'toggles'=>array(
                array('value'=>'no', 'label'=>'No'),
                array('value'=>'yes', 'label'=>'Yes'),
                ),
            ),
        array('label'=>'Category Format',
            'setting'=>'page-products-categories-format', 
            'type'=>'toggle',
            'value'=>(isset($settings['page-products-categories-format'])?$settings['page-products-categories-format']:'thumbnails'),
            'toggles'=>array(
                array('value'=>'thumbnails', 'label'=>'Thumbnails'),
                array('value'=>'list', 'label'=>'List'),
                ),
            ),
        array('label'=>'Thumbnail Format',
            'setting'=>'page-products-thumbnail-format', 
            'type'=>'toggle',
            'value'=>(isset($settings['page-products-thumbnail-format'])?$settings['page-products-thumbnail-format']:'square-cropped'),
            'toggles'=>array(
                array('value'=>'square-cropped', 'label'=>'Cropped'),
                array('value'=>'square-padded', 'label'=>'Padded'),
                ),
            ),
        array('label'=>'Thumbnail Padding Color',
            'setting'=>'page-products-thumbnail-padding-color', 
            'type'=>'colour',
            'value'=>(isset($settings['page-products-thumbnail-padding-color'])?$settings['page-products-thumbnail-padding-color']:'#ffffff'),
            ),
        ));

    //
    // Check if sliders supported in website
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.web', 0x02) ) {
        //
        // Get the list of sliders
        //
        $strsql = "SELECT id, name "
            . "FROM ciniki_web_sliders "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY name ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'slider');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.170', 'msg'=>'Unable to load slider', 'err'=>$rc['err']));
        }
        $sliders = array(array('label' => 'None', 'value' => 0));
        if( isset($rc['rows']) ) {
            foreach($rc['rows'] as $row) {
                $sliders[] = array('label' => $row['name'], 'value' => $row['id']);
            }
        }
       
        $pages['ciniki.products']['options'][] = array(
            'label' => 'Slider',
            'setting' => 'page-products-slider-id',
            'type' => 'select',
            'value' => (isset($settings['page-products-slider-id'])?$settings['page-products-slider-id']:'0'),
            'options' => $sliders,
            );
        
    }

    return array('stat'=>'ok', 'pages'=>$pages);
}
?>
