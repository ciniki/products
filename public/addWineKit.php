<?php
//
// Description
// -----------
// This function will add a new product to the products production module.
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_products_addWineKit($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'category_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'errmsg'=>'No category specified'),
		'sales_category_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'errmsg'=>'No sales category specified'),
		'name'=>array('required'=>'yes', 'trimblanks'=>'yes', 'blank'=>'no', 'errmsg'=>'No name specified'),
		'type'=>array('required'=>'no', 'default'=>'0', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No type specified'),
        'barcode'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No barcode specified'), 
        'supplier_business_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'errmsg'=>'No supplier specified'), 
        'supplier_product_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'errmsg'=>'No supplier product specified'), 
        'price'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'errmsg'=>'No price specified'), 
        'cost'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'errmsg'=>'No costspecified'), 
        'msrp'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'errmsg'=>'No msrpspecified'), 
        'wine_type'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No wine type specified'), 
        'kit_length'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No rack length specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/products/private/checkAccess.php');
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.addWineKit', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'products');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the product to the database
	//
	$strsql = "INSERT INTO ciniki_products (business_id, category_id, sales_category_id, name, type, source, flags, status, "
		. "barcode, supplier_business_id, supplier_product_id, "
		. "price, cost, msrp, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['sales_category_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['name']) . "', "
		. "64, 0, 0, 1, "
		. "'" . ciniki_core_dbQuote($ciniki, $args['barcode']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['supplier_business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['supplier_product_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['price']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['cost']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['msrp']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'products');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'products');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'products');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'436', 'msg'=>'Unable to add product'));
	}
	$product_id = $rc['insert_id'];

	//
	// Add all the fields to the change log
	//

	$changelog_fields = array(
		'name',
		'type',
		'source',
		'category_id',
		'sales_category_id',
		'type',
		'barcode',
		'supplier_business_id',
		'supplier_product_id',
		'price',
		'cost',
		'msrp',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddChangeLog($ciniki, 'products', $args['business_id'], 
				'ciniki_products', $product_id, $field, $args[$field]);
		}
	}

	//
	// Add the wine type and kit_length to the product details
	//
	$detail_fields = array(
		'wine_type'=>'wine_type',
		'kit_length'=>'kit_length',
		);
	foreach($detail_fields as $field => $detail_field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$strsql = "INSERT INTO ciniki_product_details (product_id, detail_key, detail_value, date_added, last_updated) VALUES ("
				. "'" . ciniki_core_dbQuote($ciniki, $product_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $detail_field) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $args[$field]) . "', "
				. "UTC_TIMESTAMP(), UTC_TIMESTAMP() "
				. ")";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'products');
			if( $rc['stat'] != 'ok' ) { 
				ciniki_core_dbTransactionRollback($ciniki, 'products');
				return $rc;
			}
			$rc = ciniki_core_dbAddChangeLog($ciniki, 'products', $args['business_id'], 
				'ciniki_product_details', "$product_id-$detail_field", 'detail_value', $args[$field]);
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'products');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$product_id);
}
?>
