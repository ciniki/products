<?php
//
// Description
// -----------
// This method returns the list of products.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <products>
//		<product id="1" name="CC Merlot" type="red" kit_length="4"
// </products>
//
function ciniki_products_productExportExcel($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productExportExcel', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// Load maps
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'maps');
	$rc = ciniki_products_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	//
	// Load the product types
	//
	$types = array();
	$strsql = "SELECT id, name_s, name_p, object_def "
		. "FROM ciniki_product_types "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'types', 'fname'=>'id', 
			'fields'=>array('id', 'name_s', 'name_p', 'object_def')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['types']) ) {
		$types = $rc['types'];
	}

	$use_prices = 'no';
	$product_fields = array('id', 'type');
	foreach($types as $tid => $type) {
		$obj = unserialize($type['object_def']);
		$types[$tid]['object'] = $obj;
		foreach($obj['parent']['products'] as $field_id => $field) {
			if( array_search($field_id, $product_fields) === FALSE ) {
				$product_fields[] = $field_id;
			}
		}
		if( isset($obj['parent']['prices']) ) {
			$use_prices = 'yes';
		}
	}

	//
	// Get the list of suppliers
	//
	$suppliers = array();
	$strsql = "SELECT id, name "
		. "FROM ciniki_product_suppliers "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'suppliers', 'fname'=>'id', 
			'fields'=>array('id', 'name')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['suppliers']) ) {
		$suppliers = $rc['suppliers'];
	}

	//
	// Load pricepoints
	//
	$pricepoints = array();
	$use_pricepoints = 'no';
	if( ($ciniki['business']['modules']['ciniki.customers']['flags']&0x1000) > 0 ) {
		$strsql = "SELECT id, name, code "
			. "FROM ciniki_customer_pricepoints "
			. "WHERE ciniki_customer_pricepoints.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'pricepoints', 'fname'=>'id', 
				'fields'=>array('id', 'name', 'code')),
			));	
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['pricepoints']) && count($rc['pricepoints']) > 0 ) {
			$use_pricepoints = 'yes';
			$pricepoints = $rc['pricepoints'];
		}
	}

	//
	// Get the prices
	//
	$prices = array();
	$num_price_columns = 0;
	if( $use_prices == 'yes' ) {
		$strsql = "SELECT id, product_id, name, pricepoint_id, available_to, min_quantity, "
			. "unit_amount, unit_discount_amount, unit_discount_percentage, "
			. "taxtype_id, start_date, end_date, "
			. "IF((ciniki_product_prices.webflags&0x01)=1,'Hidden','Visible') AS visible "
			. "FROM ciniki_product_prices "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY product_id "
			. "";
		if( $use_pricepoints == 'yes' ) {
			$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
				array('container'=>'products', 'fname'=>'product_id', 
					'fields'=>array('id'=>'product_id')),
				array('container'=>'pricepoints', 'fname'=>'pricepoint_id', 
					'fields'=>array('id'=>'pricepoint_id')),
				array('container'=>'prices', 'fname'=>'id', 
					'fields'=>array('id', 'name', 'pricepoint_id', 'available_to', 'min_quantity',
						'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
						'taxtype_id', 'start_date', 'end_date',
						'visible')),
				));
		} else {
			$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
				array('container'=>'products', 'fname'=>'product_id', 
					'fields'=>array('id'=>'product_id')),
				array('container'=>'prices', 'fname'=>'id', 
					'fields'=>array('id', 'name', 'pricepoint_id', 'available_to', 'min_quantity',
						'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
						'taxtype_id', 'start_date', 'end_date',
						'visible')),
				));
		}
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['products']) ) {
			$prices = $rc['products'];
		}
	}

	//
	// Get the list of products
	//
	$products = array();
	$strsql = "SELECT id, "
		. "parent_id, "
		. "name, "
		. "code, "
		. "permalink, "
		. "type_id, "
		. "flags, "
		. "status, "
		. "barcode, "
		. "price, "
		. "unit_discount_amount, "
		. "unit_discount_percentage, "
		. "taxtype_id, "
		. "cost, "
		. "msrp, "
		. "sell_unit, "
		. "supplier_id, "
		. "supplier_item_number, "
		. "supplier_minimum_order, "
		. "supplier_order_multiple, "
		. "manufacture_min_time, "
		. "manufacture_max_time, "
		. "supplier_id, "
		. "supplier_item_number, "
		. "supplier_minimum_order, "
		. "supplier_order_multiple, "
		. "manufacture_min_time, "
		. "manufacture_max_time, "
		. "inventory_flags, "
		. "inventory_current_num, "
		. "inventory_reorder_num, "
		. "inventory_reorder_quantity, "
		. "shipping_flags, "
		. "shipping_weight, "
		. "shipping_height, "
		. "shipping_length, "
		. "shipping_size_units, "
		. "IF(primary_image_id>0,'yes','no') AS primary_image, "
		. "short_description, "
		. "long_description, "
		. "start_date, "
		. "end_date, "
		. "IF((ciniki_products.webflags&0x01)=1,'Hidden','Visible') AS visible, "
		. "IF((ciniki_products.webflags&0x02)=2,'Yes','No') AS sellonline "
		. "FROM ciniki_products "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
		array('container'=>'products', 'fname'=>'id', 
			'fields'=>array('id', 'parent_id', 'name', 'code', 'permalink', 'type_id', 'flags',
				'status', 'barcode', 'price', 'unit_discount_amount', 'unit_discount_percentage',
				'taxtype_id', 'cost', 'msrp', 'sell_unit', 
				'supplier_id', 'supplier_item_number', 'supplier_minimum_order', 'supplier_order_multiple',
				'manufacture_min_time', 'manufacture_max_time', 'inventory_flags', 'inventory_current_num',
				'inventory_reorder_num', 'inventory_reorder_quantity',
				'shipping_flags', 'shipping_weight', 'shipping_height', 'shipping_length', 'shipping_size_units',
				'primary_image', 'short_description', 'long_description', 'start_date', 'end_date', 'visible', 'sellonline'
				),
			'maps'=>array('status'=>$maps['product']['status'])),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['products']) ) {
		$products = $rc['products'];
	}

	$columns = array(
		'id'=>array('name'=>'ID', 'type'=>'productfield'),
		'type'=>array('name'=>'Type', 'type'=>'productfield')
		);
	if( in_array('code', $product_fields) ) { $columns['code'] = array('name'=>'Code', 'type'=>'productfield'); }
	if( in_array('name', $product_fields) ) { $columns['name'] = array('name'=>'Name', 'type'=>'productfield'); }
	if( in_array('status', $product_fields) ) { $columns['status'] = array('name'=>'Status', 'type'=>'productfield'); }
	if( in_array('barcode', $product_fields) ) { $columns['barcode'] = array('name'=>'Barcode', 'type'=>'productfield'); }
	if( in_array('price', $product_fields) ) { $columns['price'] = array('name'=>'Price', 'type'=>'productfield'); }
	if( in_array('unit_discount_amount', $product_fields) ) { $columns['unit_discount_amount'] = array('name'=>'Discount $', 'type'=>'productfield'); }
	if( in_array('unit_discount_percentage', $product_fields) ) { $columns['unit_discount_percentage'] = array('name'=>'Discount %', 'type'=>'productfield'); }
	if( in_array('taxtype_id', $product_fields) ) { $columns['taxtype_id'] = array('name'=>'Tax', 'type'=>'productfield'); }
	if( in_array('cost', $product_fields) ) { $columns['cost'] = array('name'=>'Cost', 'type'=>'productfield'); }
	if( in_array('msrp', $product_fields) ) { $columns['msrp'] = array('name'=>'MSRP', 'type'=>'productfield'); }
	//
	// Add the price columns
	//
	if( $use_pricepoints == 'yes' ) {
		$i = 0;
		foreach($pricepoints as $pricepoint) {
			$i++;
			$columns['pricepoint_' . $pricepoint['id'] . '_amount'] = array('name'=>$pricepoint['code'], 'type'=>'pricepoint', 'id'=>$pricepoint['id'], 'field'=>'unit_amount');
			$columns['pricepoint_' . $pricepoint['id'] . '_discount_amount'] = array('name'=>$pricepoint['code'] . ' Discount $', 'type'=>'pricepoint', 'id'=>$pricepoint['id'], 'field'=>'unit_discount_amount');
			$columns['pricepoint_' . $pricepoint['id'] . '_discount_percentage'] = array('name'=>$pricepoint['code'] . ' Discount %', 'type'=>'pricepoint', 'id'=>$pricepoint['id'], 'field'=>'unit_discount_percentage');
		}
	} elseif( $use_prices == 'yes' ) {
	}
	if( in_array('sell_unit', $product_fields) ) { $columns['sell_unit'] = array('name'=>'Sell Unit', 'type'=>'productfield'); }
	if( in_array('supplier_id', $product_fields) ) { $columns['supplier_id'] = array('name'=>'Supplier', 'type'=>'productfield'); }
	if( in_array('supplier_item_number', $product_fields) ) { $columns['supplier_item_number'] = array('name'=>'Item Number', 'type'=>'productfield'); }
	if( in_array('supplier_minimum_order', $product_fields) ) { $columns['supplier_minimum_order'] = array('name'=>'Min Order', 'type'=>'productfield'); }
	if( in_array('supplier_order_multiple', $product_fields) ) { $columns['supplier_order_multiple'] = array('name'=>'Multi Order', 'type'=>'productfield'); }
	if( in_array('manufacture_min_time', $product_fields) ) { $columns['manufacture_min_time'] = array('name'=>'Manufacture Min Time', 'type'=>'productfield'); }
	if( in_array('manufacture_max_time', $product_fields) ) { $columns['manufacture_max_time'] = array('name'=>'Manufacture Max Time', 'type'=>'productfield'); }
	if( in_array('inventory_flags', $product_fields) ) { $columns['inventory_flags'] = array('name'=>'Inventory', 'type'=>'productfield'); }
	if( in_array('inventory_current_num', $product_fields) ) { $columns['inventory_current_num'] = array('name'=>'Current Inventory', 'type'=>'productfield'); }
	if( in_array('inventory_reorder_num', $product_fields) ) { $columns['inventory_reorder_num'] = array('name'=>'Reorder #', 'type'=>'productfield'); }
	if( in_array('inventory_reorder_quantity', $product_fields) ) { $columns['inventory_reorder_quantity'] = array('name'=>'Reorder Quantity', 'type'=>'productfield'); }
	if( in_array('shipping_flags', $product_fields) ) { $columns['shipping_flags'] = array('name'=>'Shipping', 'type'=>'productfield'); }
	if( in_array('shipping_weight', $product_fields) ) { $columns['shipping_weight'] = array('name'=>'Weight', 'type'=>'productfield'); }
	if( in_array('shipping_height', $product_fields) ) { $columns['shipping_height'] = array('name'=>'Height', 'type'=>'productfield'); }
	if( in_array('shipping_length', $product_fields) ) { $columns['shipping_length'] = array('name'=>'Length', 'type'=>'productfield'); }
	if( in_array('shipping_size_units', $product_fields) ) { $columns['shipping_size_units'] = array('name'=>'Size Units', 'type'=>'productfield'); }
