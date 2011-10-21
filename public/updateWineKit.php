<?php
//
// Description
// -----------
// Update the product information for a wine kit.
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
function ciniki_products_updateWineKit($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No product specified'), 
		'category_id'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No category specified'),
		'sales_category_id'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No sales category specified'),
		'name'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No name specified'),
		'source'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No name specified'),
        'barcode'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No barcode specified'), 
        'supplier_business_id'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No supplier specified'), 
        'supplier_product_id'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No supplier product specified'), 
        'price'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No price specified'), 
        'cost'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No cost specified'), 
        'msrp'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No msrp specified'), 
        'wine_type'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No wine type specified'), 
        'kit_length'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No duration specified'), 
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
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.updateWineKit', $args['product_id']); 
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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'products');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the product to the database
	//
	$strsql = "UPDATE products SET last_updated = UTC_TIMESTAMP()";

	//
	// Add all the fields to the change log
	//

	$changelog_fields = array(
		'name',
		'category_id',
		'sales_category_id',
		'source',
		'barcode',
		'supplier_business_id',
		'supplier_product_id',
		'price',
		'cost',
		'msrp',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
			$rc = ciniki_core_dbAddChangeLog($ciniki, 'products', $args['business_id'], 
				'products', $args['product_id'], $field, $args[$field]);
		}
	}
	$strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'products');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'products');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'products');
		return array('stat'=>'fail', 'err'=>array('code'=>'437', 'msg'=>'Unable to add product'));
	}

	//
	// Update the details
	//
	$detail_fields = array(
		'wine_type'=>'wine_type',
		'kit_length'=>'kit_length',
		);
	foreach($detail_fields as $field => $detail_field) {
		if( isset($args[$field]) ) {
			$strsql = "INSERT INTO product_details (product_id, detail_key, detail_value, date_added, last_updated) VALUES ("
				. "'" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $detail_field) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $args[$field]) . "', "
				. "UTC_TIMESTAMP(), UTC_TIMESTAMP() "
				. ") "
				. "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'products');
			if( $rc['stat'] != 'ok' ) { 
				ciniki_core_dbTransactionRollback($ciniki, 'products');
				return $rc;
			}
			$rc = ciniki_core_dbAddChangeLog($ciniki, 'product_details', $args['business_id'], 
				'product_details', $args['product_id'] . "-$detail_field", 'detail_value', $args[$field]);
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'products');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
