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

	if( isset($args['category']) && $args['category'] == '' ) {
		$strsql = "SELECT ciniki_products.id, ciniki_products.name, "
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
				'fields'=>array('id', 'name', 'inventory_current_num')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) ) {
			return array('stat'=>'ok', 'products'=>array());
		}
		return array('stat'=>'ok', 'products'=>$rc['products']);
	}

	elseif( isset($args['category']) ) {
		$strsql = "SELECT ciniki_products.id, "
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
				'fields'=>array('id', 'name', 'inventory_current_num')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) ) {
			return array('stat'=>'ok', 'products'=>array());
		}
		return array('stat'=>'ok', 'products'=>$rc['products']);
	}

	elseif( isset($args['supplier_id']) ) {
		$strsql = "SELECT ciniki_products.id, "
			. "ciniki_products.category, "
			. "ciniki_products.name, "
			. "IF((inventory_flags&0x01)=1,inventory_current_num,'') AS inventory_current_num "
			. "FROM ciniki_products "
			. "WHERE ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND supplier_id = '" . ciniki_core_dbQuote($ciniki, $args['supplier_id']) . "' "
			. "ORDER BY name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'products', 'fname'=>'id', 'name'=>'product',
				'fields'=>array('id', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['products']) ) {
			return array('stat'=>'ok', 'products'=>array());
		}
		return array('stat'=>'ok', 'products'=>$rc['products']);
	} else {
		$strsql .= "ORDER BY category, name ";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'categories', 'fname'=>'category', 'name'=>'category',
				'fields'=>array('name'=>'category')),
			array('container'=>'products', 'fname'=>'id', 'name'=>'product',
				'fields'=>array('id', 'category', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['categories']) ) {
			return array('stat'=>'ok', 'categories'=>array());
		}
		return array('stat'=>'ok', 'categories'=>$rc['categories']);
	}

	return $rc;
}
?>
