//
// This is the main UI for a product
//
function ciniki_products_product() {
	this.init = function() {
		//
		// The product panel
		//
		this.product = new M.panel('Product',
			'ciniki_products_product', 'product',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.products.product.product');
		this.product.data = {};
		this.product.product_id = 0;
		this.product.sections = {
			'_image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
				}},
			'info':{'label':'', 'aside':'yes', 'list':{
				'type_text':{'label':'Type'},
				'name':{'label':'Name'},
				'category':{'label':'Category'},
				'status_text':{'label':'Status'},
//				'barcode':{'label':'Barcode', 'visible':'no'},
				'price':{'label':'Price'},
				'cost':{'label':'Cost'},
				'wine_type':{'label':'Wine Type', 'visible':'no'},
				'kit_length':{'label':'Kit Length', 'visible':'no'},
				'winekit_oak':{'label':'Oak', 'visible':'no'},
				'winekit_body':{'label':'Body', 'visible':'no'},
				'winekit_sweetness':{'label':'Sweetness', 'visible':'no'},
				'webvisible':{'label':'Web', 'visible':'yes'},
				}},
			'supplier':{'label':'Supplier', 'aside':'yes', 'list':{
				'supplier_name':{'label':'Name',},
				'supplier_item_number':{'label':'Item #', 'visible':'no'},
				'supplier_minimum_order':{'label':'Minimum Order', 'visible':'no'},
				'supplier_order_multiple':{'label':'Order Multiple', 'visible':'no'},
				}},
			'short_description':{'label':'Description', 'type':'htmlcontent'},
			'long_description':{'label':'Full Description', 'type':'htmlcontent'},
			'files':{'label':'Files', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline'],
				'noData':'No product files',
				'addTxt':'Add File',
				'addFn':'M.startApp(\'ciniki.products.files\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'product_id\':M.ciniki_products_product.product.product_id,\'add\':\'yes\'});',
				},
			'images':{'label':'Gallery', 'type':'simplethumbs'},
			'_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Additional Image',
				'addFn':'M.startApp(\'ciniki.products.images\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'product_id\':M.ciniki_products_product.product.product_id,\'add\':\'yes\'});',
				},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'product_id\':M.ciniki_products_product.product.product_id});'},
				}},
		};
		this.product.addDropImage = function(iid) {
			var rsp = M.api.getJSON('ciniki.products.imageAdd',
				{'business_id':M.curBusinessID, 'image_id':iid, 'product_id':M.ciniki_products_product.product.product_id});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			return true;
		};
		this.product.sectionData = function(s) {
			if( s == 'info' || s == 'supplier' ) { return this.sections[s].list; }
			if( s == 'short_description' || s == 'long_description' ) { return this.data[s].replace(/\n/g, '<br/>'); }
			return this.data[s];
		};
		this.product.addDropImageRefresh = function() {
			if( M.ciniki_products_product.product.product_id > 0 ) {
				var rsp = M.api.getJSONCb('ciniki.products.productGet', {'business_id':M.curBusinessID, 
					'product_id':M.ciniki_products_product.product.product_id, 'images':'yes'}, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						var p = M.ciniki_products_product.product;
						p.data.images = rsp.product.images;
						p.refreshSection('images');
					});
			}
		};
		this.product.listLabel = function(s, i, d) { return d.label; }
		this.product.listValue = function(s, i, d) {
			return this.data[i];
		};
		this.product.fieldValue = function(s, i, d) {
			return this.data[i];
		};
		this.product.cellValue = function(s, i, j, d) {
			if( s == 'files' && j == 0 ) {
				return '<span class="maintext">' + d.file.name + '</span>';
			}
		};
		this.product.rowFn = function(s, i, d) {
			if( s == 'files' ) {
				return 'M.startApp(\'ciniki.products.files\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'file_id\':\'' + d.file.id + '\'});';
			}
		};
		this.product.thumbSrc = function(s, i, d) {
			if( d.image.image_data != null && d.image.image_data != '' ) {
				return d.image.image_data;
			} else {
				return '/ciniki-manage-themes/default/img/noimage_75.jpg';
			}
		};
		this.product.thumbTitle = function(s, i, d) {
			if( d.image.name != null ) { return d.image.name; }
			return '';
		};
		this.product.thumbID = function(s, i, d) {
			if( d.image.id != null ) { return d.image.id; }
			return 0;
		};
		this.product.thumbFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.products.images\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'product_image_id\':\'' + d.image.id + '\'});';
		};
		this.product.addButton('edit', 'Edit', 'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'product_id\':M.ciniki_products_product.product.product_id});');
		this.product.addClose('Back');
	};

	this.start = function(cb, aP, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }
		var aC = M.createContainer(aP, 'ciniki_products_product', 'yes');
		if( aC == null ) {
			alert('App Error');
			return false;
		}

		if( args.product_id != null && args.product_id > 0 ) {
			this.showProduct(cb, args.product_id);
		}
	}

	this.showProduct = function(cb, pid) {
		this.product.reset();
		if( pid != null ) { this.product.product_id = pid; }
		M.api.getJSONCb('ciniki.products.productGet', {'business_id':M.curBusinessID,
			'product_id':this.product.product_id, 'files':'yes', 'images':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_products_product.product;
				p.data = rsp.product;
				var fields = ['wine_type', 'kit_length', 'winekit_oak', 'winekit_body', 'winekit_sweetness'];
				for(i in fields) {
					p.sections.info.list[fields[i]].visible=rsp.product[fields[i]]!=null&&rsp.product[fields[i]]!=''?'yes':'no';
				}
				p.sections.supplier.list['supplier_item_number'].visible=rsp.product['supplier_item_number']!=null&&rsp.product['supplier_item_number']!=''?'yes':'no';
				p.sections.supplier.list['supplier_minimum_order'].visible=rsp.product['supplier_minimum_order']!=null&&rsp.product['supplier_minimum_order']>1?'yes':'no';
				p.sections.supplier.list['supplier_order_multiple'].visible=rsp.product['supplier_order_multiple']!=null&&rsp.product['supplier_order_multiple']>1?'yes':'no';
				p.refresh();
				p.show(cb);
			});
	};
}
