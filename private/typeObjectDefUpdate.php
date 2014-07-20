<?php
//
// Description
// -----------
// This function returns the array of status text for ciniki_products.type.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_products_typeObjectDefUpdate($ciniki, $object_def, $args) {

	if( $object_def == NULL ) {
		$object_def = array(
			'name'=>array('name_s'=>'', 'name_p'=>''),
			);
	}
	if( !isset($object['parent']) ) {
		$object['parent'] = array('products'=>array());
	}
	if( !isset($object['parent']['products']) ) {
		$object['parent']['products'] = array();
	}

	$product_fields = array(
		'name',
		'code',
		'category',
		'source',
		'flags',
		'status',
		'barcode',
		'supplier_business_id',
		'supplier_product_id',
		'price',
		'unit_discount_amount',
		'unit_discount_percentage',
		'taxtype_id',
		'cost',
		'msrp',
		'supplier_id',
		'supplier_item_number',
		'supplier_minimum_order',
		'supplier_order_multiple',
		'manufacture_min_time',
		'manufacture_max_time',
		'inventory_flags',
		'inventory_current_num',
		'shipping_flags',
		'shipping_weight',
		'shipping_weight_units',
		'shipping_length',
		'shipping_width',
		'shipping_height',
		'shipping_size_units',
		'primary_image_id',
		'short_description',
		'long_description',
		'description',
		'start_date',
		'end_date',
		'webflags',
		'detail01',
		'detail02',
		'detail03',
		'detail04',
		'detail05',
		'detail06',
		'detail07',
		'detail08',
		'detail09',
		);
	$price_fields = array(
		'name',
		'available_to',
		'min_quantity',
		'unit_amount',
		'unit_discount_amount',
		'unit_discount_percentage',
		'taxtype_id',
		'start_date',
		'end_date',
		'webflags',
		);

	foreach($product_fields as $field) {
		if( isset($args['parent_product_' . $field]) ) {
			if( $args['parent_product_' . $field] == 'on' ) {
				$object_def['parent']['products'][$field] = array();
			} elseif( $args['parent_product_' . $field] == 'off' 
				&& isset($object_def['parent']['products'][$field]) ) {
				unset($object_def['parent']['products'][$field]);
			}
		}
		if( (!isset($args['parent_product_' . $field . '-name']) || $args['parent_product_' . $field . '-name'])
			&& isset($args['parent_product_' . $field . '-name']) ) {
			if( isset($object_def['parent']['products']) ) {
				$object_def['parent']['products'][$field]['name'] = $args['parent_product_' . $field . '-name'];
			}
		}
		if( isset($args['child_product_' . $field]) ) {
			if( $args['child_product_' . $field] == 'on' ) {
				if( !isset($object_def['child']) ) {
					$object_def['child'] = array('products'=>array());
				}
				$object_def['child']['products'][$field] = array();
			} elseif( $args['child_product_' . $field] == 'off' 
				&& isset($object_def['child']['products'][$field]) ) {
				unset($object_def['child']['products'][$field]);
			}
		}
		if( (!isset($args['child_product_' . $field . '-name']) || $args['child_product_' . $field . '-name'])
			&& isset($args['child_product_' . $field . '-name']) ) {
			if( isset($object_def['child']['products']) ) {
				$object_def['child']['products'][$field]['name'] = $args['child_product_' . $field . '-name'];
			}
		}
	}
	foreach($price_fields as $field) {
		if( isset($args['parent_price_' . $field]) ) {
			if( $args['parent_price_' . $field] == 'on' ) {
				if( !isset($object_def['parent']['prices']) ) { $object_def['parent']['prices'] = array(); }
				$object_def['parent']['prices'][$field] = array();
			} elseif( $args['parent_price_' . $field] == 'off' 
				&& isset($object_def['parent']['prices'][$field]) ) {
				unset($object_def['parent']['prices'][$field]);
			}
		}
		if( isset($args['child_price_' . $field]) ) {
			if( $args['child_price_' . $field] == 'on' ) {
				if( !isset($object_def['child']) ) { $object_def['child'] = array('products'=>array()); }
				if( !isset($object_def['child']['prices']) ) { $object_def['child']['prices'] = array(); }
				$object_def['child']['prices'][$field] = array();
			} elseif( $args['child_price_' . $field] == 'off' 
				&& isset($object_def['child']['prices'][$field]) ) {
				unset($object_def['child']['prices'][$field]);
			}
		}
	}

	//
	// Remove old ones
	//
	if( isset($object_def['parent']['subcategories']) ) { unset($object_def['parent']['subcategories']); }

//	$args['object_def'] = serialize($object_def);

	//
	// Check for subcategories
	//
	for($i=11;$i<30;$i++) {
		$field = 'parent_subcategories-' . $i;
		if( isset($args[$field]) ) {
			if( $args[$field] == 'on' ) {
				$object_def['parent']['subcategories-' . $i] = array();
				if( isset($args[$field . '-sname']) ) {
					$object_def['parent']['subcategories-' . $i]['sname'] = $args[$field . '-sname'];
				}
				if( isset($args[$field . '-pname']) ) {
					$object_def['parent']['subcategories-' . $i]['pname'] = $args[$field . '-pname'];
				}
			} elseif( $args[$field] == 'off' && isset($object_def['parent']['subcategories-' . $i]) ) {
				unset($object_def['parent']['subcategories-' . $i]);
			}
		}
	}

	$extras = array('categories', 
//		'subcategories-11', 'subcategories-12', 'subcategories-13', 'subcategories-14', 'subcategories-15', 
		'tags', 'images', 'files', 'similar', 'recipes');
	foreach($extras as $extra) {
		$field = 'parent_' . $extra;
		if( isset($args[$field]) ) {
			if( $args[$field] == 'on' ) {
				$object_def['parent'][$extra] = array();
			} elseif( $args[$field] == 'off' && isset($object_def['parent'][$extra]) ) {
				unset($object_def['parent'][$extra]);
			}
		}
		$field = 'child_' . $extra;
		if( isset($args[$field]) ) {
			if( $args[$field] == 'on' ) {
				$object_def['child'][$extra] = array();
			} elseif( $args[$field] == 'off' && isset($object_def['child'][$extra]) ) {
				unset($object_def['child'][$extra]);
			}
		}
	}

	return array('stat'=>'ok', 'object_def'=>$object_def);
}
?>
