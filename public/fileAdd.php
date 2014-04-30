<?php
//
// Description
// ===========
// This method will add a new file to the files table.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the file to.
// product_id:			The ID of the product the file is attached to.
// name:				The name of the file.
// description:			(optional) The extended description of the file, can be much longer than the name.
// webflags:			(optional) How the file is shared with the public and customers.  
//						The default is the file is public.
//
//						0x01 - Hidden, unavailable on the website
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_products_fileAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Web Flags'), 
		'publish_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'date', 'name'=>'Publish Date'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	$name = $args['name'];
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
	$args['permalink'] = ciniki_core_makePermalink($ciniki, $name);

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'private', 'checkAccess');
    $rc = ciniki_products_checkAccess($ciniki, $args['business_id'], 'ciniki.products.fileAdd', $args['product_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Check the permalink doesn't already exist
	//
	$strsql = "SELECT id, name, permalink FROM ciniki_product_files "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'file');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1479', 'msg'=>'You already have a file with this name, please choose another name'));
	}

    //
    // Check to see if an image was uploaded
    //
    if( isset($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] == UPLOAD_ERR_INI_SIZE ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1480', 'msg'=>'Upload failed, file too large.'));
    }
    // FIXME: Add other checkes for $_FILES['uploadfile']['error']

	//
	// Make sure a file was submitted
	//
	if( !isset($_FILES) || !isset($_FILES['uploadfile']) || $_FILES['uploadfile']['tmp_name'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1481', 'msg'=>'No file specified.'));
	}

	$args['org_filename'] = $_FILES['uploadfile']['name'];
	$args['extension'] = preg_replace('/^.*\.([a-zA-Z]+)$/', '$1', $args['org_filename']);

	//
	// Check the extension is a PDF, currently only accept PDF files
	//
	if( $args['extension'] != 'pdf' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1482', 'msg'=>'The file must be a PDF file.'));
	}
	$args['binary_content'] = file_get_contents($_FILES['uploadfile']['tmp_name']);

	//
	// Add the file to the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	return ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.products.file', $args, 0x07);
}
?>
