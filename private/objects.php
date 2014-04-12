<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_products_objects($ciniki) {
	
	$objects = array();
	$objects['product'] = array(
		'name'=>'Product',
		'sync'=>'yes',
		'table'=>'ciniki_products',
		'fields'=>array(
			'name'=>array(),
			'type'=>array(),
			'category'=>array(),
			'permalink'=>array(),
			'source'=>array(),
			'flags'=>array(),
			'status'=>array(),
			'barcode'=>array(),
			'supplier_id'=>array('ref'=>'ciniki.products.supplier'),
			'supplier_product_id'=>array(),
			'supplier_item_number'=>array(),
			'supplier_minimum_order'=>array(),
			'supplier_order_multiple'=>array(),
			'manufacture_min_time'=>array(),
			'manufacture_max_time'=>array(),
			'inventory_flags'=>array(),
			'inventory_current_num'=>array(),
			'price'=>array(),
			'cost'=>array(),
			'msrp'=>array(),
			'primary_image_id'=>array('ref'=>'ciniki.images.image'),
			'short_description'=>array(),
			'long_description'=>array(),
			'start_date'=>array(),
			'end_date'=>array(),
			'webflags'=>array(),
			),
		'details'=>array('key'=>'product_id', 'table'=>'ciniki_product_details'),
		'history_table'=>'ciniki_product_history',
		);
	$objects['supplier'] = array(
		'name'=>'Supplier',
		'sync'=>'yes',
		'table'=>'ciniki_product_suppliers',
		'fields'=>array(
			'name'=>array(),
			),
		'history_table'=>'ciniki_product_history',
		);
	$objects['image'] = array(
		'name'=>'Image',
		'sync'=>'yes',
		'table'=>'ciniki_product_images',
		'fields'=>array(
			'product_id'=>array('ref'=>'ciniki.products.product'),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'description'=>array(),
			),
		'history_table'=>'ciniki_product_history',
		);
	$objects['file'] = array(
		'name'=>'File',
		'sync'=>'yes',
		'table'=>'ciniki_product_files',
		'fields'=>array(
			'product_id'=>array('ref'=>'ciniki.products.product'),
			'extension'=>array(),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'description'=>array(),
			'org_filename'=>array(),
			'publish_date'=>array(),
			'binary_content'=>array('history'=>'no'),
			),
		'history_table'=>'ciniki_product_history',
		);
	$objects['relationship'] = array(
		'name'=>'Relationship',
		'sync'=>'yes',
		'table'=>'ciniki_product_relationships',
		'fields'=>array(
			'product_id'=>array('ref'=>'ciniki.products.product'),
			'relationship_type'=>array(),
			'related_id'=>array('ref'=>'ciniki.products.product'),
			'date_started'=>array(),
			'date_ended'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_product_history',
		);
	$objects['oref'] = array(
		'name'=>'Object Reference',
		'sync'=>'yes',
		'table'=>'ciniki_product_refs',
		'fields'=>array(
			'product_id'=>array('ref'=>'ciniki.products.product'),
			'object'=>array(),
			'object_id'=>array('oref'=>'object'),
			),
		'history_table'=>'ciniki_product_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
