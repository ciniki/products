<?php
//
// Description
// -----------
// This function will generate the gallery page for the website
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_products_web_processRequestPDFCatalogs(&$ciniki, $settings, $tnid, $args) {

    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        'path'=>(isset($settings['page-products-path'])&&$settings['page-products-path']!=''?$settings['page-products-path']:'yes'),
        );

    //
    // Check if a file was specified to be downloaded
    //
    $download_err = '';
    if( isset($args['uri_split']) ) {
        $num_uri = count($args['uri_split']);
    }
    if( isset($ciniki['tenant']['modules']['ciniki.products'])
        && isset($args['uri_split'][0]) && $args['uri_split'][0] == 'download'
        && isset($args['uri_split'][1]) && $args['uri_split'][1] != '' ) {
        $catalog_permalink = preg_replace("/\.pdf.*$/", '', $args['uri_split'][1]);
        //
        // Get the tenant storage directory
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
        $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $tenant_storage_dir = $rc['storage_dir'];

        $strsql = "SELECT ciniki_product_pdfcatalogs.id, "
            . "ciniki_product_pdfcatalogs.uuid, "
            . "ciniki_product_pdfcatalogs.name, "
            . "ciniki_product_pdfcatalogs.permalink, "
            . "ciniki_product_pdfcatalogs.flags "
            . "FROM ciniki_product_pdfcatalogs "
            . "WHERE ciniki_product_pdfcatalogs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x03) = 0x03 "
            . "AND ciniki_product_pdfcatalogs.permalink = '" . ciniki_core_dbQuote($ciniki, $catalog_permalink) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'catalog');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['catalog']) ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.products.158', 'msg'=>"I'm sorry, we could not find the file you were looking for. Please try again or contact us for help."));
        }
        $catalog = $rc['catalog'];
        $storage_filename = $tenant_storage_dir . '/ciniki.products/pdfcatalogs/' . $catalog['uuid'][0] . '/' . $catalog['uuid'];

        if( !file_exists($storage_filename) ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.products.159', 'msg'=>"I'm sorry, we could not find the file you were looking for. Please try again or contact us for help."));
        }

        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Cache-Control: max-age=0');
        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($storage_filename));
        ob_clean();
        flush();
        $fh = fopen($storage_filename, "rb");
        if ($fh === false) { 
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.products.160', 'msg'=>"I'm sorry, we could not find the file you were looking for. Please try again or contact us for help."));
        }
        while (!feof($fh)) { 
            echo fread($fh, (1024*1024));
            ob_flush();  // flush output
            flush();
        }
        exit;
        
        //
        // If there was an error locating the files, display generic error
        //
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.products.161', 'msg'=>'The file you requested does not exist.'));
    }

    $thumbnail_format = 'square-cropped';
    $thumbnail_padding_color = '#ffffff';
    if( isset($settings['page-pdfcatalogs-thumbnail-format']) && $settings['page-pdfcatalogs-thumbnail-format'] == 'square-padded' ) {
        $thumbnail_format = $settings['page-pdfcatalogs-thumbnail-format'];
        if( isset($settings['page-pdfcatalogs-thumbnail-padding-color']) && $settings['page-pdfcatalogs-thumbnail-padding-color'] != '' ) {
            $thumbnail_padding_color = $settings['page-pdfcatalogs-thumbnail-padding-color'];
        } 
    }

    //
    // Check if catalog to be displayed
    //
    $page_number = 1;
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] != '' ) {
        $catalog_permalink = $args['uri_split'][0];
        if( isset($args['uri_split'][1]) && $args['uri_split'][1] != '' ) {
            $page_number = preg_replace("/[^0-9]/", '', $args['uri_split'][1]);
        } else {
            $page_number = 1;
        }
    } 
   
    //
    // Check for list of catalogs
    //
    else {
       $strsql = "SELECT ciniki_product_pdfcatalogs.id, "
            . "ciniki_product_pdfcatalogs.name, "
            . "ciniki_product_pdfcatalogs.permalink, "
            . "ciniki_product_pdfcatalogs.primary_image_id, "
            . "ciniki_product_pdfcatalogs.synopsis, "
            . "'yes' AS is_details "
            . "FROM ciniki_product_pdfcatalogs "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 30 "
            . "AND (flags&0x01) > 0 "
            . "ORDER BY sequence, name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'catalogs', 'fname'=>'id', 'fields'=>array('id', 'name', 'permalink', 'image_id'=>'primary_image_id', 'synopsis', 'is_details')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['catalogs']) || count($rc['catalogs']) < 1 ) { 
            $page['blocks'][] = array('type'=>'content', 'content'=>"We're sorry, there are no catalogs available right now.");
            return array('stat'=>'ok', 'page'=>$page);
        }
        if( count($rc['catalogs']) == 1 ) {
            $catalog_permalink = $rc['catalogs'][0]['permalink'];
        } else {
            $page['blocks'][] = array('type'=>'imagelist', 'base_url'=>$args['base_url'], 'list'=>$rc['catalogs'],
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color,
                );

        }
    }

    //
    // Display the catalog
    //
    if( isset($catalog_permalink) && $catalog_permalink != '' ) {
        $base_url = $args['base_url'] . '/' . $catalog_permalink;
        $strsql = "SELECT ciniki_product_pdfcatalogs.id, "
            . "ciniki_product_pdfcatalogs.name, "
            . "ciniki_product_pdfcatalogs.permalink, "
            . "ciniki_product_pdfcatalogs.sequence, "
            . "ciniki_product_pdfcatalogs.status, "
            . "ciniki_product_pdfcatalogs.flags, "
            . "ciniki_product_pdfcatalogs.num_pages, "
            . "ciniki_product_pdfcatalogs.synopsis, "
            . "ciniki_product_pdfcatalogs.description "
            . "FROM ciniki_product_pdfcatalogs "
            . "WHERE ciniki_product_pdfcatalogs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x01) > 0 "
            . "AND ciniki_product_pdfcatalogs.permalink = '" . ciniki_core_dbQuote($ciniki, $catalog_permalink) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'catalog');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.162', 'msg'=>'PDF Catalog not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['catalog']) ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.products.163', 'msg'=>'No'));
        }
        $catalog = $rc['catalog'];

        if( $page_number < 1 ) {
            $page_number = 1;
        } elseif( $page_number > $catalog['num_pages'] ) {
            $page_number = $catalog['num_pages'];
        }

        //
        // Get the images for the catalog
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
        $strsql = "SELECT ciniki_product_pdfcatalog_images.id, "
            . "ciniki_product_pdfcatalog_images.image_id, "
            . "ciniki_product_pdfcatalog_images.page_number, "
            . "ciniki_product_pdfcatalog_images.page_number AS permalink, "
            . "CONCAT_WS(' ', 'Page', ciniki_product_pdfcatalog_images.page_number) AS title "
            . "FROM ciniki_product_pdfcatalog_images "
            . "WHERE ciniki_product_pdfcatalog_images.catalog_id = '" . ciniki_core_dbQuote($ciniki, $catalog['id']) . "' "
            . "AND ciniki_product_pdfcatalog_images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY ciniki_product_pdfcatalog_images.page_number "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'images', 'fname'=>'page_number', 'fields'=>array('id', 'image_id', 'page_number', 'permalink', 'title')),
            ));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( isset($rc['images']) ) {
            $catalog['images'] = $rc['images'];
        }

        //
        // Get the main image to display
        //
        if( isset($catalog['images'][$page_number]) ) {
            $block = array('type'=>'galleryimage', 'image'=>$catalog['images'][$page_number]);

            $block['image']['description'] = $catalog['description'];
            if( $page_number > 1 && isset($catalog['images'][($page_number-1)]) ) {
                $block['prev'] = array('url'=>$base_url . '/' . ($page_number-1), 
                    'permalink'=>$base_url . '/' . ($page_number-1), 
                    'image_id'=>$catalog['images'][($page_number-1)]['image_id'],
                    );
            }
            if( $page_number < $catalog['num_pages'] && isset($catalog['images'][($page_number+1)]) ) {
                $block['next'] = array('url'=>$base_url . '/' . ($page_number+1), 
                    'permalink'=>$base_url . '/' . ($page_number+1), 
                    'image_id'=>$catalog['images'][($page_number+1)]['image_id'],
                    );
            }
            $page['blocks'][] = $block;
        }

        //
        // Display the download link
        //
        if( ($catalog['flags']&0x02) == 0x02 ) {
            $page['blocks'][] = array('type'=>'content', 'html'=>"<p class='wide alignright'><a href='" . $args['base_url'] . "/download/" . $catalog['permalink'] . ".pdf'>Download the PDF catalog.</a></p>");
        }

        //
        // Display the list of images
        //
        $page['blocks'][] = array('type'=>'gallery', 'title'=>'Pages', 'base_url'=>$base_url, 'images'=>$catalog['images']);
    } 
    
    return array('stat'=>'ok', 'page'=>$page);
}
?>
