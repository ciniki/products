//
// This is the main UI for a product
//
function ciniki_products_category() {
	this.init = function() {
		//
		// The edit panel
		//
		this.edit = new M.panel('Category',
			'ciniki_products_category', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.products.category.edit');
		this.edit.data = {};
		this.edit.category_permalink = '';
		this.edit.subcategory_permalink = '';
		this.edit.sections = {
			'_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 
					'controls':'all', 'history':'no'},
			}},
//			'_image_caption':{'label':'', 'aside':'yes', 'fields':{
//				'primary_image_caption':{'label':'Caption', 'type':'text'},
//			}},
			'_name':{'label':'', 'aside':'yes', 'fields':{
				'name':{'label':'Name', 'type':'text'},
				'sequence':{'label':'Sequence', 'type':'text', 'size':'small'},
                'subcategory':{'label':'Sub Category', 'type':'select', 
                    'visible':function() { return M.ciniki_products_category.edit.subcategory_permalink==''?'yes':'no'; },
                    'options':{'':'All'},
                    },
             }},
             '_formats':{'label':'Display Formats', 
                'active':function() { return M.ciniki_products_category.edit.subcategory_permalink==''?'yes':'no'; },
                'aside':'yes', 'fields':{
                    'format':{'label':'Category', 'type':'select', 
                        'options':{'':'Default'},
                        },
                    'subformat':{'label':'Sub Category', 'type':'select', 
                        'options':{'':'Default'},
                        },
			}},
			'_synopsis':{'label':'Synopsis', 'fields':{
				'synopsis':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
			}},
			'_description':{'label':'Description', 'fields':{
				'description':{'label':'', 'type':'textarea', 'size':'large', 'hidelabel':'yes'},
			}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_products_category.saveCategory();'},
			}},
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.products.categoryHistory', 'args':{'business_id':M.curBusinessID,
				'category_id':this.category_id, 'field':i}};
		};
		this.edit.addDropImage = function(iid) {
			M.ciniki_products_category.edit.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.edit.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.edit.sectionData = function(s) { 
			return this.data[s];
		};
		this.edit.fieldValue = function(s, i, j, d) {
			return this.data[i];
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_products_category.saveCategory();');
		this.edit.addClose('Cancel');
	};

	this.start = function(cb, aP, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }
		var aC = M.createContainer(aP, 'ciniki_products_category', 'yes');
		if( aC == null ) {
			alert('App Error');
			return false;
		}

		this.edit.category = '';
		this.edit.subcategory = '';
		this.showEdit(cb, args.category, args.subcategory);
	}

	this.showEdit = function(cb, category, subcategory) {
		this.edit.reset();
		if( category != null ) { 
			this.edit.category_permalink = category; 
			this.edit.subcategory_permalink = '';
		}
		if( subcategory != null ) { this.edit.subcategory_permalink = subcategory; }
		M.api.getJSONCb('ciniki.products.categoryGet', {'business_id':M.curBusinessID,
			'category':this.edit.category_permalink,
			'subcategory':this.edit.subcategory_permalink}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_products_category.edit;
				p.data = rsp.category;
				p.category_id = rsp.category.id;
				p.refresh();
				p.show(cb);
			});
	};

	this.saveCategory = function() {
		if( this.edit.category_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {	
				M.api.postJSONCb('ciniki.products.categoryUpdate', {'business_id':M.curBusinessID,
					'category_id':this.edit.category_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_products_category.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			M.api.postJSONCb('ciniki.products.categoryAdd', {'business_id':M.curBusinessID,
				'category':this.edit.category_permalink,
				'subcategory':this.edit.subcategory_permalink}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_products_category.edit.close();
				});
		}
	};
}
