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
		'available_to',
		'min_quantity',
		'amount',
		'discount_amount',
		'discount_percentage',
		'taxtype_id',
		'start_date',
		'end_date',
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

	$args['object_def'] = serialize($object_def);
	if( isset($args['parent_images']) ) {
		if( $args['parent_images'] == 'on' ) {
			$object_def['parent']['images'] = array();
		} elseif( $args['parent_images'] == 'off' && isset($object_def['parent']['images']) ) {
			unset($object_def['parent']['images']);
		}
	}
	if( isset($args['child_images']) ) {
		if( $args['child_images'] == 'on' ) {
			if( !isset($object_def['child']) ) { $object_def['child'] = array('products'=>array()); }
			$object_def['child']['images'] = array();
		} elseif( $args['child_images'] == 'off' && isset($object_def['child']['images']) ) {
			unset($object_def['child']['images']);
		}
	}
	if( isset($args['parent_files']) ) {
		if( $args['parent_files'] == 'on' ) {
			$object_def['parent']['files'] = array();
		} elseif( $args['parent_files'] == 'off' && isset($object_def['parent']['files']) ) {
			unset($object_def['parent']['files']);
		}
	}
	if( isset($args['child_files']) ) {
		if( $args['child_files'] == 'on' ) {
			if( !isset($object_def['child']) ) { $object_def['child'] = array('products'=>array()); }
			$object_def['child']['files'] = array();
		} elseif( $args['child_files'] == 'off' && isset($object_def['child']['files']) ) {
			unset($object_def['child']['files']);
		}
	}
	if( isset($args['parent_similar']) ) {
		if( $args['parent_similar'] == 'on' ) {
			$object_def['parent']['similar'] = array();
		} elseif( $args['parent_similar'] == 'off' && isset($object_def['parent']['similar']) ) {
			unset($object_def['parent']['similar']);
		}
	}
	if( isset($args['child_similar']) ) {
		if( $args['child_similar'] == 'on' ) {
			if( !isset($object_def['child']) ) { $object_def['child'] = array('products'=>array()); }
			$object_def['child']['similar'] = array();
		} elseif( $args['child_similar'] == 'off' && isset($object_def['child']['similar']) ) {
			unset($object_def['child']['similar']);
		}
	}
	if( isset($args['parent_recipes']) ) {
		if( $args['parent_recipes'] == 'on' ) {
			$object_def['parent']['recipes'] = array();
		} elseif( $args['parent_recipes'] == 'off' && isset($object_def['parent']['recipes']) ) {
			unset($object_def['parent']['recipes']);
		}
	}
	if( isset($args['child_recipes']) ) {
		if( $args['child_recipes'] == 'on' ) {
			if( !isset($object_def['child']) ) { $object_def['child'] = array('products'=>array()); }
			$object_def['child']['recipes'] = array();
		} elseif( $args['child_recipes'] == 'off' && isset($object_def['child']['recipes']) ) {
			unset($object_def['child']['recipes']);
		}
	}

	return array('stat'=>'ok', 'object_def'=>$object_def);
}
?>
