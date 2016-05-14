//
// This is the UI to edit pdf catalogs
//
function ciniki_products_pdfcatalogs() {
	this.init = function() {
		//
		// The edit panel
		//
		this.catalog = new M.panel('Catalog',
			'ciniki_products_pdfcatalogs', 'catalog',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.products.catalogs.catalog');
		this.catalog.data = {};
		this.catalog.catalog_id = 0;
        this.catalog.sections = {
			'_image':{'label':'Image', 'aside':'yes', 'type':'imageform', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
            'general':{'label':'Catalog', 'aside':'yes', 'fields':{
                'name':{'label':'Name', 'type':'text'},
                'sequence':{'label':'Order', 'type':'text', 'size':'small'},
                'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
                }},
            '_synopsis':{'label':'Synopsis', 'aside':'yes', 'fields':{
                'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
                }},
            '_description':{'label':'Description', 'aside':'yes', 'fields':{
                'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
                }},
            '_file':{'label':'File', 'active':function() { return (M.ciniki_products_pdfcatalogs.catalog.catalog_id == 0 ? 'yes' : 'no'); }, 'fields':{
                'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes', 'history':'no'},
                }},
            'images':{'label':'Pages', 'type':'simplethumbs',
                'visible':function() { return (this.catalog_id > 0 ? 'yes' : 'yes'); },
                },
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_products_pdfcatalogs.catalog.save();'},
                 }},
        };
        this.catalog.sectionData = function(s) { return this.data[s]; }
		this.catalog.fieldValue = function(s, i, d) {
			if( this.data[i] != null ) { return this.data[i]; }
			return '';
		};
		this.catalog.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.products.pdfcatalogHistory', 'args':{'business_id':M.curBusinessID,
				'catalog_id':this.catalog_id, 'field':i}};
		}
        this.catalog.thumbFn = function(s, i, d) {
            return 'M.ciniki_products_pdfcatalogs.image.edit(\'M.ciniki_products_pdfcatalogs.catalog.edit();\',\'' + d.id + '\');';
        };
		this.catalog.addDropImage = function(iid) {
			M.ciniki_products_pdfcatalogs.catalog.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.catalog.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.catalog.addButton('save', 'Save', 'M.ciniki_products_pdfcatalogs.catalog.save();');
		this.catalog.addClose('Cancel');
        this.catalog.edit = function(cb, cid) {
            this.reset();
            if( cid != null) { this.catalog_id = cid; }
            M.api.getJSONCb('ciniki.products.pdfcatalogGet', {'business_id':M.curBusinessID, 'catalog_id':this.catalog_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_products_pdfcatalogs.catalog;
                p.data = rsp.catalog;
                p.refresh();
                p.show(cb);
            });
        }
        this.catalog.save = function() {
            if( this.catalog_id > 0 ) {
                var c = this.serializeForm('no');
                if( c != '' ) {
                    M.api.postJSONCb('ciniki.products.pdfcatalogUpdate', {'business_id':M.curBusinessID, 'catalog_id':this.catalog_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } 
                        M.ciniki_products_pdfcatalogs.catalog.close();
                        });
                } else {
                    this.close();
                }
            } else {
                var c = this.serializeFormData('yes');
                M.api.postJSONFormData('ciniki.products.pdfcatalogAdd', {'business_id':M.curBusinessID, 'catalog_id':this.catalog_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_products_pdfcatalogs.catalog.close();
                    });
            }
        }

		//
		// The edit image panel
		//
		this.image = new M.panel('Image',
			'ciniki_products_pdfcatalogs', 'image',
			'mc', 'medium', 'sectioned', 'ciniki.products.catalogs.image');
		this.image.data = {};
		this.image.catalog_image_id = 0;
        this.image.sections = {
			'_image':{'label':'Image', 'type':'imageform', 'fields':{
                'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
            'general':{'label':'', 'fields':{
                'page_number':{'label':'Page', 'type':'text'},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_products_pdfcatalogs.image.save();'},
                 }},
        };
        this.image.sectionData = function(s) { return this.data[s]; }
		this.image.fieldValue = function(s, i, d) {
			if( this.data[i] != null ) { return this.data[i]; }
			return '';
		};
		this.image.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.products.pdfcatalogImageHistory', 'args':{'business_id':M.curBusinessID,
				'catalog_image_id':this.catalog_image_id, 'field':i}};
		}
		this.image.addDropImage = function(iid) {
			M.ciniki_products_pdfcatalogs.image.setFieldValue('image_id', iid, null, null);
			return true;
		};
		this.image.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.image.addButton('save', 'Save', 'M.ciniki_products_pdfcatalogs.image.save();');
		this.image.addClose('Cancel');
        this.image.edit = function(cb, iid) {
            this.reset();
            if( iid != null) { this.catalog_image_id = iid; }
            M.api.getJSONCb('ciniki.products.pdfcatalogImageGet', {'business_id':M.curBusinessID, 'catalog_image_id':this.catalog_image_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_products_pdfcatalogs.image;
                p.data = rsp.image;
                p.refresh();
                p.show(cb);
            });
        }
        this.image.save = function() {
            if( this.catalog_image_id > 0 ) {
                var c = this.serializeForm('no');
                if( c != '' ) {
                    M.api.postJSONCb('ciniki.products.pdfcatalogImageUpdate', {'business_id':M.curBusinessID, 'catalog_image_id':this.catalog_image_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } 
                        M.ciniki_products_pdfcatalogs.image.close();
                        });
                } else {
                    this.close();
                }
            } else {
                var c = this.serializeForm('yes');
                M.api.postJSONCb('ciniki.products.pdfcatalogImageAdd', {'business_id':M.curBusinessID, 'catalog_image_id':this.catalog_image_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_products_pdfcatalogs.image.close();
                    });
            }
        }
	};

	this.start = function(cb, aP, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }
		var aC = M.createContainer(aP, 'ciniki_products_pdfcatalogs', 'yes');
		if( aC == null ) {
			alert('App Error');
			return false;
		}

		this.catalog.edit(cb, args.catalog_id);
	}
}
