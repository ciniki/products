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
            'parent_id'=>array('ref'=>'ciniki.products.product', 'default'=>0),
            'name'=>array('name'=>'Name'),
            'code'=>array('default'=>''),
            'type_id'=>array(),
//          'type'=>array(),
            'category'=>array('default'=>''),
            'permalink'=>array(),
            'sequence'=>array('name'=>'Sequence', 'default'=>'1'),
            'source'=>array('default'=>''),
            'flags'=>array(),
            'status'=>array(),
            'barcode'=>array('default'=>''),
            'supplier_id'=>array('ref'=>'ciniki.products.supplier', 'default'=>0),
            'supplier_product_id'=>array('default'=>''),
            'supplier_item_number'=>array('default'=>''),
            'supplier_minimum_order'=>array('default'=>''),
            'supplier_order_multiple'=>array('default'=>''),
            'manufacture_min_time'=>array('default'=>''),
            'manufacture_max_time'=>array('default'=>''),
            'inventory_flags'=>array('default'=>0),
            'inventory_current_num'=>array('default'=>''),
            'inventory_reorder_num'=>array('default'=>''),
            'inventory_reorder_quantity'=>array('default'=>''),
            'shipping_flags'=>array('default'=>0),
            'shipping_weight'=>array('default'=>''),
            'shipping_weight_units'=>array('default'=>''),
            'shipping_length'=>array('default'=>''),
            'shipping_width'=>array('default'=>''),
            'shipping_height'=>array('default'=>''),
            'shipping_size_units'=>array('default'=>'0'),
            'price'=>array('default'=>'0'),
            'unit_discount_amount'=>array('default'=>'0'),
            'unit_discount_percentage'=>array('default'=>'0'),
            'taxtype_id'=>array('default'=>'0'),
            'cost'=>array('default'=>''),
            'msrp'=>array('default'=>''),
            'sell_unit'=>array('default'=>''),
            'primary_image_id'=>array('ref'=>'ciniki.images.image', 'default'=>''),
            'short_description'=>array('default'=>''),
            'long_description'=>array('default'=>''),
            'start_date'=>array('default'=>''),
            'end_date'=>array('default'=>''),
            'webflags'=>array('default'=>0),
            'detail01'=>array('default'=>''),
            'detail02'=>array('default'=>''),
            'detail03'=>array('default'=>''),
            'detail04'=>array('default'=>''),
            'detail05'=>array('default'=>''),
            'detail06'=>array('default'=>''),
            'detail07'=>array('default'=>''),
            'detail08'=>array('default'=>''),
            'detail09'=>array('default'=>''),
            ),
        'details'=>array('key'=>'product_id', 'table'=>'ciniki_product_details'),
        'history_table'=>'ciniki_product_history',
        'history_notes'=>'yes',
        );
    $objects['tag'] = array(
        'name'=>'Tag',
        'sync'=>'yes',
        'table'=>'ciniki_product_tags',
        'fields'=>array(
            'product_id'=>array('ref'=>'ciniki.products.product'),
            'tag_type'=>array(),
            'tag_name'=>array(),
            'permalink'=>array(),
            ),
        'history_table'=>'ciniki_product_history',
        );
    $objects['category'] = array(
        'name'=>'Category',
        'sync'=>'yes',
        'table'=>'ciniki_product_categories',
        'fields'=>array(
            'category'=>array(),
            'subcategory'=>array(),
            'name'=>array('name'=>'Sub Name', 'default'=>''),
            'subname'=>array(),
            'sequence'=>array(),
            'tag_type'=>array('name'=>'Tag Type', 'default'=>'0'),
            'display'=>array('name'=>'Category Format', 'default'=>''),
            'subcategorydisplay'=>array('name'=>'Sub Category Format', 'default'=>''),
            'productdisplay'=>array('name'=>'Product Format', 'default'=>''),
            'primary_image_id'=>array(),
            'synopsis'=>array(),
            'description'=>array(),
            'webflags'=>array(),
            ),
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
            'sequence'=>array(),
            'permalink'=>array(),
            'webflags'=>array(),
            'image_id'=>array('ref'=>'ciniki.images.image'),
            'description'=>array(),
            ),
        'history_table'=>'ciniki_product_history',
        );
    $objects['audio'] = array(
        'name'=>'Audio',
        'sync'=>'yes',
        'table'=>'ciniki_product_audio',
        'fields'=>array(
            'product_id'=>array('ref'=>'ciniki.products.product'),
            'name'=>array(),
            'permalink'=>array(),
            'sequence'=>array('default'=>'1'),
            'webflags'=>array('default'=>'0'),
            'mp3_audio_id'=>array('default'=>'0', 'ref'=>'ciniki.audio.file'),
            'wav_audio_id'=>array('default'=>'0', 'ref'=>'ciniki.audio.file'),
            'ogg_audio_id'=>array('default'=>'0', 'ref'=>'ciniki.audio.file'),
            'description'=>array('default'=>''),
            ),
        'history_table'=>'ciniki_product_history',
        );
    $objects['price'] = array(
        'name'=>'Price',
        'sync'=>'yes',
        'table'=>'ciniki_product_prices',
        'fields'=>array(
            'name'=>array('name'=>'Name'),
            'product_id'=>array('name'=>'Product', 'ref'=>'ciniki.products.product'),
            'pricepoint_id'=>array('name'=>'Price Point', 'ref'=>'ciniki.customers.pricepoint'),
            'available_to'=>array('name'=>'Available to'),
            'min_quantity'=>array('name'=>'Minimum Quantity'),
            'unit_amount'=>array('name'=>'Unit Amount'),
            'unit_discount_amount'=>array('name'=>'Unit Discount Amount'),
            'unit_discount_percentage'=>array('name'=>'Unit Discount Percentage'),
            'taxtype_id'=>array('name'=>'Tax Type'),
            'start_date'=>array('name'=>'Start Date'),
            'end_date'=>array('name'=>'End Date'),
            'webflags'=>array('name'=>'Web Options'),
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
    $objects['type'] = array(
        'name'=>'Product Type',
        'sync'=>'yes',
        'table'=>'ciniki_product_types',
        'fields'=>array(
            'status'=>array(),
            'name_s'=>array(),
            'name_p'=>array(),
            'object_def'=>array(),
            ),
        'history_table'=>'ciniki_product_history',
        );
    $objects['pdfcatalog'] = array(
        'name'=>'PDF Catalog',
        'o_name'=>'catalog',
        'o_container'=>'catalogs',
        'sync'=>'yes',
        'table'=>'ciniki_product_pdfcatalogs',
        'fields'=>array(
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'sequence'=>array('name'=>'Sequence', 'default'=>'0'),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'num_pages'=>array('name'=>'Number of Pages', 'default'=>'0'),
            'primary_image_id'=>array('name'=>'Image', 'default'=>'0'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_product_history',
        );
    $objects['pdfcatalogimage'] = array(
        'name'=>'PDF Catalog Images',
        'o_name'=>'image',
        'o_container'=>'images',
        'sync'=>'yes',
        'table'=>'ciniki_product_pdfcatalog_images',
        'fields'=>array(
            'catalog_id'=>array('name'=>'Catalog'),
            'page_number'=>array('name'=>'Page Number'),
            'image_id'=>array('name'=>'Image'),
            ),
        'history_table'=>'ciniki_product_history',
        );
    $objects['setting'] = array(
        'type'=>'settings',
        'name'=>'Product Settings',
        'table'=>'ciniki_products_settings',
        'history_table'=>'ciniki_product_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
