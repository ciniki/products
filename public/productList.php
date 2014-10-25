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
function ciniki_products_productList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
		'subcategory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sub-Category'),
		'tag'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tag'),
		'supplier_id'=>array('required'=>'no', 'name'=>'Supplier'),
		'type_id'=>array('required'=>'no', 'name'=>'Type'),
		'reserved'=>array('required'=>'no', 'name'=>'Reserved Quantities'),
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.productList', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

	if( isset($args['category']) && $args['category'] != '' 
		&& isset($args['subcategory']) && $args['subcategory'] != '' 
		) {
		$strsql = "SELECT ciniki_products.id, "
			. "ciniki_products.code, "
			. "ciniki_products.name, "
			. "IF((inventory_flags&0x01)=1,inventory_current_num,'') AS inventory_current_num "
			. "FROM ciniki_product_tags AS t1 "
			. "LEFT JOIN ciniki_product_tags AS t2 ON ("
				. "t1.product_id = t2.product_id "
				. "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND t2.permalink = '" . ciniki_core_dbQuote($ciniki, $args['subcategory']) . "' "
				. "AND t2.tag_type > 10 "
				. "AND t2.tag_type < 30 "
				. ") "
			. "LEFT JOIN ciniki_products ON ("
				. "t2.product_id = ciniki_products.id "
				. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE t1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
			. "AND t1.tag_type = 10 "
			. "ORDER BY ciniki_products.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id', 'name'=>'product',
				'fields'=>array('id', 'code', 'name', 'inventory_current_num')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) ) {
			return array('stat'=>'ok', 'products'=>array());
		}
		$products = $rc['products'];
	}
	elseif( isset($args['category']) && $args['category'] == '' ) {
		$strsql = "SELECT ciniki_products.id, ciniki_products.code, ciniki_products.name, "
			. "IF((inventory_flags&0x01)=1,inventory_current_num,'') AS inventory_current_num "
			. "FROM ciniki_products "
			. "LEFT JOIN ciniki_product_tags ON ("
				. "ciniki_products.id = ciniki_product_tags.product_id "
				. "AND ciniki_product_tags.tag_type = 10 "
				. ") "
			. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ISNULL(tag_name) "
			. "ORDER BY ciniki_products.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id', 'name'=>'product',
				'fields'=>array('id', 'code', 'name', 'inventory_current_num')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) ) {
			return array('stat'=>'ok', 'products'=>array());
		}
		$products = $rc['products'];
	}

	elseif( isset($args['category']) ) {
		$strsql = "SELECT ciniki_products.id, "
			. "ciniki_products.code, "
			. "ciniki_products.name, "
			. "IF((inventory_flags&0x01)=1,inventory_current_num,'') AS inventory_current_num "
			. "FROM ciniki_product_tags "
			. "LEFT JOIN ciniki_products ON ("
				. "ciniki_product_tags.product_id = ciniki_products.id "
				. "AND ciniki_product_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_product_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
			. "ORDER BY ciniki_product_tags.tag_name, ciniki_products.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id', 'name'=>'product',
				'fields'=>array('id', 'code', 'name', 'inventory_current_num')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) ) {
			return array('stat'=>'ok', 'products'=>array());
		}
		$products = $rc['products'];
	}

	elseif( isset($args['supplier_id']) ) {
		$strsql = "SELECT ciniki_products.id, "
			. "ciniki_products.category, "
			. "ciniki_products.code, "
			. "ciniki_products.name, "
			. "IF((inventory_flags&0x01)=1,inventory_current_num,'') AS inventory_current_num "
			. "FROM ciniki_products "
			. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND supplier_id = '" . ciniki_core_dbQuote($ciniki, $args['supplier_id']) . "' "
			. "ORDER BY name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id', 'name'=>'product',
				'fields'=>array('id', 'code', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) ) {
			return array('stat'=>'ok', 'products'=>array());
		}
		$products = $rc['products'];
	}

	elseif( isset($args['type_id']) && $args['type_id'] != '' ) {
		$strsql = "SELECT ciniki_products.id, "
			. "ciniki_products.category, "
			. "ciniki_products.code, "
			. "ciniki_products.name, "
			. "IF((inventory_flags&0x01)=1,inventory_current_num,'') AS inventory_current_num "
			. "FROM ciniki_products "
			. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND type_id = '" . ciniki_core_dbQuote($ciniki, $args['type_id']) . "' "
			. "ORDER BY name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id', 'name'=>'product',
				'fields'=>array('id', 'code', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) ) {
			return array('stat'=>'ok', 'products'=>array());
		}
		$products = $rc['products'];
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1843', 'msg'=>'Unable to find products'));
	}

	//
	// Get the reserved quantities for the products
	//
	if( isset($args['reserved']) && $args['reserved'] == 'yes' && count($products) > 0 ) {
		$product_ids = array();
		foreach($products as $pid => $product) {
			$product_ids[] = $product['product']['id'];
			$products[$pid]['product']['inventory_reserved'] = 0;
		}
		$product_ids = array_unique($product_ids);
		if( isset($ciniki['business']['modules']['ciniki.sapos']) ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getReservedQuantities');
			$rc = ciniki_sapos_getReservedQuantities($ciniki, $args['business_id'], 
				'ciniki.products.product', $product_ids, 0);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$quantities = $rc['quantities'];
			foreach($products as $pid => $product) {
				if( isset($quantities[$product['product']['id']]) ) {
					$products[$pid]['product']['inventory_reserved'] = (float)$quantities[$product['product']['id']]['quantity_reserved'];
				}
			}
		}
	}
	
	return array('stat'=>'ok', 'products'=>$products);
}
?>
