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
				'type_text':{'label':'Type', 'visible':'no'},
				'name':{'label':'Name', 'visible':'no'},
				'category':{'label':'Category', 'visible':'no'},
				'status_text':{'label':'Status', 'visible':'no'},
//				'barcode':{'label':'Barcode', 'visible':'no'},
				'price':{'label':'Price', 'visible':'no'},
				'cost':{'label':'Cost', 'visible':'no'},
				'webflags_text':{'label':'Web', 'visible':'yes'},
				'manufacture_times':{'label':'Manufacture Time', 'visible':'no'},
				'inventory_current_num':{'label':'Inventory', 'visible':'no'},
				'detail01':{'label':'', 'visible':'no'},
				'detail02':{'label':'', 'visible':'no'},
				'detail03':{'label':'', 'visible':'no'},
				'detail04':{'label':'', 'visible':'no'},
				'detail05':{'label':'', 'visible':'no'},
				'detail06':{'label':'', 'visible':'no'},
				'detail07':{'label':'', 'visible':'no'},
				'detail08':{'label':'', 'visible':'no'},
				'detail09':{'label':'', 'visible':'no'},
				}},
			'supplier':{'label':'Supplier', 'aside':'yes', 'visible':'no', 'list':{
				'supplier_name':{'label':'Name', 'visible':'no'},
				'supplier_item_number':{'label':'Item #', 'visible':'no'},
				'supplier_minimum_order':{'label':'Minimum Order', 'visible':'no'},
				'supplier_order_multiple':{'label':'Order Multiple', 'visible':'no'},
				}},
//			'inventory':{'label':'Inventory', 'aside':'yes', 'visible':'no', 'list':{
//				'inventory_current_num':{'label':'', 'visible':'no'},
//				}},
			'short_description':{'label':'Description', 'visible':'yes', 'type':'htmlcontent'},
			'long_description':{'label':'Full Description', 'visible':'yes', 'type':'htmlcontent'},
			'files':{'label':'Files', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline'],
				'noData':'No product files',
				'addTxt':'Add File',
				'addFn':'M.startApp(\'ciniki.products.files\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'product_id\':M.ciniki_products_product.product.product_id,\'add\':\'yes\'});',
				},
			'images':{'label':'Gallery', 'visible':'no', 'type':'simplethumbs'},
			'_images':{'label':'', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Additional Image',
				'addFn':'M.startApp(\'ciniki.products.images\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'product_id\':M.ciniki_products_product.product.product_id,\'add\':\'yes\'});',
				},
			'similar':{'label':'Similar Products', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add similar product',
				'addFn':'M.startApp(\'ciniki.products.relationships\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'product_id\':M.ciniki_products_product.product.product_id});',
				},
			'recipes':{'label':'Recommended Recipes', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add recipe',
				'addFn':'M.startApp(\'ciniki.products.recipes\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'product_id\':M.ciniki_products_product.product.product_id});',
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
			if( s == 'similar' && j == 0 ) {
				return d.product.name;
			}
			if( s == 'recipes' && j == 0 ) {
				return d.recipe.name;
			}
		};
		this.product.rowFn = function(s, i, d) {	
			if( s == 'files' ) {
				return 'M.startApp(\'ciniki.products.files\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'file_id\':\'' + d.file.id + '\'});';
			}
			if( s == 'similar' ) {
				return 'M.startApp(\'ciniki.products.relationships\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'product_id\':M.ciniki_products_product.product.product_id,\'relationship_id\':\'' + d.product.relationship_id + '\'});';
			}
			if( s == 'recipes' ) {
				return 'M.startApp(\'ciniki.products.recipes\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'product_id\':M.ciniki_products_product.product.product_id,\'ref_id\':\'' + d.recipe.ref_id + '\'});';
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
		this.product.sections.similar.visible=(M.curBusiness.modules['ciniki.products'].flags&0x01)==1?'yes':'no';
		this.product.sections.recipes.visible=(M.curBusiness.modules['ciniki.products'].flags&0x02)==2?'yes':'no';
		if( pid != null ) { this.product.product_id = pid; }
		M.api.getJSONCb('ciniki.products.productGet', {'business_id':M.curBusinessID,
			'product_id':this.product.product_id, 
			'files':'yes', 'images':'yes', 'similar':'yes', 'recipes':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_products_product.product;
				p.data = rsp.product;
				var object_def = eval('(' + rsp.product.object_def + ')');
				// Setup the visible fields
				for(i in p.sections.info.list) {
					if( object_def.parent.products[i] != null ) {
						p.sections.info.list[i].visible='yes';
						if( object_def.parent.products[i].name != null ) {
							p.sections.info.list[i].label = object_def.parent.products[i].name;
						}
					} else {
						p.sections.info.list[i].visible='no';
					}
				}
				for(i in p.sections.info.list) {
					p.sections.info.list[i].visible=(object_def.parent.products[i] != null?'yes':'no');
				}
				var nvis = 0;
				for(i in p.sections.supplier.list) {
					if( object_def.parent.products[i] != null ) {
						p.sections.supplier.list[i].visible='yes';
						nvis++;
					} else {
						p.sections.supplier.list[i].visible='no';
					}
				}
				console.log(nvis);
				p.sections.supplier.visible = (nvis==0?'no':'yes');
				p.sections.supplier.list.supplier_name.visible = (nvis==0?'no':'yes');
				p.sections.short_description.visible = (object_def.parent.products.short_description!=null?'yes':'no');
				p.sections.long_description.visible = (object_def.parent.products.long_description!=null?'yes':'no');
				p.sections._image.visible = (object_def.parent.products.primary_image_id!=null?'yes':'no');
				p.sections.info.visible = (object_def.parent.products.primary_image_id!=null?'yes':'no');
				p.sections.images.visible = (object_def.parent.images!=null?'yes':'no');
				p.sections._images.visible = (object_def.parent.images!=null?'yes':'no');
				p.sections.files.visible = (object_def.parent.files!=null?'yes':'no');
				p.sections.similar.visible = (object_def.parent.similar!=null?'yes':'no');
				p.sections.recipes.visible = (object_def.parent.recipes!=null?'yes':'no');
				p.refresh();
				p.show(cb);
			});
	};
}
