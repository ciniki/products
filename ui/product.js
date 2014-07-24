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
		this.product.type_id = 0;
		this.product.prevnext = {'prev_id':0, 'next_id':0, 'list':[]};
		this.product.sections = {
			'_image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
				}},
			'info':{'label':'', 'aside':'yes', 'list':{
				'type_text':{'label':'Type', 'visible':'no'},
				'name':{'label':'Name', 'visible':'no'},
				'code':{'label':'Code', 'visible':'no'},
				'category':{'label':'Category', 'visible':'no'},
				'categories':{'label':'Categories', 'visible':'no'},
				'subcategories-11':{'label':'Sub-Categories', 'visible':'no'},
				'subcategories-12':{'label':'Sub-Categories', 'visible':'no'},
				'subcategories-13':{'label':'Sub-Categories', 'visible':'no'},
				'subcategories-14':{'label':'Sub-Categories', 'visible':'no'},
				'subcategories-15':{'label':'Sub-Categories', 'visible':'no'},
				'tags':{'label':'Tags', 'visible':'no'},
				'status_text':{'label':'Status', 'visible':'no'},
//				'barcode':{'label':'Barcode', 'visible':'no'},
				'price':{'label':'Price', 'visible':'no'},
				'cost':{'label':'Cost', 'visible':'no'},
				'start_date':{'label':'Start', 'visible':'no'},
				'end_date':{'label':'End', 'visible':'no'},
				'webflags_text':{'label':'Web', 'visible':'yes'},
				'manufacture_times':{'label':'Manufacture Time', 'visible':'no'},
				'inventory_current_num':{'label':'Inventory', 'visible':'no'},
				'shipping_flags_text':{'label':'Shipping', 'visible':'no'},
				'shipping_package':{'label':'Shipping', 'visible':'no'},
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
			'prices':{'label':'Pricing', 'visible':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['','alignright'],
				'addTxt':'Add Pricing',
				'addFn':'M.startApp(\'ciniki.products.prices\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'product_id\':M.ciniki_products_product.product.product_id,\'price_id\':\'0\',\'type_id\':M.ciniki_products_product.product.data.type_id});',
				},
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
			if( s == 'prices' ) {
				if( j == 0 ) {
					var txt = '';
					if( M.curBusiness.modules['ciniki.customers'] != null 
						&& (M.curBusiness.modules['ciniki.customers'].flags&0x1000) ) {
						if( d.price.pricepoint_id == 0 ) {
							txt += 'None';
						} else {
							txt += (d.price.pricepoint_id_text==''?'Unknown':d.price.pricepoint_id_text);
						}
					}
					if( d.price.name != '' ) {
						if( txt != '' ) {
							txt += ' <span class="subdue">' + d.price.name + ' [' + d.price.available_to_text + ']</span>';
						} else {
							txt += d.price.name + ' <span class="subdue">' + d.price.available_to_text + '</span>';
							
						}
					} else {
						if( txt != '' ) {
							txt += ' <span class="subdue">' + d.price.available_to_text + '</span>';
						} else {
							txt += d.price.available_to_text;
						}
					}
					return txt;
				} else if( j == 1 ) {
					return d.price.unit_amount_display;
				}
			}
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
			if( s == 'prices' ) {
				return 'M.startApp(\'ciniki.products.prices\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'price_id\':\'' + d.price.id + '\',\'type_id\':M.ciniki_products_product.product.data.type_id});';
			}
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
				return '/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg';
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
		this.product.prevButtonFn = function() {
			if( this.prevnext.prev_id > 0 ) {
				return 'M.ciniki_products_product.showProduct(null,\'' + this.prevnext.prev_id + '\');';
			}
			return null;
		};
		this.product.nextButtonFn = function() {
			if( this.prevnext.next_id > 0 ) {
				return 'M.ciniki_products_product.showProduct(null,\'' + this.prevnext.next_id + '\');';
			}
			return null;
		};
		this.product.addButton('edit', 'Edit', 'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_product.showProduct();\',\'mc\',{\'product_id\':M.ciniki_products_product.product.product_id});');
		this.product.addButton('next', 'Next');
		this.product.addClose('Back');
		this.product.addLeftButton('prev', 'Prev');
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
			this.showProduct(cb, args.product_id, args.list);
		}
	}

	this.showProduct = function(cb, pid, list) {
		this.product.reset();
//		this.product.sections.similar.visible=(M.curBusiness.modules['ciniki.products'].flags&0x01)==1?'yes':'no';
//		this.product.sections.recipes.visible=(M.curBusiness.modules['ciniki.products'].flags&0x02)==2?'yes':'no';
		if( pid != null ) { this.product.product_id = pid; }
		if( list != null ) { this.product.prevnext.list = list; }
		M.api.getJSONCb('ciniki.products.productGet', {'business_id':M.curBusinessID,
			'product_id':this.product.product_id, 'prices':'yes',
			'files':'yes', 'images':'yes', 'similar':'yes', 'recipes':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_products_product.product;
				p.data = rsp.product;
				var object_def = eval('(' + rsp.product.object_def + ')');
				var pc_object_def = (rsp.product.parent_id==0?object_def.parent:object_def.child);
				// Setup the visible fields
				for(i in p.sections.info.list) {
					if( pc_object_def.products[i] != null ) {
						p.sections.info.list[i].visible='yes';
						if( pc_object_def.products[i].name != null ) {
							p.sections.info.list[i].label = pc_object_def.products[i].name;
						}
					} else {
						p.sections.info.list[i].visible='no';
					}
				}
				if( rsp.product.categories != null && rsp.product.categories != '' ) {
					p.data.categories = rsp.product.categories.replace(/::/g, ', ');
				}
				for(var i=11;i<16;i++) {
					if( rsp.product['subcategories-'+i] != null && rsp.product['subcategories-'+i] != '' ) {

						p.data['subcategories-'+i] = rsp.product['subcategories-'+i].replace(/::/g, ', ');
						p.sections.info.list['subcategories-'+i].visible = (pc_object_def['subcategories-'+i]!=null?'yes':'no');
						p.sections.info.list['subcategories-'+i].label = (pc_object_def['subcategories-'+i]['pname']?pc_object_def['subcategories-'+i]['pname']:'Sub-Categories');
					}
				}
				if( rsp.product.tags != null && rsp.product.tags != '' ) {
					p.data.tags = rsp.product.tags.replace(/::/g, ', ');
				}
				p.sections.info.list.categories.visible = (pc_object_def.categories!=null?'yes':'no');
				p.sections.info.list.tags.visible = (pc_object_def.tags!=null?'yes':'no');
				if( pc_object_def.products['status'] != null ) {
					p.sections.info.list['status_text'].visible = 'yes';
				}
				if( pc_object_def.products['webflags'] != null ) {
					p.sections.info.list['webflags_text'].visible = 'yes';
				}
				if( pc_object_def.products['shipping_weight'] != null ) {
					p.sections.info.list['shipping_package'].visible = 'yes';
				}
				var nvis = 0;
				for(i in p.sections.supplier.list) {
					if( pc_object_def.products[i] != null ) {
						p.sections.supplier.list[i].visible='yes';
						nvis++;
					} else {
						p.sections.supplier.list[i].visible='no';
					}
				}
				p.sections.supplier.visible = (nvis==0?'no':'yes');
				p.sections.supplier.list.supplier_name.visible = (nvis==0?'no':'yes');
				p.sections.short_description.visible = (pc_object_def.products.short_description!=null?'yes':'no');
				p.sections.long_description.visible = (pc_object_def.products.long_description!=null?'yes':'no');
				p.sections._image.visible = (pc_object_def.products.primary_image_id!=null?'yes':'no');
				p.sections.info.visible = (pc_object_def.products.primary_image_id!=null?'yes':'no');
				p.sections.prices.visible = (pc_object_def.prices!=null?'yes':'no');
				p.sections.images.visible = (pc_object_def.images!=null?'yes':'no');
				p.sections._images.visible = (pc_object_def.images!=null?'yes':'no');
				p.sections.files.visible = (pc_object_def.files!=null?'yes':'no');
				p.sections.similar.visible = (pc_object_def.similar!=null?'yes':'no');
				p.sections.recipes.visible = (pc_object_def.recipes!=null?'yes':'no');
				// Setup prev/next buttons
				p.prevnext.prev_id = 0;
				p.prevnext.next_id = 0;
				if( p.prevnext.list != null ) {
					for(i in p.prevnext.list) {
						if( p.prevnext.next_id == -1 ) {
							p.prevnext.next_id = p.prevnext.list[i].product.id;
							break;
						} else if( p.prevnext.list[i].product.id == p.product_id ) {
							p.prevnext.next_id = -1;
						} else {
							p.prevnext.prev_id = p.prevnext.list[i].product.id;
						}
					}
				}
				p.refresh();
				p.show(cb);
			});
	};
}
