<?php
//
// Description
// -----------
// This function will retreive the information about a product.
//
// Info
// ----
// Status: 			started
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <product id="1" name="CC Merlot" wine_type="red" kit_length="
function ciniki_products_productGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
		'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'),
		'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
		'similar'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Similar Products'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productGet', $args['product_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Load currency and timezone settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');
	$datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

	//
	// Load the status maps for the text description of each status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'productStatusMaps');
	$rc = ciniki_products_productStatusMaps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$status_maps = $rc['maps'];

	//
	// Load the status maps for the text description of each type
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'productTypeMaps');
	$rc = ciniki_products_productTypeMaps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$type_maps = $rc['maps'];

	//
	// Get the basic product information
	//
	$strsql = "SELECT ciniki_products.id, "
		. "ciniki_products.name, "
		. "type, type AS type_text, "
		. "category, "
		. "status, status AS status_text, "
		. "barcode, "
		. "ciniki_products.supplier_id, "
		. "ciniki_product_suppliers.name AS supplier_name, "
		. "supplier_item_number, "
		. "supplier_minimum_order, "
		. "supplier_order_multiple, "
		. "price, "
		. "cost, "
		. "msrp, "
		. "primary_image_id, "
		. "short_description, "
		. "long_description, "
		. "start_date, "
		. "end_date, "
		. "webflags, "
		. "IF((webflags&0x01)=1,'Hidden','Visible') AS webvisible "
		. "FROM ciniki_products "
		. "LEFT JOIN ciniki_product_suppliers ON (ciniki_products.supplier_id = ciniki_product_suppliers.id "
			. "AND ciniki_product_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_products.id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'products', 'fname'=>'id', 'name'=>'product',
			'fields'=>array('id', 'name', 'type', 'type_text', 
				'category', 'status', 'status_text',
				'supplier_id', 'supplier_name', 'supplier_item_number', 
				'supplier_minimum_order', 'supplier_order_multiple',
				'barcode', 'price', 'cost', 'msrp', 'primary_image_id',
				'short_description', 'long_description', 'start_date', 'end_date',
				'webflags', 'webvisible'),
			'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				'start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				),
			'maps'=>array('status_text'=>$status_maps, 'type_text'=>$type_maps),
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['products'][0]['product']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1471', 'msg'=>'Unable to find the specified product'));
	}
	$product = $rc['products'][0]['product'];

	//
	// Get the product details
	//
	$strsql = "SELECT detail_key, detail_value FROM ciniki_product_details "
		. "WHERE product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFetchHashRow');
	$rc = ciniki_core_dbQuery($ciniki, $strsql, 'ciniki.products');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$result_handle = $rc['handle'];

	$result = ciniki_core_dbFetchHashRow($ciniki, $result_handle);
	while( isset($result['row']) ) {
		$product[$result['row']['detail_key']] = $result['row']['detail_value'];
		$result = ciniki_core_dbFetchHashRow($ciniki, $result_handle);
	}

	//
	// Get the files for the product
	//
	if( isset($args['files']) && $args['files'] == 'yes' ) {
		$strsql = "SELECT id, name, extension, permalink "
			. "FROM ciniki_product_files "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_product_files.product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'files', 'fname'=>'id', 'name'=>'file',
				'fields'=>array('id', 'name', 'extension', 'permalink')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['files']) ) {
			$product['files'] = $rc['files'];
		} else {
			$product['files'] = array();
		}
	}

	//
	// Get the images for the product
	//
	if( isset($args['images']) && $args['images'] == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
		$strsql = "SELECT ciniki_product_images.id, "
			. "ciniki_product_images.image_id, "
			. "ciniki_product_images.name, "
			. "ciniki_product_images.sequence, "
			. "ciniki_product_images.webflags, "
			. "ciniki_product_images.description "
			. "FROM ciniki_product_images "
			. "WHERE ciniki_product_images.product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
			. "AND ciniki_product_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY ciniki_product_images.sequence, ciniki_product_images.date_added, ciniki_product_images.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'images', 'fname'=>'id', 'name'=>'image',
				'fields'=>array('id', 'image_id', 'name', 'sequence', 'webflags', 'description')),
			));
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['images']) ) {
			$product['images'] = $rc['images'];
			foreach($product['images'] as $img_id => $img) {
				if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
					$rc = ciniki_images_loadCacheThumbnail($ciniki, $args['business_id'], $img['image']['image_id'], 75);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$product['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
				}
			}
		} else {
			$product['images'] = array();
		}
	}

	//
	// Check if similar products is enabled and requested
	//
	if( isset($args['similar']) && $args['similar'] == 'yes' && ($modules['ciniki.products']['flags']&0x01) > 0 ) {
		$strsql = "SELECT ciniki_products.id, ciniki_product_relationships.id AS relationship_id, "
			. "ciniki_products.name "
			. "FROM ciniki_product_relationships "
			. "LEFT JOIN ciniki_products ON ((ciniki_product_relationships.product_id = ciniki_products.id "
					. "OR ciniki_product_relationships.related_id = ciniki_products.id) "
				. "AND ciniki_products.id <> '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
				. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE (ciniki_product_relationships.product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
				. "OR ciniki_product_relationships.related_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
				. ") "
			. "AND ciniki_product_relationships.relationship_type = 10 "
			. "AND ciniki_product_relationships.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ""; 
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id', 'name'=>'product',
				'fields'=>array('id', 'relationship_id', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['products']) ) {
			$product['similar'] = $rc['products'];
		}
	}

	//
	// Format the prices
	//
	$product['price'] = numfmt_format_currency($intl_currency_fmt, $product['price'], $intl_currency);
	$product['cost'] = numfmt_format_currency($intl_currency_fmt, $product['cost'], $intl_currency);
	$product['msrp'] = numfmt_format_currency($intl_currency_fmt, $product['msrp'], $intl_currency);

	return array('stat'=>'ok', 'product'=>$product);
}
?>