//	if( in_array('start_date', $product_fields) ) { $columns['start_date'] = array('name'=>'Start Date', 'type'=>'productfield'); }
//	if( in_array('end_date', $product_fields) ) { $columns['end_date'] = array('name'=>'End Date', 'type'=>'productfield'); }
	if( in_array('webflags', $product_fields) ) { 
		$columns['visible'] = array('name'=>'Visible', 'type'=>'productfield'); 
		$columns['sellonline'] = array('name'=>'Sell Online', 'type'=>'productfield');
	}
	if( in_array('primary_image', $product_fields) ) { $columns['primary_image'] = array('name'=>'Primary Image', 'type'=>'productfield'); }
	if( in_array('short_description', $product_fields) ) { $columns['short_description'] = array('name'=>'Synopsis', 'type'=>'productfield'); }
	if( in_array('long_description', $product_fields) ) { $columns['long_description'] = array('name'=>'Description', 'type'=>'productfield'); }

	//
	// Check if output should be excel
	//
	ini_set('memory_limit', '4192M');
	require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
	$objPHPExcel = new PHPExcel();
	$start_date = new DateTime("now", new DateTimeZone($intl_timezone));
	$title = "Product Export - " . date_format($start_date, 'M d, Y');
	$sheet_title = $title;
	$sheet = $objPHPExcel->setActiveSheetIndex(0);
	$sheet->setTitle($sheet_title);

	//
	// Headers
	//
	$col = 0;
	$row = 1;
	foreach($columns as $column) {
		$sheet->setCellValueByColumnAndRow($col++, 1, $column['name'], false);
	}
	$row++;

	foreach($products as $pid => $product) {
		$col = 0;

		foreach($columns as $field => $column) {
			if( $field == 'type' ) {
				$sheet->setCellValueByColumnAndRow($col++, $row, (isset($types[$product['type_id']])?$types[$product['type_id']]['name_s']:''), false);
			} 
			elseif( $column['type'] == 'pricepoint' ) {
				$value = '';
				if( isset($prices[$pid]['pricepoints'][$column['id']]['prices']) ) {
					foreach($prices[$pid]['pricepoints'][$column['id']]['prices'] as $prid => $price ) {
						$value .= ($value!=''?', ':'') . $price[$column['field']];
					}
				}
				$sheet->setCellValueByColumnAndRow($col++, $row, $value, false);
			} 
			elseif( $field == 'barcode' ) {
				$sheet->setCellValueExplicitByColumnAndRow($col++, $row, "" . $product[$field], PHPExcel_Cell_DataType::TYPE_STRING);
			} 
			elseif( $column['type'] == 'productfield' ) {
				$sheet->setCellValueByColumnAndRow($col++, $row, $product[$field], false);
			} 
			else {
				$col++;
			}

		}
		$row++;
	}

	$sheet->getStyle('A1:' . PHPExcel_Cell::stringFromColumnIndex($col) . '1')->getFont()->setBold(true);
	$sheet->freezePane('A2');

	//
	// Redirect output to a client’s web browser (Excel)
	//
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="export.xls"');
	header('Cache-Control: max-age=0');

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save('php://output');

	return array('stat'=>'exit');
}
?>
