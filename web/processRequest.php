<?php
//
// Description
// -----------
// This function will generate the gallery page for the website
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_products_web_processRequest(&$ciniki, $settings, $business_id, $args) {

	if( !isset($ciniki['business']['modules']['ciniki.products']) ) {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'3045', 'msg'=>"I'm sorry, the page you requested does not exist."));
	}
	$page = array(
		'title'=>$args['page_title'],
		'breadcrumbs'=>$args['breadcrumbs'],
		'blocks'=>array(),
		);

	//
	// Check if a file was specified to be downloaded
	//
	$download_err = '';
	if( isset($ciniki['business']['modules']['ciniki.products'])
		&& isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'product'
		&& isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != ''
		&& isset($ciniki['request']['uri_split'][2]) && $ciniki['request']['uri_split'][2] == 'download'
		&& isset($ciniki['request']['uri_split'][3]) && $ciniki['request']['uri_split'][3] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'web', 'fileDownload');
		$rc = ciniki_products_web_fileDownload($ciniki, $ciniki['request']['business_id'], 
			$ciniki['request']['uri_split'][1], $ciniki['request']['uri_split'][3]);
		if( $rc['stat'] == 'ok' ) {
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			$file = $rc['file'];
			if( $file['extension'] == 'pdf' ) {
				header('Content-Type: application/pdf');
			}
//			header('Content-Disposition: attachment;filename="' . $file['filename'] . '"');
			header('Content-Length: ' . strlen($file['binary_content']));
			header('Cache-Control: max-age=0');

			print $file['binary_content'];
			exit;
		}
		
		//
		// If there was an error locating the files, display generic error
		//
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'1504', 'msg'=>'The file you requested does not exist.'));
	}

    //
    // Load the product type definitions
    //
    $strsql = "SELECT id, name_s, name_p, object_def "
        . "FROM ciniki_product_types "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "ORDER BY id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'types', 'fname'=>'id',
            'fields'=>array('id', 'name_s', 'name_p', 'object_def')),
        ));
    $types = isset($rc['types'])?$rc['types']:array();
    $object_defs = array();
    // Prep the object defs
    foreach($types as $type_id => $type) {
        $object_defs[$type_id] = unserialize($type['object_def']);
    }

	//
	// Store the content created by the page
	//
	$page_content = '';

	//
	// FIXME: Check if anything has changed, and if not load from cache
	//
    
    if( $page['title'] == '' ) {
        $page_title = "Products";
    }
	$tags = array();
	$ciniki['response']['head']['og']['url'] = $ciniki['request']['domain_base_url'] . '/products';

    #
    # URLs
    # /products
    # /products/product
    # /products/category
    # /products/category/product
    # /products/category/subcategory
    # /products/category/subcategory/product
    #
    $base_url = $args['base_url'];
    $uri_split = $args['uri_split'];

    $display = '';
    $category_display = 'default';
    if( isset($settings['page-products-categories-format']) && $settings['page-products-categories-format'] == 'list' ) {
        $category_display = 'cilist';
    }
    $subcategory_display = 'default';
    $product_display = 'default';
    while(isset($uri_split[0]) ) {
        $permalink = array_shift($uri_split);
        //
        // Check if permalink is a category
        //
        $strsql = "SELECT DISTINCT tag_name "
            . "FROM ciniki_product_tags "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
            . "AND tag_type = 10 "
            . "LIMIT 1 " // Only grab the first one
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'category');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['category']) ) {
            $category_permalink = $permalink;
            $page['title'] = $rc['category']['tag_name'];
            $display = 'category';

            //
            // Get any details about the category from settings
            //
            $strsql = "SELECT id, name, subname, sequence, "
                . "tag_type, display, subcategorydisplay, productdisplay, "
                . "primary_image_id, synopsis, description "
                . "FROM ciniki_product_categories "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND category = '" . ciniki_core_dbQuote($ciniki, $page['title']) . "' "
                . "AND subcategory = '' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'category');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['category']) ) {  
                $category = $rc['category'];
                $page['title'] = $category['name'];
                if( $category['display'] != '' && $category['display'] != 'default' ) {
                    $category_display = $category['display'];
                }
                if( $category['subcategorydisplay'] != '' && $category['subcategorydisplay'] != 'default' ) {
                    $subcategory_display = $category['subcategorydisplay'];
                }
                if( $category['productdisplay'] != '' && $category['productdisplay'] != 'default' ) {
                    $product_display = $category['productdisplay'];
                }
            } else {
                $category = array(
                    'id'=>0,
                    'name'=>$page['title'],
                    'subname'=>'',
                    'sequence'=>1,
                    'tag_type'=>0,
                    'primary_image_id'=>0,
                    'synopsis'=>'',
                    'description'=>'',
                    );
            }
            $base_url .= '/' . $permalink;
            $category['base_url'] = $base_url;
            $category['permalink'] = $permalink;
            $page['breadcrumbs'][] = array('name'=>$page['title'], 'url'=>$base_url);
            continue;   // Skip to next piece of URI
        }

        //
        // Check if permalink is a subcategory (if category is specified)
        //
        if( isset($category) ) {
            // Add breadcrumbs, set page_title
            $strsql = "SELECT DISTINCT tag_name "
                . "FROM ciniki_product_tags "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' ";
            if( isset($category['tag_type']) && $category['tag_type'] > 0 ) {
                $strsql .= "AND tag_type = '" . ciniki_core_dbQuote($ciniki, $category['tag_type']) . "' ";
            } else {
                $strsql .= "AND tag_type > 10 AND tag_type < 30 ";
            }
            $strsql .= "LIMIT 1 " // Only grab the first one
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'subcategory');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['subcategory']) ) {
                $subcategory_permalink = $permalink;
                $page['title'] = $rc['subcategory']['tag_name'];
                $display = 'subcategoryproducts';

                //
                // Get any details about the category from settings
                //
                $strsql = "SELECT id, name, subname, sequence, "
                    . "tag_type, display, subcategorydisplay, productdisplay, "
                    . "primary_image_id, synopsis, description "
                    . "FROM ciniki_product_categories "
                    . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . "AND category = '" . ciniki_core_dbQuote($ciniki, $category_permalink) . "' "
                    . "AND subcategory = '" . ciniki_core_dbQuote($ciniki, $subcategory_permalink) . "' "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'subcategory');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['subcategory']) ) {  
                    $subcategory = $rc['subcategory'];
                    if( $subcategory['name'] != '' ) {
                        $page['title'] = $subcategory['name'];
                    }
                    if( $category['subcategorydisplay'] != '' && $category['subcategorydisplay'] != 'default' ) {
                        $subcategory_display = $category['subcategorydisplay'];
                    }
                    if( $category['productdisplay'] != '' && $category['productdisplay'] != 'default' ) {
                        $product_display = $category['productdisplay'];
                    }
                } else {
                    $subcategory = array(
                        'id'=>0,
                        'name'=>$page['title'],
                        'sequence'=>1,
                        'tag_type'=>0,
                        'primary_image_id'=>0,
                        'synopsis'=>'',
                        'description'=>'',
                        );
                }
                $base_url .= '/' . $permalink;
                $subcategory['base_url'] = $base_url;
                $subcategory['permalink'] = $permalink;
                $page['breadcrumbs'][] = array('name'=>$page['title'], 'url'=>$base_url);
                continue;   // Skip to next piece of URI
            }
        }

        //
        // Check if permalink is a product
        //
        $display = 'product';
        $product_permalink = $permalink;
    }
    
    //
    // Check what should be displayed if no uri specified
    //
    if( $display == '' ) {
        $display = 'categories';
    }

    //
    // Check for display of a category
    //
    if( $display == 'category' ) {
        //
        // Check if there are subcategories or products to display
        //
        $strsql = "SELECT t2.tag_type, t2.tag_name AS name, "
            . "t2.permalink, "
            . "IFNULL(ciniki_product_categories.name, t2.tag_name) AS cat_name, "
            . "IFNULL(ciniki_product_categories.subname, '') AS cat_subname, "
            . "IFNULL(ciniki_product_categories.primary_image_id, 0) AS image_id, "
            . "IFNULL(ciniki_product_categories.synopsis, '') AS synopsis, "
            . "ciniki_products.type_id, "
            . "COUNT(ciniki_products.id) AS num_products "
            . "FROM ciniki_product_tags AS t1 "
            . "LEFT JOIN ciniki_product_tags AS t2 ON ("
                . "t1.product_id = t2.product_id "
                . "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
         if( isset($category['tag_type']) && $category['tag_type'] > 0 ) {
            $strsql .= "AND t2.tag_type = '" . ciniki_core_dbQuote($ciniki, $category['tag_type']) . "' ";
         } else {
            $strsql .= "AND t2.tag_type > 10 AND t2.tag_type < 30 ";
         }
         $strsql .= ") "
            . "LEFT JOIN ciniki_product_categories ON ("
                . "t1.permalink = ciniki_product_categories.category "
                . "AND t2.permalink = ciniki_product_categories.subcategory "
                . "AND ciniki_product_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "LEFT JOIN ciniki_products ON ("
                . "t2.product_id = ciniki_products.id "
                . "AND ciniki_products.parent_id = 0 "
                . "AND (ciniki_products.webflags&0x01) > 0 "
                . "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "WHERE t1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $category_permalink) . "' "
            . "AND t1.tag_type = 10 "
            . "GROUP BY type_id, t2.tag_type, t2.tag_name "
            . "ORDER BY type_id, t2.tag_type, IFNULL(ciniki_product_categories.sequence, 999), t2.tag_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'product_types', 'fname'=>'type_id', 'name'=>'product_type',
                'fields'=>array('id'=>'type_id')),
            array('container'=>'types', 'fname'=>'tag_type', 'name'=>'type',
                'fields'=>array('tag_type', 'name')),
            array('container'=>'categories', 'fname'=>'name', 'name'=>'category',
                'fields'=>array('name'=>'cat_name', 'cat_name', 'subname'=>'cat_subname', 'permalink', 'image_id', 'synopsis', 'num_products')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        if( isset($rc['product_types']) && count($rc['product_types']) > 0 ) {
            $product_types = $rc['product_types'];
            if( isset($category['tag_type']) && $category['tag_type'] > 0 ) {
                //
                // Ignore product types, just build list of categories
                //
                $categories = array();
                foreach($product_types as $ptid => $ptype) {
                    if( isset($ptype['types']) ) {
                        foreach($ptype['types'] as $tag_type) {
                            if( isset($tag_type['categories']) ) {
                                $categories = array_merge($categories, $tag_type['categories']);
                            }
                        }
                    }
                }
                $page['blocks'][] = array('type'=>'tagimages', 'title'=>'', 'base_url'=>$base_url, 'tags'=>$categories);
                $display = '';
            } else {
                //
                // Go through the product types looking for names
                //
                foreach($product_types as $ptid => $ptype) {
                    // Check of the product type exists
                    if( isset($object_defs[$ptype['id']]) ) {
                        $odef = $object_defs[$ptype['id']]['parent'];
                        foreach($ptype['types'] as $tid => $type) {
                            if( isset($odef['subcategories-' . $type['tag_type']]['pname']) ) {
                                $sub_cat_name = $odef['subcategories-' . $type['tag_type']]['pname'];
                            } else {
                                $sub_cat_name = 'Sub-Categories';
                            }
                            if( !isset($types[$sub_cat_name]) ) {
                                $types[$sub_cat_name] = array('name'=>$sub_cat_name, 'categories'=>$type['categories']);
                            } else {
                                foreach($type['categories'] as $new_id => $new_cat) {
                                    // Check for existing category name
                                    $found = 'no';
                                    foreach($types[$sub_cat_name]['categories'] as $old_id => $old_cat) {
                                        if( $old_cat['name'] == $new_cat['name'] ) {
                                            $types[$sub_cat_name]['categories'][$old_id]['num_products'] += $new_cat['num_products'];
                                            $found = 'yes';
                                            break;
                                        }
                                    }
                                    if( $found == 'no' ) {
                                        $types[$sub_cat_name]['categories'][] = $type['categories'][$new_id];
                                    }
                                }
                            }
                        }
                    }
                }
                //
                // Look for any products that are not sub-categorized
                //
                $display = 'products';
            }
        } else {
            $display = 'products';
        }

        //
        // Don't look for a product list for the category if a specific category tag_type has been defined
        //
        if( isset($category['tag_type']) && $category['tag_type'] > 0 ) {
            $display = '';
        }
    }

    //
    // Display the list of categories
    //
    if( $display == 'categories' ) {
        $strsql = "SELECT ciniki_product_tags.tag_name AS name, "
            . "IFNULL(ciniki_product_categories.name, '') AS cat_name, "
            . "IFNULL(ciniki_product_categories.primary_image_id, 0) AS primary_image_id, "
            . "IFNULL(ciniki_product_categories.synopsis, '') AS synopsis, "
            . "ciniki_product_tags.permalink, "
            . "'yes' AS is_details, "
            . "COUNT(ciniki_products.id) AS num_products "
            . "FROM ciniki_product_tags "
            . "LEFT JOIN ciniki_products ON ("
                . "ciniki_product_tags.product_id = ciniki_products.id "
                . "AND ciniki_products.parent_id = 0 "
                . "AND ciniki_products.start_date < UTC_TIMESTAMP() "
                . "AND (ciniki_products.end_date = '0000-00-00 00:00:00' "
                    . "OR ciniki_products.end_date > UTC_TIMESTAMP()"
                    . ") "
                . "AND (ciniki_products.webflags&0x01) > 0 "
                . "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "LEFT JOIN ciniki_product_categories ON ("
                . "ciniki_product_tags.permalink = ciniki_product_categories.category "
                . "AND ciniki_product_categories.subcategory = '' "
                . "AND ciniki_product_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "WHERE ciniki_product_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_product_tags.tag_type = 10 "
            . "AND ciniki_product_tags.tag_name <> '' "
            . "GROUP BY ciniki_product_tags.tag_name "
            . "ORDER BY IFNULL(ciniki_product_categories.sequence, 99), ciniki_product_tags.tag_name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'categories', 'fname'=>'name', 
                'fields'=>array('name', 'cat_name', 'title'=>'cat_name', 'permalink', 'image_id'=>'primary_image_id', 'num_products', 'synopsis', 'is_details')),
            ));
        if( !isset($rc['categories']) ) {
            $page['blocks'][] = array('type'=>'content', 'content'=>"I'm sorry, but we currently don't have any products available.");
		} elseif( $category_display == 'tradingcards' ) {
            $page['blocks'][] = array('type'=>'tradingcards', 'title'=>'', 'base_url'=>$base_url, 'cards'=>$rc['categories']);
		} elseif( $category_display == 'cilist' ) {
            $page['blocks'][] = array('type'=>'cilist', 'title'=>'', 'base_url'=>$base_url, 'list'=>$rc['categories']);
        } else {
            $page['blocks'][] = array('type'=>'tagimages', 'base_url'=>$base_url, 'tags'=>$rc['categories']);
        }
    }

    //
    // Display the list of products
    //
    elseif( $display == 'products' || $display == 'categoryproducts' || $display == 'subcategoryproducts' ) {
        //
        // Check for any products that are not in a sub category
        //
        if( isset($category) && isset($subcategory) ) {
            //
            // Get the list of subcategory products
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'web', 'processRequestSubCategoryProducts');
            $rc = ciniki_products_web_processRequestSubCategoryProducts($ciniki, $settings, $business_id, $category, $subcategory);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $products = $rc['products']; 
        } elseif( isset($category) ) {
            //
            // Get the list of category products
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'web', 'processRequestCategoryProducts');
            $rc = ciniki_products_web_processRequestCategoryProducts($ciniki, $settings, $business_id, $category, null);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $products = $rc['products']; 
        } elseif( $display == 'products' ) {
            $page['blocks'][] = array('type'=>'content', 'content'=>"We're sorry but we don't have any products available yet");
        }
        
        //
        // Sort the products
        //
        uasort($products, function($a, $b) {
            return strnatcmp($a['title'], $b['title']);
        });

        //
        // Decide how to display the information
        //
        if( $display == 'subcategoryproducts' ) {
            if( $subcategory_display == 'image-description-productaudio-prices' ) {
                if( isset($subcategory['primary_image_id']) && $subcategory['primary_image_id'] > 0 ) {
                    $page['blocks'][] = array('type'=>'image', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$subcategory['primary_image_id'],
                        'title'=>$subcategory['name'], 'caption'=>'');
                }
                if( isset($subcategory['description']) && $subcategory['description'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$subcategory['description']);
                } elseif( isset($subcategory['synopsis']) && $subcategory['synopsis'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$subcategory['synopsis']);
                }
                //
                // Get the list of products with their prices and audio samples.
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'web', 'processRequestProductsDetails');
                $rc = ciniki_products_web_processRequestProductsDetails($ciniki, $settings, $business_id, $products, 
                    array('audio'=>'yes', 'object_defs'=>$object_defs));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page['blocks'][] = array('type'=>'audiopricelist', 'section'=>'products', 'title'=>'Products', 'base_url'=>$base_url, 'list'=>$rc['products']);

                ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'web', 'processRequestProductsDetails');
                $rc = ciniki_products_web_processRequestProductsDetails($ciniki, $settings, $business_id, $products, 
                    array('prices'=>'required', 'object_defs'=>$object_defs));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( count($rc['products']) > 0 ) {
                    $page['blocks'][] = array('type'=>'audiopricelist', 'section'=>'ordering', 'title'=>'Order', 'base_url'=>$base_url, 'list'=>$rc['products']);
                }

            } elseif( $subcategory_display == 'image-description-audiopricelist' ) {
                if( isset($subcategory['primary_image_id']) && $subcategory['primary_image_id'] > 0 ) {
                    $page['blocks'][] = array('type'=>'image', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$subcategory['primary_image_id'],
                        'title'=>$subcategory['name'], 'caption'=>'');
                }
                if( isset($subcategory['description']) && $subcategory['description'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$subcategory['description']);
                } elseif( isset($subcategory['synopsis']) && $subcategory['synopsis'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$subcategory['synopsis']);
                }
                //
                // Get the list of products with their prices and audio samples.
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'web', 'processRequestProductsDetails');
                $rc = ciniki_products_web_processRequestProductsDetails($ciniki, $settings, $business_id, $products, 
                    array('audio'=>'yes', 'prices'=>'yes', 'object_defs'=>$object_defs));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page['blocks'][] = array('type'=>'audiopricelist', 'section'=>'products', 'title'=>'Products', 'base_url'=>$base_url, 'list'=>$rc['products']);
                
            } elseif( $subcategory_display == 'image-description-audio-prices' ) {
                if( isset($subcategory['primary_image_id']) && $subcategory['primary_image_id'] > 0 ) {
                    $page['blocks'][] = array('type'=>'image', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$subcategory['primary_image_id'],
                        'title'=>$subcategory['name'], 'caption'=>'');
                }
                if( isset($subcategory['description']) && $subcategory['description'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$subcategory['description']);
                } elseif( isset($subcategory['synopsis']) && $subcategory['synopsis'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$subcategory['synopsis']);
                }
                //
                // Get the list of audio samples from products, remove any products that don't have audio
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'web', 'processRequestProductsDetails');
                $rc = ciniki_products_web_processRequestProductsDetails($ciniki, $settings, $business_id, $products, 
                    array('audio'=>'required', 'object_defs'=>$object_defs));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['products']) ) {
                    $page['blocks'][] = array('type'=>'audiopricelist', 'section'=>'products', 'title'=>'Samples', 'list'=>$rc['products']);
                }
                
                //
                // Get the list of products and their prices
                //
                $rc = ciniki_products_web_processRequestProductsDetails($ciniki, $settings, $business_id, $products, 
                    array('prices'=>'required', 'object_defs'=>$object_defs));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['products']) && count($rc['products']) > 0 ) {
                    $page['blocks'][] = array('type'=>'pricelist', 'section'=>'ordering', 'title'=>'Order', 'list'=>$rc['products']);
                }
                
            } else {
                if( isset($subcategory['primary_image_id']) && $subcategory['primary_image_id'] > 0 ) {
                    $page['blocks'][] = array('type'=>'image', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$subcategory['primary_image_id'],
                        'title'=>$subcategory['name'], 'caption'=>'');
                }
                if( isset($subcategory['description']) && $subcategory['description'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$subcategory['description']);
                } elseif( isset($subcategory['synopsis']) && $subcategory['synopsis'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$subcategory['synopsis']);
                }
                //
                // Get the list of products for this subcategory
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'web', 'processRequestProductsDetails');
                $rc = ciniki_products_web_processRequestProductsDetails($ciniki, $settings, $business_id, $products, 
                    array('image'=>'yes', 'prices'=>'yes', 'object_defs'=>$object_defs));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page['blocks'][] = array('type'=>'imagelist', 'section'=>'imageproductlist', 'prices'=>'yes', 'noimage'=>'yes', 'title'=>'', 'base_url'=>$base_url, 'list'=>$rc['products']);
            }
        } elseif( $display == 'categoryproducts' ) {
            // FIXME: Add query for category products
        } else {
            $page['blocks'][] = array('type'=>'imagelist', 'section'=>'imageproductlist', 'prices'=>'yes', 'noimage'=>'yes', 'title'=>'', 'base_url'=>$base_url, 'list'=>$rc['products']);
        }

    }

    //
    // Display a product
    //
    elseif( $display == 'product' ) {
        //
        // Get the product information
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'products', 'web', 'productDetails');
        $rc = ciniki_products_web_productDetails($ciniki, $settings, $ciniki['request']['business_id'], 
            array('product_permalink'=>$product_permalink));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $product = $rc['product'];
        $page['title'] = $product['name'];
        $page['breadcrumbs'][] = array('name'=>$product['name'], 'url'=>$base_url . '/' . $product['permalink']);
        
