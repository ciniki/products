//
// This is the main UI for a product
//
function ciniki_products_edit() {
	this.statusOptions = {
		'10':'Active',
		'60':'Discontinued',
		};
	this.webFlags = {
		'1':{'name':'Hidden'},
		'2':{'name':'Sell Online', 'active':'no'},
		'3':{'name':'Hide Price', 'active':'yes'},
		'5':{'name':'Category Highlight'},
		};
//	this.oakToggles = {
//		'0':'0',
//		'1':'1',
//		'2':'2',
//		'3':'3',
//		'4':'4',
//		'5':'5',
//		};
//	this.bodyToggles = {
//		'1':'1',
//		'2':'2',
//		'3':'3',
//		'4':'4',
//		'5':'5',
//		};
//	this.sweetnessToggles = {
//		'0':'0',
//		'1':'1',
//		'2':'2',
//		'3':'3',
//		'4':'4',
//		'5':'5',
//		};
//	this.inventoryFlags = {
//		'1':{'name':'Track'},
//		};
	this.init = function() {
		//
		// The edit panel
		//
		this.edit = new M.panel('Product',
			'ciniki_products_edit', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.products.edit.edit');
		this.edit.data = {};
		this.edit.product_id = 0;
		this.edit.formtab = 0;
		this.edit.default_formtab = 0;
		this.edit.forms = {};
		this.edit.sections = {};
		this.edit.liveSearchCb = function(s, i, value) {
			if( s == 'info' ) { 
				M.api.getJSONBgCb('ciniki.products.productCategorySearch', {'business_id':M.curBusinessID, 
					'start_needle':value, 'limit':'25'}, function(rsp) { 
						M.ciniki_products_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_products_edit.edit.panelUID + '_' + i), rsp.categories); 
				}); 
				return true;
			}
			if( i == 'supplier_id' ) {
				M.api.getJSONBgCb('ciniki.products.supplierSearch', {'business_id':M.curBusinessID, 
					'start_needle':value, 'limit':25}, function(rsp) {
						M.ciniki_products_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_products_edit.edit.panelUID + '_' + i), rsp.suppliers);
					});
			}
		};  
		this.edit.liveSearchResultValue = function(s, f, i, j, d) {
			if( f == 'category' ) { return d.category.name; }
			if( f == 'supplier_id' ) { return d.supplier.name; }
			return ''; 
		}   
		this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
			if( f == 'supplier_id' ) {
				return 'M.ciniki_products_edit.edit.updateSupplier(\'' + escape(d.supplier.name) + '\', \'' + d.supplier.id + '\');';
			}
			return 'M.ciniki_products_edit.edit.updateCategory(\'' + escape(d.category.name) + '\');';
		};  
		this.edit.updateSupplier = function(name, pid) {
			M.gE(this.panelUID + '_supplier_id').value = pid;
			M.gE(this.panelUID + '_supplier_id_fkidstr').value = unescape(name);
			this.removeLiveSearch('supplier', 'supplier_id');
		};
		this.edit.updateCategory = function(name) {
			M.gE(this.panelUID + '_category').value = unescape(name);
			this.removeLiveSearch('info', 'category');
		};
		this.edit.fieldValue = function(s, i, d) {
			if( i == 'supplier_id_fkidstr' ) { return this.data.supplier_name; }
			if( this.data[i] != null ) { return this.data[i]; }
			return '';
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.products.productHistory', 'args':{'business_id':M.curBusinessID,
				'product_id':this.product_id, 'field':i}};
		}
		this.edit.addDropImage = function(iid) {
			M.ciniki_products_edit.edit.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.edit.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_products_edit.saveProduct();');
		this.edit.addClose('Cancel');
	};

	this.start = function(cb, aP, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }
		var aC = M.createContainer(aP, 'ciniki_products_edit', 'yes');
		if( aC == null ) {
			alert('App Error');
			return false;
		}

		// Use the business product types to setup the edit form
		if( M.curBusiness.products != null && M.curBusiness.products.settings.types != null ) {
			this.edit.formtabs = {'label':'', 'field':'type_id', 'tabs':{}};
			this.edit.forms = {};
			this.edit.formtab = 0;
			this.edit.default_formtab = 0;
			for(i in M.curBusiness.products.settings.types) {
				var type = M.curBusiness.products.settings.types[i].type;
				if( this.edit.formtab == 0 ) {
					this.edit.formtab = type.id;
					this.edit.default_formtab = type.id;
				}
				this.edit.formtabs.tabs[type.id] = {'label':type.name.single, 'field_id':type.id, 'form':type.id};
				this.edit.forms[type.id] = this.setupForm(type);
			}
		}

		this.showEdit(cb, args.product_id, args.category, args.supplier_id, args.supplier_name);
	}

	this.setupForm = function(type) {
		var fields = type.parent.products;
		var form = {};
		if( fields.primary_image_id != null ) {
			form['image_id'] = {'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes',
					'controls':'all', 'history':'no'},
				}};
		}
		form['info'] = {'label':'', 'fields':{
			'name':{'label':'Name', 'hint':'Product Name', 'type':'text', 
				'active':(fields.name!=null?'yes':'no')},
			'code':{'label':'Code', 'hint':'Product Code', 'type':'text', 
				'active':(fields.code!=null?'yes':'no')},
			'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
				'active':(fields.category!=null?'yes':'no')},
			'price':{'label':'Price', 'type':'text', 'active':(fields.price!=null?'yes':'no')},
			'cost':{'label':'Cost', 'type':'text', 'active':(fields.cost!=null?'yes':'no')},
			'msrp':{'label':'MSRP', 'type':'text', 'active':(fields.msrp!=null?'yes':'no')},
			'status':{'label':'Status', 'type':'select', 'options':this.statusOptions, 
				'active':(fields.status!=null?'yes':'no')},
			'webflags':{'label':'Name', 'hint':'Product Name', 'type':'flags', 'flags':this.webFlags,
				'active':(fields.webflags!=null?'yes':'no')},
		}};
		if( M.curBusiness.modules['ciniki.sapos'] != null 
			&& (M.curBusiness.modules['ciniki.sapos'].flags&0x08) > 0 ) {
			form.info.fields.webflags.flags['2'].active = 'yes';
		} else {
			form.info.fields.webflags.flags['2'].active = 'no';
		}
		for(i=0;i<10;i++) {
			if( fields['detail0' + i] != null ) {
				var field = fields['detail0' + i];
				if( form.details == null ) { form['details'] = {'label':'', 'fields':{}}; }
				form.details.fields['detail0' + i] = {'label':(field.name!=null?field.name:''), 'type':'text'};
			}
		}
		if( fields.supplier_id != null ) {
			form['supplier'] = {'label':'Supplier', 'fields':{
				'supplier_id':{'label':'Name', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
				'supplier_item_number':{'label':'Item Number', 'type':'text', 
					'active':(fields.supplier_item_number!=null?'yes':'no')},
				'supplier_minimum_order':{'label':'Minimum Order', 'type':'text', 'size':'small', 
					'active':(fields.supplier_minimum_order!=null?'yes':'no')},
				'supplier_order_multiple':{'label':'Multiples', 'type':'text', 'size':'small', 
					'active':(fields.supplier_order_multiple!=null?'yes':'no')},
				}};
		}
		if( fields.inventory_flags != null || fields.inventory_current_num != null ) {
			form['inventory'] = {'label':'Inventory', 'fields':{}};
//			if( fields.inventory_flags != null ) { form.inventory.fields['inventory_flags'] = 
//				{'label':'Options', 'type':'flags', 'flags':this.inventoryFlags}; }
			if( fields.inventory_current_num != null ) { form.inventory.fields['inventory_current_num'] = 
				{'label':'Number', 'type':'text', 'size':'small'}; }
		}
		if( fields.manufacture_min_time != null || fields.manufacture_max_time != null ) {
			form['manufacturing'] = {'label':'Manufacturing', 'fields':{}};
			if( fields.manufacture_min_time != null ) { form.manufacturing.fields['manufacture_min_time'] = 
				{'label':'Min Time', 'type':'text', 'size':'small'}};
			if( fields.manufacture_max_time != null ) { form.manufacturing.fields['manufacture_max_time'] = 
				{'label':'Max Time', 'type':'text', 'size':'small'}};
		}
		if( fields.short_description != null ) {
			form['_description'] = {'label':'Brief Description', 'fields':{
				'short_description':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea'},
				}};
		}
		if( fields.long_description != null ) {
			form['_long_description'] = {'label':'Full Description', 'fields':{
				'long_description':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea'},
				}};
		}
		form['save'] = {'label':'', 'buttons':{
			'save':{'label':'Save', 'fn':'M.ciniki_products_edit.saveProduct();'},
			'delete':{'label':'Delete', 'fn':'M.ciniki_products_edit.deleteProduct();'},
			}};
form;

		return form;
	}

	this.showEdit = function(cb, pid, category, supplier_id, supplier_name) {
		this.edit.reset();
		if( pid != null ) { this.edit.product_id = pid; }
		// Check if inventory enabled
//		if( (M.curBusiness.modules['ciniki.products'].flags&0x04) > 0 ) {
//			this.edit.forms.generic.inventory.active = 'yes';
//		} else {
//			this.edit.forms.generic.inventory.active = 'no';
//		}
//		// Check if suppliers enabled
//		if( (M.curBusiness.modules['ciniki.products'].flags&0x08) > 0 ) {
//			this.edit.forms.generic.supplier.active = 'yes';
//		} else {
//			this.edit.forms.generic.supplier.active = 'no';
//		}
		// Check if shopping cart is enabled for business
//		if( M.curBusiness.modules['ciniki.sapos'] != null 
//			&& (M.curBusiness.modules['ciniki.sapos'].flags&0x08) > 0 ) {
//			this.edit.forms.generic.info.fields.webflags.flags['2'].active = 'yes';
//		} else {
//			this.edit.forms.generic.info.fields.webflags.flags['2'].active = 'no';
//		}
		if( this.edit.product_id > 0 ) {
			M.api.getJSONCb('ciniki.products.productGet', {'business_id':M.curBusinessID,
				'product_id':this.edit.product_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_products_edit.edit;
					p.data = rsp.product;
					p.refresh();
					p.show(cb);
				});
		} else {
			this.edit.product_id = 0;
			this.edit.data = {'type_id':this.edit.default_formtab};
			if( category != '' ) {
				this.edit.data.category = category;
			}
			if( supplier_id != null ) {
				this.edit.data.supplier_id = supplier_id;
				this.edit.data.supplier_name = unescape(supplier_name);
			}
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.saveProduct = function() {
		if( this.edit.sections.supplier == null ) {
			return this.saveProductFinish();
		}
		var name = M.gE(this.edit.panelUID + '_supplier_id_fkidstr').value;
		var sid = this.edit.formValue('supplier_id');
		if( (sid == 0 && name != '')
			|| (this.edit.data.supplier_name != null && this.edit.data.supplier_name != name && name != '' ) ) {
			M.api.getJSONCb('ciniki.products.supplierAdd', {'business_id':M.curBusinessID,
				'name':encodeURIComponent(name)}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.gE(M.ciniki_products_edit.edit.panelUID + '_supplier_id').value = rsp.id;
					M.ciniki_products_edit.saveProductFinish();
				});
		} else {
			this.saveProductFinish();
		}
	};

	this.saveProductFinish = function() {
		if( this.edit.product_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.products.productUpdate',
					{'business_id':M.curBusinessID, 'product_id':this.edit.product_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_products_edit.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			M.api.postJSONCb('ciniki.products.productAdd',
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
//					if( rsp.id > 0 ) {
//						var cb = M.ciniki_products_edit.edit.cb;
//						M.ciniki_products_edit.edit.close();
//						M.ciniki_products_product.showProduct(cb, rsp.id);
//					} else {
						M.ciniki_products_edit.edit.close();
//					}
				});
		}
	};

	this.deleteProduct = function() {
	};
}
