//
function ciniki_products_types() {
	//
	// Panels
	//
	this.main = null;

	this.cb = null;
	this.fieldOptions = {'off':'Off', 'on':'On'};
	this.statusOptions = {'10':'Active', '60':'Inactive'};
	this.subscriptionOptions = {'off':'Unsubscribed', 'on':'Subscribed'};

	this.init = function() {
		//
		// The main panel, which lists the options for production
		//
		this.menu = new M.panel('Products',
			'ciniki_products_types', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.products.types.menu');
		this.menu.sections = {
			'types':{'label':'Product Types', 'type':'simplegrid', 'num_cols':2,
				'headerValues':['Type','Status'],
				'addTxt':'Add',
				'addFn':'M.ciniki_products_types.editType(\'M.ciniki_products_types.showMenu();\',0);',
				},
			};
		this.menu.sectionData = function(s) { return this.data[s]; }
		this.menu.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.type.name_s;
				case 1: return d.type.status_text;
			}
		};
		this.menu.rowFn = function(s, i, d) {
				return 'M.ciniki_products_types.editType(\'M.ciniki_products_types.showMenu();\',\'' + d.type.id + '\');';
		};
		this.menu.addButton('add', 'Add', 'M.ciniki_products_types.editType(\'M.ciniki_products_types.showMenu();\',0);');
		this.menu.addClose('Back');

		//
		// The edit panel to add/update a type
		//
		this.edit = new M.panel('Product Type',
			'ciniki_products_types', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.products.types.edit');
		this.edit.type_id = 0;
		this.edit.data = {};
		this.edit.sections = {
			'info':{'label':'', 'aside':'yes', 'fields':{
				'name_s':{'label':'Name', 'type':'text'},
				'name_p':{'label':'Plural', 'type':'text'},
				'status':{'label':'Status', 'type':'toggle', 'default':'10', 'toggles':this.statusOptions},
			}},
			'parent_products':{'label':'Parent', 'aside':'yes', 'fields':{
				'parent_product_name':{'label':'Name', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_code':{'label':'Code', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_category':{'label':'Category', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_source':{'label':'Source', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_flags':{'label':'Flags', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_status':{'label':'Status', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_barcode':{'label':'Barcode', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
//				'parent_product_supplier_business_id':{'label':'Supplier Business ID', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
//				'parent_product_supplier_product_id':{'label':'Supplier Product ID', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_price':{'label':'Price', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_unit_discount_amount':{'label':'Unit Discount Amount', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_unit_discount_percentage':{'label':'Unit Discount Percentage', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_taxtype_id':{'label':'Taxtype', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_cost':{'label':'Cost', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_msrp':{'label':'MSRP', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_supplier_id':{'label':'Supplier ID', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_supplier_item_number':{'label':'Supplier Item Number', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_supplier_minimum_order':{'label':'Supplier Minimum Order', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_supplier_order_multiple':{'label':'Supplier Order Multiple', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_manufacture_min_time':{'label':'Manufacture Min Time', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_manufacture_max_time':{'label':'Manufacture Max Time', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_inventory_flags':{'label':'Inventory Flags', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_inventory_current_num':{'label':'Inventory Current Num', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_shipping_flags':{'label':'Shipping Options', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_shipping_weight':{'label':'Shipping Weight', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_shipping_weight_units':{'label':'Shipping Weight Units', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_shipping_length':{'label':'Shipping Length', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_shipping_width':{'label':'Shipping Width', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_shipping_height':{'label':'Shipping Height', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_shipping_size_units':{'label':'Shipping Size Units', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_primary_image_id':{'label':'Primary Image', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_short_description':{'label':'Short Description', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_long_description':{'label':'Long Description', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_start_date':{'label':'Start Date', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_end_date':{'label':'End Date', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_webflags':{'label':'Webflags', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				}},
			'parent_product_details':{'label':'Parent Details', 'aside':'yes', 'fields':{
				'parent_product_detail01':{'label':'Details 1', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_detail01-name':{'label':'Name 1', 'type':'text'},
				'parent_product_detail02':{'label':'Details 2', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_detail02-name':{'label':'Name 2', 'type':'text'},
				'parent_product_detail03':{'label':'Details 3', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_detail03-name':{'label':'Name 3', 'type':'text'},
				'parent_product_detail04':{'label':'Details 4', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_detail04-name':{'label':'Name 4', 'type':'text'},
				'parent_product_detail05':{'label':'Details 5', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_detail05-name':{'label':'Name 5', 'type':'text'},
				'parent_product_detail06':{'label':'Details 6', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_detail06-name':{'label':'Name 6', 'type':'text'},
				'parent_product_detail07':{'label':'Details 7', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_detail07-name':{'label':'Name 7', 'type':'text'},
				'parent_product_detail08':{'label':'Details 8', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_detail08-name':{'label':'Name 8', 'type':'text'},
				'parent_product_detail09':{'label':'Details 9', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_product_detail09-name':{'label':'Name 9', 'type':'text'},
				}},
			'parent_other':{'label':'Parent Images', 'aside':'yes', 'fields':{
				'parent_categories':{'label':'Categories', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_subcategories-11':{'label':'Sub-Categories', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_subcategories-11-sname':{'label':'Single Name', 'type':'text'},
				'parent_subcategories-11-pname':{'label':'Plural Name', 'type':'text'},
				'parent_subcategories-12':{'label':'Sub-Categories', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_subcategories-12-sname':{'label':'Single Name', 'type':'text'},
				'parent_subcategories-12-pname':{'label':'Plural Name', 'type':'text'},
				'parent_subcategories-13':{'label':'Sub-Categories', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_subcategories-13-sname':{'label':'Single Name', 'type':'text'},
				'parent_subcategories-13-pname':{'label':'Plural Name', 'type':'text'},
				'parent_subcategories-14':{'label':'Sub-Categories', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_subcategories-14-sname':{'label':'Single Name', 'type':'text'},
				'parent_subcategories-14-pname':{'label':'Plural Name', 'type':'text'},
				'parent_subcategories-15':{'label':'Sub-Categories', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_subcategories-15-sname':{'label':'Single Name', 'type':'text'},
				'parent_subcategories-15-pname':{'label':'Plural Name', 'type':'text'},
				'parent_tags':{'label':'Tags', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_images':{'label':'Images', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_files':{'label':'Files', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_similar':{'label':'Similar', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_recipes':{'label':'Recipes', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				}},
			'parent_pricing':{'label':'Parent Pricing', 'aside':'yes', 'fields':{
				'parent_price_name':{'label':'Name', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_price_available_to':{'label':'Available To', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_price_min_quantity':{'label':'Min Quantity', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_price_unit_amount':{'label':'Amount', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_price_unit_discount_amount':{'label':'Discount Amount', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_price_unit_discount_percentage':{'label':'Discount Amount Percentage', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_price_taxtype_id':{'label':'Taxtype', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_price_start_date':{'label':'Start Date', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_price_end_date':{'label':'End Date', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'parent_price_webflags':{'label':'Web Flags', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				}},
			'child_products':{'label':'Child', 'fields':{
				'child_product_name':{'label':'Name', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
//				'child_product_category':{'label':'Category', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_source':{'label':'Source', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_flags':{'label':'Flags', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_status':{'label':'Status', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_barcode':{'label':'Barcode', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
//				'child_product_supplier_business_id':{'label':'Supplier Business ID', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
//				'child_product_supplier_product_id':{'label':'Supplier Product ID', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_price':{'label':'Price', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_unit_discount_amount':{'label':'Unit Discount Amount', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_unit_discount_percentage':{'label':'Unit Discount Percentage', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_taxtype_id':{'label':'Taxtype', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_cost':{'label':'Cost', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_msrp':{'label':'MSRP', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_supplier_id':{'label':'Supplier ID', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_supplier_item_number':{'label':'Supplier Item Number', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_supplier_minimum_order':{'label':'Supplier Minimum Order', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_supplier_order_multiple':{'label':'Supplier Order Multiple', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_manufacture_min_time':{'label':'Manufacture Min Time', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_manufacture_max_time':{'label':'Manufacture Max Time', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_inventory_flags':{'label':'Inventory Flags', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_inventory_current_num':{'label':'Inventory Current Num', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_shipping_flags':{'label':'Shipping Options', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_shipping_weight':{'label':'Shipping Weight', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_shipping_weight_units':{'label':'Shipping Weight Units', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_shipping_length':{'label':'Shipping Length', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_shipping_width':{'label':'Shipping Width', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_shipping_height':{'label':'Shipping Height', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_shipping_size_units':{'label':'Shipping Size Units', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_primary_image_id':{'label':'Primary Image', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_short_description':{'label':'Short Description', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_long_description':{'label':'Long Description', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_start_date':{'label':'Start Date', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_end_date':{'label':'End Date', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_webflags':{'label':'Webflags', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				}},
			'child_product_details':{'label':'Child Details', 'fields':{
				'child_product_detail01':{'label':'Details 1', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_detail01-name':{'label':'Name 1', 'type':'text'},
				'child_product_detail02':{'label':'Details 2', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_detail02-name':{'label':'Name 2', 'type':'text'},
				'child_product_detail03':{'label':'Details 3', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_detail03-name':{'label':'Name 3', 'type':'text'},
				'child_product_detail04':{'label':'Details 4', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_detail04-name':{'label':'Name 4', 'type':'text'},
				'child_product_detail05':{'label':'Details 5', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_detail05-name':{'label':'Name 5', 'type':'text'},
				'child_product_detail06':{'label':'Details 6', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_detail06-name':{'label':'Name 6', 'type':'text'},
				'child_product_detail07':{'label':'Details 7', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_detail07-name':{'label':'Name 7', 'type':'text'},
				'child_product_detail08':{'label':'Details 8', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_detail08-name':{'label':'Name 8', 'type':'text'},
				'child_product_detail09':{'label':'Details 9', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_product_detail09-name':{'label':'Name 9', 'type':'text'},
				}},
			'child_other':{'label':'Child Images', 'fields':{
				'child_images':{'label':'Images', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_files':{'label':'Files', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_similar':{'label':'Similar', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_recipes':{'label':'Recipes', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				}},
			'child_pricing':{'label':'Child Pricing', 'fields':{
				'child_price_name':{'label':'Name', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_price_available_to':{'label':'Available To', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_price_min_quantity':{'label':'Min Quantity', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_price_unit_amount':{'label':'Amount', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_price_unit_discount_amount':{'label':'Discount Amount', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_price_unit_discount_percentage':{'label':'Discount Amount Percentage', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_price_taxtype_id':{'label':'Taxtype', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_price_start_date':{'label':'Start Date', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_price_end_date':{'label':'End Date', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				'child_price_webflags':{'label':'Web Flags', 'type':'toggle', 'default':'off', 'toggles':this.fieldOptions},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_products_types.saveType();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_products_types.deleteType();'},
				}},
		};
		this.edit.fieldValue = function(s, i, d) { 
			if( this.data == null || this.data[i] == null ) { return ''; }
			return this.data[i]; 
		}
		this.edit.addButton('save', 'Save', 'M.ciniki_products_types.saveType();');
		this.edit.addClose('Back');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_products_types', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.showMenu(cb);
	}

	//
	// Grab the stats for the business from the database and present the list of products.
	//
	this.showMenu = function(cb) {
		M.api.getJSONCb('ciniki.products.typeList', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_products_types.menu;
				p.data = {'types':rsp.types};
				p.refresh();
				p.show(cb);
			});
	};

	this.editType = function(cb, tid) {
		if( tid != null ) { this.edit.type_id = tid; }
		if( this.edit.type_id > 0 ) {
			M.api.getJSONCb('ciniki.products.typeGet', {'business_id':M.curBusinessID,
				'type_id':this.edit.type_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_products_types.edit;
					p.data = rsp.type;
					p.refresh();
					p.show(cb);
				});
			
		} else {
			this.edit.reset();
			this.edit.data = {};
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.saveType = function() {
		if( this.edit.type_id > 0 ) {
			var c = this.edit.serializeForm('no');
			M.api.postJSONCb('ciniki.products.typeUpdate', {'business_id':M.curBusinessID,
				'type_id':this.edit.type_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_products_types.edit.close();
				});
		} else {
			var c = this.edit.serializeForm('yes');
			M.api.postJSONCb('ciniki.products.typeAdd', {'business_id':M.curBusinessID,
				'type_id':this.edit.type_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_products_types.edit.close();
				});
		}
	};

	this.deleteType = function() {
		if( confirm("Are you sure you want to remove this type?") ) {
			var rsp = M.api.getJSONCb('ciniki.products.typeDelete', 
				{'business_id':M.curBusinessID, 'type_id':this.edit.type_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_products_types.edit.close();
				});
		}
	};
}
