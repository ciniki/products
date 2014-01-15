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
		'5':{'name':'Category Highlight'},
		};
	this.init = function() {
		//
		// The edit panel
		//
		this.edit = new M.panel('Product',
			'ciniki_products_edit', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.products.edit.edit');
		this.edit.data = {};
		this.edit.product_id = 0;
		this.edit.formtab = 'generic';
		this.edit.formtabs = {'label':'', 'field':'type', 'tabs':{
			'generic':{'label':'Generic', 'field_id':1, 'form':'generic'},
			'winekit':{'label':'Wine Kit', 'field_id':64, 'form':'winekit'},
			}};
		this.edit.forms = {};
		this.edit.forms.generic = {
			'_image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 
					'controls':'all', 'history':'no'},
				}},
			'info':{'label':'', 'fields':{
				'name':{'label':'Name', 'hint':'Product Name', 'type':'text'},
				'category':{'label':'Category', 'hint':'', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
				'price':{'label':'Price', 'hint':'', 'type':'text'},
				'status':{'label':'Status', 'type':'select', 'options':this.statusOptions},
				'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.webFlags},
				}},
			'details':{'label':'', 'visible':'no', 'fields':{
				}},
			'_description':{'label':'Brief Description', 'fields':{
				'short_description':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea'},
				}},
			'_long_description':{'label':'Full Description', 'fields':{
				'long_description':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea'},
				}},
			'_save':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_products_edit.saveProduct();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_products_edit.deleteProduct();'},
				}},
			};
		this.edit.forms.winekit = {
			'_image':this.edit.forms.generic._image,
			'info':this.edit.forms.generic.info,
			'details':{'label':'', 'fields':{
				'wine_type':{'label':'Wine Type', 'hint':'red, white or other', 'type':'text', 'size':'medium'},
				'kit_length':{'label':'Kit Length', 'hint':'4, 5, 6, 8', 'type':'text', 'size':'small'},
			}},
			'_description':this.edit.forms.generic._description,
			'_long_description':this.edit.forms.generic._long_description,
			'_save':this.edit.forms.generic._save
			};
		this.edit.liveSearchCb = function(s, i, value) {
			if( s == 'info' ) { 
				M.api.getJSONBgCb('ciniki.products.productCategorySearch', {'business_id':M.curBusinessID, 
					'start_needle':value, 'limit':'25'}, function(rsp) { 
						M.ciniki_products_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_products_edit.edit.panelUID + '_' + i), rsp.categories); 
				}); 
			return true;
			}   
		};  
		this.edit.liveSearchResultValue = function(s, f, i, j, d) {
			if( f == 'category' ) {
				return d.category.name;
			}
			return ''; 
		}   
		this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
			return 'M.ciniki_products_edit.edit.updateCategory(\'' + escape(d.category.name) + '\');';
		};  
		this.edit.updateCategory = function(name) {
			M.gE(this.panelUID + '_category').value = unescape(name);
			this.removeLiveSearch('info', 'category');
		};
		this.edit.fieldValue = function(s, i, d) {
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

		this.showEdit(cb, args.product_id, args.category);
	}

	this.showEdit = function(cb, pid, category) {
		this.edit.reset();
		if( pid != null ) { this.edit.product_id = pid; }
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
			this.edit.data = {'type':1};
			if( category != '' ) {
				this.edit.data.category = category;
			}
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.saveProduct = function() {
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
