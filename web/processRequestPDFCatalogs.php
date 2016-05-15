<?php
//
// Description
// -----------
// This function will generate the gallery page for the website
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_products_web_processRequestPDFCatalogs(&$ciniki, $settings, $business_id, $args) {

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
	if( isset($ciniki['business']['modules']['ciniki.products'])
        && isset($num_uri)
		&& isset($args['uri_split'][$num_uri-3]) && $args['uri_split'][$num_uri-3] != ''
		&& isset($args['uri_split'][$num_uri-2]) && $args['uri_split'][$num_uri-2] == 'download'
		&& isset($args['uri_split'][$num_uri-1]) && $args['uri_split'][$num_uri-1] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'web', 'pdfcatalogFileDownload');
		$rc = ciniki_products_web_pdfcatalogFileDownload($ciniki, $ciniki['request']['business_id'], 
			$ciniki['request']['uri_split'][$num_uri-3], $ciniki['request']['uri_split'][$num_uri-1]);
		if( $rc['stat'] == 'ok' ) {
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			$file = $rc['file'];
			if( $file['extension'] == 'pdf' ) {
				header('Content-Type: application/pdf');
			}
//			header('Content-Disposition: attachment;filename="' . $file['filename'] . '"');
			header('Content-Length: ' . strlen($file['binary_content']));
			header('Cache-Control: max-age=0');

			print $file['binary_content'];
			exit;
		}
		
		//
		// If there was an error locating the files, display generic error
		//
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'3431', 'msg'=>'The file you requested does not exist.'));
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
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
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
            $page['blocks'][] = array('type'=>'imagelist', 'base_url'=>$args['base_url'], 'list'=>$rc['catalogs']);

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
            . "WHERE ciniki_product_pdfcatalogs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_product_pdfcatalogs.permalink = '" . ciniki_core_dbQuote($ciniki, $catalog_permalink) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'catalog');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3428', 'msg'=>'PDF Catalog not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['catalog']) ) {
            return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'3429', 'msg'=>'No'));
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
            . "AND ciniki_product_pdfcatalog_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
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
        // Display the list of images
        //
        $page['blocks'][] = array('type'=>'gallery', 'title'=>'Pages', 'base_url'=>$base_url, 'images'=>$catalog['images']);
    } 
    
    return array('stat'=>'ok', 'page'=>$page);
}
?>