//      $ciniki['response']['head']['links'][] = array('rel'=>'canonical', 
//        'href'=>$ciniki['request']['domain_base_url'] . '/products/product/' . $product_permalink
//         );
//        $ciniki['response']['head']['og']['url'] .= '/' . $product_permalink;
        $ciniki['response']['head']['og']['url'] = $ciniki['request']['domain_base_url'] . $base_url . '/' . $product_permalink;
        $ciniki['response']['head']['og']['description'] = strip_tags($product['synopsis']);


        if( $product_display == 'image-audio-description-subcategorylist' ) {
            if( isset($product['image_id']) && $product['image_id'] > 0 ) {
                $page['blocks'][] = array('type'=>'image', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$product['image_id'],
                    'title'=>$product['name'], 'caption'=>'');
            }
            if( isset($product['audio']) && count($product['audio']) > 0 ) {
                $page['blocks'][] = array('type'=>'audiolist', 'section'=>'audio', 'audio'=>$product['audio']);
            }
            if( isset($product['description']) && $product['description'] != '' ) {
                $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$product['description']);
            } elseif( isset($product['synopsis']) && $product['synopsis'] != '' ) {
                $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$product['synopsis']);
            }
            
            if( isset($product['prices']) && count($product['prices']) > 0 ) {
                $page['blocks'][] = array('type'=>'prices', 'section'=>'prices', 'prices'=>$product['prices']);
            }
            if( isset($product['files']) && count($product['files']) > 0 ) {
                // FIXME: Add files
            }
            
            // FIXME: Add similar products
            // FIXME: Add recipes
            // Add share buttons
            if( !isset($settings['page-products-share-buttons']) || $settings['page-products-share-buttons'] == 'yes' ) {
                $page['blocks'][] = array('type'=>'sharebuttons', 'section'=>'share', 'pagetitle'=>$product['name'], 'tags'=>array());
            }

        } else {
            //
            // Check if image requested
            //
            if( isset($ciniki['request']['uri_split'][4]) && $ciniki['request']['uri_split'][4] == 'gallery'
                && isset($ciniki['request']['uri_split'][5]) && $ciniki['request']['uri_split'][5] != '' 
                ) {
                $image_permalink = $ciniki['request']['uri_split'][5];
                $ciniki['response']['head']['links']['canonical']['href'] .= '/gallery/' . $image_permalink;
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processGalleryImage');
                $rc = ciniki_web_processGalleryImage($ciniki, $settings, $ciniki['request']['business_id'],
                    array('item'=>$product,
                        'gallery_url'=>$ciniki['request']['base_url'] . "/products/category/$category_permalink/product/$product_permalink/gallery",
                        'article_title'=>$article_title .= " - <a href='" . $ciniki['request']['base_url'] 
                            . "/products/category/$category_permalink/product/$product_permalink'>" . $product['name'] . "</a>",
                        'image_permalink'=>$image_permalink,
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page['blocks'][] = array('type'=>'content', 'html'=>$rc['content']);
            } 
       
            //
            // Display the product
            //
            else {
                if( isset($product['image_id']) && $product['image_id'] > 0 ) {
                    $page['blocks'][] = array('type'=>'image', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$product['image_id'],
                        'title'=>$product['name'], 'caption'=>'');
                }
                if( isset($product['description']) && $product['description'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$product['description']);
                } elseif( isset($product['synopsis']) && $product['synopsis'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$product['synopsis']);
                }
                
                if( isset($product['audio']) && count($product['audio']) > 0 ) {
                    $page['blocks'][] = array('type'=>'audiolist', 'section'=>'audio', 'title'=>'Sample' . (count($product['audio'])>1?'s':''), 'audio'=>$product['audio']);
                }
                if( isset($product['prices']) && count($product['prices']) > 0 ) {
                    $page['blocks'][] = array('type'=>'prices', 'section'=>'prices', 'prices'=>$product['prices']);
                }
                if( isset($product['files']) && count($product['files']) > 0 ) {
                    // FIXME: Add files
                }
                
                // FIXME: Add similar products
                // FIXME: Add recipes
                // Add share buttons
                if( !isset($settings['page-products-share-buttons']) || $settings['page-products-share-buttons'] == 'yes' ) {
                    $page['blocks'][] = array('type'=>'sharebuttons', 'section'=>'share', 'pagetitle'=>$product['name'], 'tags'=>array());
                }
            }
            
        }
    }

//    $page['blocks'][] = array('type'=>'content', 'content'=>$display);
//    $page['blocks'][] = array('type'=>'content', 'html'=>"<pre>" . print_r($page, true) . "</pre>");
/*        $page['blocks'][] = array('type'=>'content', 'html'=>"<pre>" . print_r($object_defs, true) . "</pre>");

    if( isset($category) ) {
        $page['blocks'][] = array('type'=>'content', 'html'=>"<pre>" . print_r($category, true) . "</pre>");
    }

    if( isset($subcategory) ) {
        $page['blocks'][] = array('type'=>'content', 'html'=>"<pre>" . print_r($subcategory, true) . "</pre>");
    }
*/

    return array('stat'=>'ok', 'page'=>$page);
}
?>
