//
// This is the main UI for a product
//
function ciniki_products_edit() {
    this.statusOptions = {
        '10':'Active',
        '60':'Discontinued',
        };
    this.shippingWeightUnits = {
        '10':'lbs',
        '20':'kgs',
        };
    this.shippingSizeUnits = {
        '10':'in',
        '20':'cm',
        };
    this.shippingFlags = {
        '1':{'name':'Shipping'},
        '2':{'name':'Pickup'},
        };
//  this.oakToggles = {
//      '0':'0',
//      '1':'1',
//      '2':'2',
//      '3':'3',
//      '4':'4',
//      '5':'5',
//      };
//  this.bodyToggles = {
//      '1':'1',
//      '2':'2',
//      '3':'3',
//      '4':'4',
//      '5':'5',
//      };
//  this.sweetnessToggles = {
//      '0':'0',
//      '1':'1',
//      '2':'2',
//      '3':'3',
//      '4':'4',
//      '5':'5',
//      };
    this.inventoryFlags = {
        '1':{'name':'Track'},
        '2':{'name':'Backorder'},
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
        this.edit.formtab = 0;
        this.edit.default_formtab = 0;
        this.edit.forms = {};
        this.edit.sections = {};
        this.edit.prevnext = {'prev_id':-1, 'next_id':-1, 'list':[]};
        this.edit.tags = {'categories':[], 
            'subcategories-11':[], 
            'subcategories-12':[], 
            'subcategories-13':[], 
            'subcategories-14':[], 
            'subcategories-15':[], 
            'tags':[]};
        this.edit.liveSearchCb = function(s, i, value) {
            if( s == 'info' ) { 
                M.api.getJSONBgCb('ciniki.products.productCategorySearch', {'tnid':M.curTenantID, 
                    'start_needle':value, 'limit':'25'}, function(rsp) { 
                        M.ciniki_products_edit.edit.liveSearchShow(s, i, M.gE(M.ciniki_products_edit.edit.panelUID + '_' + i), rsp.categories); 
                }); 
                return true;
            }
            if( i == 'supplier_id' ) {
                M.api.getJSONBgCb('ciniki.products.supplierSearch', {'tnid':M.curTenantID, 
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
//      this.edit.fieldTags = function(s, i, d) {
//          return this.tags[i];
//      };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.products.productHistory', 'args':{'tnid':M.curTenantID,
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
        this.edit.prevButtonFn = function() {
            if( this.prevnext.prev_id > -1 ) {
                return 'M.ciniki_products_edit.saveProduct(\'' + this.prevnext.prev_id + '\');';
            }
            return null;
        };
        this.edit.nextButtonFn = function() {
            if( this.prevnext.next_id > -1 ) {
                return 'M.ciniki_products_edit.saveProduct(\'' + this.prevnext.next_id + '\');';
            }
            return null;
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_products_edit.saveProduct();');
        this.edit.addButton('next', 'Next');
        this.edit.addClose('Cancel');
        this.edit.addLeftButton('prev', 'Prev');
    };

    this.start = function(cb, aP, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }
        var aC = M.createContainer(aP, 'ciniki_products_edit', 'yes');
        if( aC == null ) {
            alert('App Error');
            return false;
        }

        var pc = (args.parent_id==null||args.parent_id==0)?'parent':'child';
        // Use the tenant product types to setup the edit form
        if( M.curTenant.products != null && M.curTenant.products.settings.types != null ) {
            this.edit.formtabs = {'label':'', 'field':'type_id', 'tabs':{}};
            this.edit.forms = {};
            this.edit.formtab = 0;
            this.edit.default_formtab = 0;
            for(i in M.curTenant.products.settings.types) {
                var type = M.curTenant.products.settings.types[i].type;
                if( this.edit.formtab == 0 ) {
                    this.edit.formtab = type.id;
                    this.edit.default_formtab = type.id;
                }
                this.edit.formtabs.tabs[type.id] = {'label':type.name.single, 'field_id':type.id, 'form':type.id, 'type_num':i};
                this.edit.forms[type.id] = this.setupForm(type, pc);
            }
        }

        this.showEdit(cb, args.product_id, args.category, args.supplier_id, args.supplier_name, args.list);
    }

    this.setupForm = function(type, pc) {
        var fields = type[pc].products;
        var form = {};
        if( fields.primary_image_id != null ) {
            form['image_id'] = {'label':'', 'aside':'yes', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes',
                    'controls':'all', 'history':'no'},
                }};
        }
        var webFlags = {
            '1':{'name':'Visible'},
            '2':{'name':'Sell Online', 'active':'no'},
            '3':{'name':'Hide Price', 'active':'yes'},
            '5':{'name':'Category Highlight', 'active':'no'},
            '6':{'name':'Sub-Category Highlight', 'active':'no'},
            '11':{'name':'Visible to dealers', 'active':'no'},
            };
        var flags = {
            '3':{'name':'Promotional Item', 'active':'no'},
            };
        var flags_visible = 'no';
        if( M.curTenant.modules['ciniki.sapos'] != null && (M.curTenant.modules['ciniki.sapos'].flags&0x08) > 0 ) {
            webFlags['2'].active = 'yes';
        }
        if( M.curTenant.modules['ciniki.products'] != null && (M.curTenant.modules['ciniki.products'].flags&0x10) > 0 && fields.flags != null ) {
            flags_visible = 'yes';
            flags['3'].active = 'yes';
        }
        if( type[pc].categories != null ) {
            webFlags['5'].active = 'yes';
        }
        if( type[pc]['subcategories-11'] != null ) {
            webFlags['6'].active = 'yes';
        }
        if( M.curTenant.modules['ciniki.customers'] != null && (M.curTenant.modules['ciniki.customers'].flags&0x10) > 0 ) {
            webFlags['11'].active = 'yes';
        }
        form['info'] = {'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'hint':'Product Name', 'type':'text', 
                'active':(fields.name!=null?'yes':'no')},
            'code':{'label':'Code', 'hint':'Product Code', 'type':'text', 
                'active':(fields.code!=null?'yes':'no')},
            'sequence':{'label':'Order', 'type':'text', 'size':'small', 'active':(fields.sequence!=null?'yes':'no')},
            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
                'active':(fields.category!=null?'yes':'no')},
            'price':{'label':'Price', 'type':'text', 'active':(fields.price!=null?'yes':'no')},
            'taxtype_id':{'label':'Taxes', 'active':(fields.taxtype_id!=null?'yes':'no'), 'type':'select', 'options':{}},
            'cost':{'label':'Cost', 'type':'text', 'active':(fields.cost!=null?'yes':'no')},
            'msrp':{'label':'MSRP', 'type':'text', 'active':(fields.msrp!=null?'yes':'no')},
            'sell_unit':{'label':'Sell Unit', 'type':'text', 'active':(fields.sell_unit!=null?'yes':'no')},
            'status':{'label':'Status', 'type':'select', 'options':this.statusOptions, 
                'active':(fields.status!=null?'yes':'no')},
            'start_date':{'label':'Start Date', 'type':'date', 'active':(fields.start_date!=null?'yes':'no')},
            'end_date':{'label':'End Date', 'type':'date', 'active':(fields.end_date!=null?'yes':'no')},
            'flags':{'label':'Options', 'hint':'', 'type':'flags', 'flags':flags, 'active':flags_visible},
            'webflags':{'label':'Website', 'hint':'', 'type':'flags', 'flags':webFlags,
                'active':(fields.webflags!=null?'yes':'no')},
        }};
        //
        // Setup the tax types
        //
        if( M.curTenant.modules['ciniki.taxes'] != null ) {
            form['info'].fields.taxtype_id.options = {'0':'No Taxes'};
            if( M.curTenant.taxes != null && M.curTenant.taxes.settings.types != null ) {
                for(i in M.curTenant.taxes.settings.types) {
                    form['info'].fields.taxtype_id.options[M.curTenant.taxes.settings.types[i].type.id] = M.curTenant.taxes.settings.types[i].type.name;
                }
            }
        } else {
            form['info'].fields.taxtype_id.options = {'0':'No Taxes'};
        }

        if( type[pc].categories != null ) {
            form['_categories'] = {'label':'Categories', 'aside':'yes', 'fields':{
                'categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':this.edit.tags.categories, 'hint':'Enter a new category:'},
                }};
        }
        for(var i=11;i<16;i++) {
            if( type[pc]['subcategories-'+i] != null ) {
                form['_subcategories-'+i] = {'label':(type[pc]['subcategories-'+i]['pname']!=null?type[pc]['subcategories-'+i]['pname']:'Sub-Categories'), 'aside':'yes', 'fields':{}};
                form['_subcategories-'+i].fields['subcategories-'+i] = {'label':'', 'hidelabel':'yes', 
                    'type':'tags', 'tags':this.edit.tags['subcategories-'+i], 'hint':'Enter a new sub-category:'};
            }
        }
        if( type[pc].tags != null ) {
            form['_tags'] = {'label':'Tag', 'aside':'yes', 'fields':{
                'tags':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':this.edit.tags.tags, 'hint':'Enter a new tag:'},
                }};
        }
        if( (M.curTenant.modules['ciniki.products'].flags&0x04) > 0 && (fields.inventory_flags != null || fields.inventory_current_num != null) ) {
            form['inventory'] = {'label':'', 'fields':{}};
            if( fields.inventory_flags != null ) { form.inventory.fields['inventory_flags'] = 
                {'label':'Options', 'type':'flags', 'flags':this.inventoryFlags}; }
            if( fields.inventory_current_num != null ) { 
                form.inventory.fields['inventory_current_num'] = {'label':'Inventory', 'type':'text', 'size':'small'}; 
                if( M.modFlagOn('ciniki.products', 0x20) ) {
                    form.inventory.fields['history_notes'] = {'label':'Notes', 'type':'text'};
                }
            }
            if( fields.inventory_reorder_num != null ) { form.inventory.fields['inventory_reorder_num'] = 
                {'label':'Reorder Level', 'type':'text', 'size':'small'}; }
            if( fields.inventory_reorder_quantity != null ) { form.inventory.fields['inventory_reorder_quantity'] = 
                {'label':'Reorder Quantity', 'type':'text', 'size':'small'}; }
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
        if( fields.manufacture_min_time != null || fields.manufacture_max_time != null ) {
            form['manufacturing'] = {'label':'Manufacturing', 'fields':{}};
            if( fields.manufacture_min_time != null ) { form.manufacturing.fields['manufacture_min_time'] = 
                {'label':'Min Time', 'type':'text', 'size':'small'}};
            if( fields.manufacture_max_time != null ) { form.manufacturing.fields['manufacture_max_time'] = 
                {'label':'Max Time', 'type':'text', 'size':'small'}};
        }
        if( fields.shipping_flags != null 
            || fields.shipping_weight != null || fields.shipping_length != null 
            ) {
            form['shipping'] = {'label':'Shipping', 'fields':{}};
            if( fields.shipping_flags != null ) {
                form.shipping.fields['shipping_flags'] = {'label':'Options', 'type':'flags', 'flags':this.shippingFlags};
            };
            if( fields.shipping_weight != null ) { 
                form.shipping.fields['shipping_weight'] = {'label':'Weight', 'type':'text', 'size':'small'};
                form.shipping.fields['shipping_weight_units'] = 
                    {'label':'Units', 'type':'toggle', 'default':'10', 'toggles':this.shippingWeightUnits};
                };
            if( fields.shipping_length != null ) { form.shipping.fields['shipping_length'] = 
                {'label':'Length', 'type':'text', 'size':'small'}};
            if( fields.shipping_width != null ) { form.shipping.fields['shipping_width'] = 
                {'label':'Width', 'type':'text', 'size':'small'}};
            if( fields.shipping_height != null ) { form.shipping.fields['shipping_height'] = 
                {'label':'Height', 'type':'text', 'size':'small'}};
            if( fields.shipping_size_units != null ) { form.shipping.fields['shipping_size_units'] = 
                {'label':'Units', 'type':'toggle', 'default':'10', 'toggles':this.shippingSizeUnits}};
        }
        if( fields.short_description != null ) {
            form['_description'] = {'label':'Brief Description', 'fields':{
                'short_description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
                }};
        }
        if( fields.long_description != null ) {
            form['_long_description'] = {'label':'Full Description', 'fields':{
                'long_description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
                }};
        }
        form['save'] = {'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_products_edit.saveProduct();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_products_edit.deleteProduct();'},
            }};

        return form;
    }

    this.showEdit = function(cb, pid, category, supplier_id, supplier_name, list) {
        this.edit.reset();
        if( pid != null ) { this.edit.product_id = pid; }
        if( list != null ) { this.edit.prevnext.list = list; }
        // Check if inventory enabled
//      if( (M.curTenant.modules['ciniki.products'].flags&0x04) > 0 ) {
//          this.edit.forms.generic.inventory.active = 'yes';
//      } else {
//          this.edit.forms.generic.inventory.active = 'no';
//      }
//      // Check if suppliers enabled
//      if( (M.curTenant.modules['ciniki.products'].flags&0x08) > 0 ) {
//          this.edit.forms.generic.supplier.active = 'yes';
//      } else {
//          this.edit.forms.generic.supplier.active = 'no';
//      }
        // Check if shopping cart is enabled for tenant
//      if( M.curTenant.modules['ciniki.sapos'] != null 
//          && (M.curTenant.modules['ciniki.sapos'].flags&0x08) > 0 ) {
//          this.edit.forms.generic.info.fields.webflags.flags['2'].active = 'yes';
//      } else {
//          this.edit.forms.generic.info.fields.webflags.flags['2'].active = 'no';
//      }
        if( this.edit.product_id > 0 ) {
            M.api.getJSONCb('ciniki.products.productGet', {'tnid':M.curTenantID,
                'product_id':this.edit.product_id, 'categories':'yes', 'subcategories':'yes', 'tags':'yes'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_products_edit.edit;
                    p.data = rsp.product;
                    p.tags = {'categories':[], 
                        'subcategories-11':[], 
                        'subcategories-12':[], 
                        'subcategories-13':[],
                        'subcategories-14':[],
                        'subcategories-15':[],
                        'tags':[]};
                    for(i in p.tags) {
                        if( rsp[i] != null ) {
                            for(j in rsp[i]) {
                                p.tags[i].push(rsp[i][j].tag.name);
                            }
                        }
                    }
//                  if( rsp.categories != null ) {
//                      for(i in rsp.categories) {
//                          p.tags.categories.push(rsp.categories[i].tag.name);
//                      }
//                  }
//                  if( rsp.tags != null ) {
//                      for(i in rsp.tags) {
//                          p.tags.push(rsp.categories[i].tag.name);
//                      }
//                  }
                    // Setup prev/next buttons
                    p.prevnext.prev_id = -1;
                    p.prevnext.next_id = -1;
                    if( p.prevnext.list != null ) {
                        for(i in p.prevnext.list) {
                            if( p.prevnext.next_id == -2 ) {
                                p.prevnext.next_id = p.prevnext.list[i].product.id;
                                break;
                            } else if( p.prevnext.list[i].product.id == p.product_id ) {
                                p.prevnext.next_id = -2;
                            } else {
                                p.prevnext.prev_id = p.prevnext.list[i].product.id;
                            }
                        }
                    }
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.edit.product_id = 0;
            this.edit.data = {'type_id':this.edit.default_formtab, 'webflags':1,
                'shipping_weight_units':10, 'shipping_size_units':10};
            if( category != '' ) {
                this.edit.data.category = category;
                this.edit.data.categories = category;
            }
            if( supplier_id != null ) {
                this.edit.data.supplier_id = supplier_id;
                this.edit.data.supplier_name = unescape(supplier_name);
            }
            M.api.getJSONCb('ciniki.products.productTags', {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_products_edit.edit;
                p.tags = {'categories':[], 
                    'subcategories-11':[], 
                    'subcategories-12':[], 
                    'subcategories-13':[], 
                    'subcategories-14':[], 
                    'subcategories-15':[], 
                    'tags':[]};
                for(i in p.tags) {
                    if( rsp[i] != null ) {
                        for(j in rsp[i]) {
                            p.tags[i].push(rsp[i][j].tag.name);
                        }
                    }
                }
//              p.tags = {'categories':[], 'tags':[]};
//              if( rsp.categories != null ) {
//                  for(i in rsp.categories) {
//                      p.tags.categories.push(rsp.categories[i].tag.name);
//                  }
//              }
//              if( rsp.tags != null ) {
//                  for(i in rsp.tags) {
//                      p.tags.push(rsp.categories[i].tag.name);
//                  }
//              }
                p.prevnext.prev_id = -1;
                p.prevnext.next_id = 0;
                p.refresh();
                p.show(cb);
                });
        }
    };

    this.saveProduct = function(prevnext_id) {
        if( this.edit.sections.supplier == null ) {
            return this.saveProductFinish();
        }
        var name = M.gE(this.edit.panelUID + '_supplier_id_fkidstr').value;
        var sid = this.edit.formValue('supplier_id');
        if( (sid == 0 && name != '')
            || (this.edit.data.supplier_name != null && this.edit.data.supplier_name != name && name != '' ) ) {
            M.api.getJSONCb('ciniki.products.supplierAdd', {'tnid':M.curTenantID,
                'name':encodeURIComponent(name)}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.gE(M.ciniki_products_edit.edit.panelUID + '_supplier_id').value = rsp.id;
                    M.ciniki_products_edit.saveProductFinish(prevnext_id);
                });
        } else {
            this.saveProductFinish(prevnext_id);
        }
    };

    this.saveProductFinish = function(prevnext_id) {
        if( this.edit.product_id > 0 ) {
            var c = this.edit.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.products.productUpdate',
                    {'tnid':M.curTenantID, 'product_id':this.edit.product_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        if( prevnext_id != null && prevnext_id > -1 ) {
                            M.ciniki_products_edit.showEdit(null, prevnext_id);
                        } else {
                            M.ciniki_products_edit.edit.close();
                        }
                    });
            } else {
                if( prevnext_id != null && prevnext_id > -1 ) {
                    M.ciniki_products_edit.showEdit(null, prevnext_id);
                } else {
                    this.edit.close();
                }
            }
        } else {
            var c = this.edit.serializeForm('yes');
            M.api.postJSONCb('ciniki.products.productAdd',
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
//                  if( rsp.id > 0 ) {
//                      var cb = M.ciniki_products_edit.edit.cb;
//                      M.ciniki_products_edit.edit.close();
//                      M.ciniki_products_product.showProduct(cb, rsp.id);
//                  } else {
                    if( prevnext_id != null && prevnext_id > -1 ) {
                        M.ciniki_products_edit.showEdit(null, prevnext_id);
                    } else {
                        M.ciniki_products_edit.edit.close();
                    }
//                  }
                });
        }
    };

    this.deleteProduct = function() {
        if( this.edit.product_id < 1 ) { return false; }
        if( confirm("Are you sure you want to remove this product?") ) {
            M.api.getJSONCb('ciniki.products.productDelete', {'tnid':M.curTenantID,
                'product_id':this.edit.product_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_products_product.product.close();
                });
        }
    };
}
