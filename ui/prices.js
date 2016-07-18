//
// This app will display and update prices for an product
//
function ciniki_products_prices() {
    this.webFlags = {
        '1':{'name':'Hidden'},
        };
    this.availableToFlags = {
        '1':{'name':'Public'},
        '2':{'name':'Private'},
        '5':{'name':'Customers'},
        '6':{'name':'Members'},
        '7':{'name':'Dealers'},
        '8':{'name':'Distributors'},
        };
    this.init = function() {
        //
        // The panel for editing a registrant
        //
        this.edit = new M.panel('Product Price',
            'ciniki_products_prices', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.products.prices.edit');
        this.edit.data = null;
        this.edit.product_id = 0;
        this.edit.price_id = 0;
        this.edit.sections = { 
            'price':{'label':'Price', 'fields':{
                'name':{'label':'Name', 'type':'text'},
                'pricepoint_id':{'label':'Price Point', 'type':'select', 'options':{}},
                'available_to':{'label':'Availablity', 'type':'flags', 'default':1, 'flags':{}},
                'min_quantity':{'label':'Minimum Quantity', 'type':'text', 'size':'small'},
                'unit_amount':{'label':'Unit Amount', 'type':'text', 'size':'small'},
                'unit_discount_amount':{'label':'Discount Amount', 'type':'text', 'size':'small'},
                'unit_discount_percentage':{'label':'Discount Percent', 'type':'text', 'size':'small'},
                'taxtype_id':{'label':'Taxes', 'active':'no', 'type':'select', 'options':{}},
                'start_date':{'label':'Start Date', 'visible':'no', 'hint':'', 'type':'date'},
                'end_date':{'label':'End Date', 'visible':'no', 'hint':'', 'type':'date'},
                'webflags':{'label':'Web', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_products_prices.savePrice();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_products_prices.deletePrice();'},
                }},
            };  
        this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.products.priceHistory', 'args':{'business_id':M.curBusinessID, 
                'price_id':this.price_id, 'product_id':this.product_id, 'field':i}};
        }
        this.edit.sectionData = function(s) {
            return this.data[s];
        }
        this.edit.rowFn = function(s, i, d) { return ''; }
        this.edit.addButton('save', 'Save', 'M.ciniki_products_prices.savePrice();');
        this.edit.addClose('Cancel');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_products_prices', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        //
        // Setup the tax types
        //
        if( M.curBusiness.modules['ciniki.taxes'] != null ) {
            this.edit.sections.price.fields.taxtype_id.active = 'yes';
            this.edit.sections.price.fields.taxtype_id.options = {'0':'No Taxes'};
            if( M.curBusiness.taxes != null && M.curBusiness.taxes.settings.types != null ) {
                for(i in M.curBusiness.taxes.settings.types) {
                    this.edit.sections.price.fields.taxtype_id.options[M.curBusiness.taxes.settings.types[i].type.id] = M.curBusiness.taxes.settings.types[i].type.name;
                }
            }
        } else {
            this.edit.sections.price.fields.taxtype_id.active = 'no';
            this.edit.sections.price.fields.taxtype_id.options = {'0':'No Taxes'};
        }

        //
        // Setup the visible fields
        //
        var ptype = null;
        for(i in M.curBusiness.products.settings.types) {
            if( M.curBusiness.products.settings.types[i].type.id == args.type_id ) {
                var ptype = M.curBusiness.products.settings.types[i].type;
                break;
            }
        }
        if( ptype != null ) {
            for(i in this.edit.sections.price.fields) {
                if( ptype.parent.prices[i] != null ) {
                    this.edit.sections.price.fields[i].active = 'yes';
                    if( ptype.parent.prices[i]['ui-hide'] != null 
                        && ptype.parent.prices[i]['ui-hide'] == 'yes') {
                        this.edit.sections.price.fields[i].visible = 'no';
                    } else {
                        this.edit.sections.price.fields[i].visible = 'yes';
                    }
                    if( ptype.parent.prices[i].default != null ) {
                        this.edit.sections.price.fields[i].default = ptype.parent.prices[i].default;
                    }
                } else {
                    this.edit.sections.price.fields[i].active = 'no';
                }
            }
        }
        //
        // Setup the pricepoint field
        //
        if( M.curBusiness.modules['ciniki.customers'] != null
            && (M.curBusiness.modules['ciniki.customers'].flags&0x1000) > 0 
            ) {
            if( M.curBusiness.customers.settings != null 
                && M.curBusiness.customers.settings.pricepoints != null 
                ) {
                this.edit.sections.price.fields.pricepoint_id.options = {'0':'None'};
                var pp = M.curBusiness.customers.settings.pricepoints;
                for(i in pp) {
                    this.edit.sections.price.fields.pricepoint_id.options[pp[i].pricepoint.id] = pp[i].pricepoint.name;
                }
            }
        }

        //
        // Setup the available_to flags and webflags
        //
        this.edit.sections.price.fields.webflags.flags = {'1':{'name':'Hidden'}};
        this.edit.sections.price.fields.available_to.flags = {};
        if( (M.curBusiness.modules['ciniki.customers'].flags&0x0112) > 0 ) {
            this.edit.sections.price.fields.available_to.flags['1'] = {'name':'Public'};
//          this.edit.sections.price.fields.available_to.visible = 'yes';
            this.edit.sections.price.fields.available_to.flags['5'] = {'name':'Customers'};
//          this.edit.sections.price.fields.available_to.visible = 'yes';
        } else {
//          this.edit.sections.price.fields.available_to.visible = 'no';
        }
        if( (M.curBusiness.modules['ciniki.customers'].flags&0x02) > 0 ) {
            this.edit.sections.price.fields.available_to.flags['6'] = {'name':'Members'};
            this.edit.sections.price.fields.webflags.flags['6'] = {'name':'Show Members Price'};
        }
        if( (M.curBusiness.modules['ciniki.customers'].flags&0x10) > 0 ) {
            this.edit.sections.price.fields.available_to.flags['7'] = {'name':'Dealers'};
            this.edit.sections.price.fields.webflags.flags['7'] = {'name':'Show Dealers Price'};
        }
        if( (M.curBusiness.modules['ciniki.customers'].flags&0x100) > 0 ) {
            this.edit.sections.price.fields.available_to.flags['8'] = {'name':'Distributors'};
            this.edit.sections.price.fields.webflags.flags['8'] = {'name':'Show Distributors Price'};
        }
        this.showEdit(cb, args.price_id, args.product_id);
    }

    this.showEdit = function(cb, pid, eid) {
        this.edit.reset();
        if( pid != null ) {
            this.edit.price_id = pid;
        }
        if( eid != null ) {
            this.edit.product_id = eid;
        }

        // Check if this is editing a existing price or adding a new one
        if( this.edit.price_id > 0 ) {
            this.edit.sections._buttons.buttons.delete.visible = 'yes';
            M.api.getJSONCb('ciniki.products.priceGet', {'business_id':M.curBusinessID, 
                'price_id':this.edit.price_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_products_prices.edit;
                    p.data = rsp.price;
                    p.product_id = rsp.price.product_id;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.edit.sections._buttons.buttons.delete.visible = 'no';
            this.edit.data = {'available_to':this.edit.sections.price.fields.available_to.default};
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.savePrice = function() {
        if( this.edit.price_id > 0 ) {
            var c = this.edit.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.products.priceUpdate', 
                    {'business_id':M.curBusinessID, 
                    'price_id':M.ciniki_products_prices.edit.price_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_products_prices.edit.close();
                    });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeForm('yes');
            M.api.postJSONCb('ciniki.products.priceAdd', 
                {'business_id':M.curBusinessID, 'product_id':this.edit.product_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_products_prices.edit.close();
                });
        }
    };

    this.deletePrice = function() {
        if( confirm("Are you sure you want to remove this price?") ) {
            M.api.getJSONCb('ciniki.products.priceDelete', 
                {'business_id':M.curBusinessID, 
                'price_id':this.edit.price_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_products_prices.edit.close();  
                });
        }
    };
}
