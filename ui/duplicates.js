//
function ciniki_products_duplicates() {
    //
    // Panels
    //
    this.menu = null;

    this.statusOptions = {
        '10':'Ordered',
        '20':'Started',
        '25':'SG Ready',
        '30':'Racked',
        '40':'Filtered',
        '60':'Bottled',
        '100':'Removed',
        '*':'Unknown',
        };

    this.init = function() {
        //
        // The main panel, which lists the options for production
        //
        this.list = new M.panel('Duplicate Products',
            'ciniki_products_duplicates', 'list',
            'mc', 'medium', 'sectioned', 'ciniki.products.duplicates.list');
        this.list.data = {};
        this.list.sections = {
            'matches':{'label':'Duplicate Products', 'num_cols':4, 'type':'simplegrid', 
                'headerValues':['ID', 'Name', 'ID', 'Name'],
                'noData':'No potential product matches found',
                },
            };
        this.list.sectionData = function(s) {
            return this.data[s];
        };
        this.list.noData = function(s) { return 'No potential matches found'; }
        this.list.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return d.match.p1_id;
                case 1: return d.match.p1_name;
                case 2: return d.match.p2_id;
                case 3: return d.match.p2_name;
            }
            return '';
        };
        this.list.rowFn = function(s, i, d) { 
            return 'M.ciniki_products_duplicates.showMatch(\'M.ciniki_products_duplicates.showList();\',\'' + d.match.p1_id + '\',\'' + d.match.p2_id + '\');'; 
        };
        this.list.addClose('Back');

        //
        // The match2 panel is the second product record that matches.
        // It must be listed first, as it's referenced by match1.
        //
        this.match2 = new M.panel('Product Match',
            'ciniki_products_duplicates', 'match2',
            'mc', 'medium', 'sectioned', 'ciniki.products.duplicates.match');
        this.match2.product_id = 0;
        this.match2.data = {};
        this.match2.sections = {
            'details':{'label':'', 'type':'simplegrid', 'num_cols':2,
                'headerValues':null,
                'cellClasses':['label', ''],
                'dataMaps':['name', 'value'],
                },
            'currentwineproduction':{'label':'Current Orders', 'type':'simplegrid', 'visible':'no', 'num_cols':7,
                'sortable':'yes',
                'headerValues':['INV#', 'Wine', 'OD', 'SD', 'RD', 'FD', 'BD'], 
                'cellClasses':['multiline', 'multiline', 'multiline aligncenter', 'multiline aligncenter', 'multiline aligncenter', 'multiline aligncenter', 'multiline aligncenter'],
                'dataMaps':['invoice_number', 'wine_name', 'order_date', 'start_date', 'racking_date', 'filtering_date', 'bottling_date'],
                'noData':'No current orders',
                },
            'pastwineproduction':{'label':'Past Orders', 'type':'simplegrid', 'visible':'no', 'num_cols':7,
                'sortable':'yes',
                'cellClasses':['multiline', 'multiline', 'multiline aligncenter', 'multiline aligncenter', 'multiline aligncenter', 'multiline aligncenter', 'multiline aligncenter'],
                'headerValues':['INV#', 'Wine', 'OD', 'SD', 'RD', 'FD', 'BD'], 
                'dataMaps':['invoice_number', 'wine_name', 'order_date', 'start_date', 'racking_date', 'filtering_date', 'bottle_date'],
                'noData':'No past orders',
                },
            '_buttons':{'label':'', 'buttons':{}},
            };
        this.match2.noData = function(s) {
            return this.sections[s].noData;
        };
        this.match2.sectionData = function(s) {
            return this.data[s];
        };
        this.match2.cellColour = function(s, i, j, d) {
            return '';
        };
        this.match2.fieldValue = function(s, i, d) {
            if( i == 'notes' && this.data[i] == '' ) { return 'No notes'; }
            return this.data[i];
        };
        this.match2.cellValue = function(s, i, j, d) {
            if( s == 'details' || s == 'tenant' || s == 'phones' ) {
                if( j == 0 ) { return d.label; }
                if( j == 1 ) { return d.value; }
            }
            else if( s == 'currentwineproduction' || s == 'pastwineproduction' ) {
                if( j == 0 ) {
                    return '<span class="maintext">' + d.order.invoice_number + '</span><span class="subtext">' + M.ciniki_products_duplicates.statusOptions[d.order.status] + '</span>';
                } else if ( j == 1 ) {
                    return '<span class="maintext">' + d.order.wine_name + '</span><span class="subtext">' + d.order.customer_name + '</span>';
                
                } else if( (s == 'currentwineproduction' || s == 'pastwineproduction') && j > 1 && j < 7 ) {
                    var dt = d.order[this.sections[s].dataMaps[j]];
                    // Check for missing filter date, and try to take a guess
                    if( dt == null && j == 6 ) {
                        var dt = d.order.approx_filtering_date;
                        if( dt != null ) {  
                            return dt.replace(/(...)\s([0-9]+),\s([0-9][0-9][0-9][0-9])/, "<span class='maintext'>$1<\/span><span class='subtext'>~$2<\/span>");
                        }
                        return '';
                    }
                    if( dt != null && dt != '' ) {
                        return dt.replace(/(...)\s([0-9]+),\s([0-9][0-9][0-9][0-9])/, "<span class='maintext'>$1<\/span><span class='subtext'>$2<\/span>");
                    } else {
                        return '';
                    }
                }
                return d.order[this.sections[s].dataMaps[j]];
            }
            return this.data[s][i];
        };
        this.match2.cellFn = function(s, i, j, d) {
            return '';
        };
        this.match2.rowFn = function(s, i, d) {
            if( s == 'currentwineproduction' || s == 'pastwineproduction' ) {
                return 'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_products_duplicates.showMatch();\',\'mc\',{\'order_id\':' + d.order.id + '});';
            }
            return d.Fn;
        };

        //
        // The first product record, will be displayed on the left or top
        //
        this.match1 = new M.panel('Product Match',
            'ciniki_products_duplicates', 'match1',
            'mc', 'medium', 'sectioned', 'ciniki.products.duplicates.match');
        this.match1.data = {};
        this.match1.sections = {};
        for(var attr in this.match2.sections) {
            this.match1.sections[attr] = this.match2.sections[attr];
        }
        this.match1.noData = this.match2.noData;
        this.match1.sectionData = this.match2.sectionData;
        this.match1.cellColour = this.match2.cellColour;
        this.match1.fieldValue = this.match2.fieldValue;
        this.match1.cellValue = this.match2.cellValue;
        this.match1.cellFn = this.match2.cellFn;
        this.match1.rowFn = this.match2.rowFn;
        this.match1.sidePanel = this.match2;
        this.match1.addClose('Back');

        //
        // Setup buttons
        //
        this.match2.sections._buttons = {'buttons':{
            'merge':{'label':'< Merge', 
                'fn':'M.ciniki_products_duplicates.mergeProduct(M.ciniki_products_duplicates.match1.product_id, M.ciniki_products_duplicates.match2.product_id);'},
            'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.products.main\',null,\'M.ciniki_products_duplicates.showMatch();\',\'mc\',{\'editproductid\':M.ciniki_products_duplicates.match2.product_id});'},
            'delete':{'label':'Delete', 'visible':'yes', 'fn':'M.ciniki_products_duplicates.deleteProduct(M.ciniki_products_duplicates.match2.product_id);'},
            }};
        this.match1.sections._buttons = {'buttons':{
            'merge':{'label':'Merge >',
                'fn':'M.ciniki_products_duplicates.mergeProduct(M.ciniki_products_duplicates.match2.product_id, M.ciniki_products_duplicates.match1.product_id);'},
            'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.products.main\',null,\'M.ciniki_products_duplicates.showMatch();\',\'mc\',{\'editproductid\':M.ciniki_products_duplicates.match1.product_id});'},
            'delete':{'label':'Delete', 'visible':'yes', 'fn':'M.ciniki_products_duplicates.deleteProduct(M.ciniki_products_duplicates.match1.product_id);'},
            }};
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_products_duplicates', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.showList(cb, args.type);
    };

    //
    // Grab the stats for the tenant from the database and present the list of products.
    //
    this.showList = function(cb, type) {
        if( type != null ) {
            this.list.find_type = type;
        }
        //
        // Grab list of recently updated products
        //
        var rsp = M.api.getJSONCb('ciniki.products.productDuplicates', {'tnid':M.curTenantID,
            'type':this.list.find_type}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                var p = M.ciniki_products_duplicates.list;
                p.data.matches = rsp.matches;
                p.refresh();
                p.show(cb);
            });
    };

    this.showMatch = function(cb, pid1, pid2) {
        if( pid1 != null ) {
            this.match1.product_id = pid1;
            this.match2.product_id = pid2;
        }
        this.match1.sections._buttons.buttons.delete.visible = 'yes';
        this.match2.sections._buttons.buttons.delete.visible = 'yes';
        M.startLoad();
        var rsp = M.api.getJSONCb('ciniki.products.productGet', {'tnid':M.curTenantID, 
            'product_id':this.match1.product_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.stopLoad();
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_products_duplicates.match1.data = rsp.product;
                M.ciniki_products_duplicates.match1.data.details = {
                    'prefix':{'label':'Name', 'value':rsp.product.name},
                    'wine_type':{'label':'Type', 'value':rsp.product.wine_type},
                    'kit_length':{'label':'Length', 'value':rsp.product.kit_length},
                }
                M.ciniki_products_duplicates.showMatchFinish(cb);
            });
    };

    this.showMatchFinish = function(cb) {
        M.api.getJSONCb('ciniki.products.productGet', {'tnid':M.curTenantID, 
            'product_id':this.match2.product_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.stopLoad();
                    M.api.err(rsp);
                    return false;
                }
                var p1 = M.ciniki_products_duplicates.match1;
                var p2 = M.ciniki_products_duplicates.match2;
                p2.data = rsp.product;
                p2.data.details = {
                    'prefix':{'label':'Name', 'value':rsp.product.name},
                    'wine_type':{'label':'Type', 'value':rsp.product.wine_type},
                    'kit_length':{'label':'Length', 'value':rsp.product.kit_length},
                    };

                // Reset visible sections
                p1.sections.currentwineproduction.visible = 'no';
                p2.sections.currentwineproduction.visible = 'no';
                p1.sections.pastwineproduction.visible = 'no';
                p2.sections.pastwineproduction.visible = 'no';

                if( M.curTenant.modules['ciniki.wineproduction'] != null ) {
                    p1.sections.currentwineproduction.visible = 'yes';
                    p2.sections.currentwineproduction.visible = 'yes';
                    p1.sections.pastwineproduction.visible = 'yes';
                    p2.sections.pastwineproduction.visible = 'yes';
                    // Get wine production
                    var rsp = M.api.getJSON('ciniki.wineproduction.list', {'tnid':M.curTenantID, 
                        'product_id':p1.product_id});
                    if( rsp.stat != 'ok' ) {
                        M.stopLoad();
                        M.api.err(rsp);
                        return false;
                    } 
                    p1.data.currentwineproduction = [];
                    p1.data.pastwineproduction = [];
                    var i = 0;
                    for(i in rsp.orders) {
                        var order = rsp.orders[i].order;
                        if( order.status < 50 ) {
                            p1.data.currentwineproduction.push(rsp.orders[i]);
                        } else  {
                            p1.data.pastwineproduction.push(rsp.orders[i]);
                        }
                    }
                    if( rsp.orders.length > 0 ) {
                        p1.sections._buttons.buttons.delete.visible = 'no';
                    }

                    // Get second product wine production
                    var rsp = M.api.getJSON('ciniki.wineproduction.list', {'tnid':M.curTenantID, 
                        'product_id':p2.product_id});
                    if( rsp.stat != 'ok' ) {
                        M.stopLoad();
                        M.api.err(rsp);
                        return false;
                    } 
                    p2.data.currentwineproduction = [];
                    p2.data.pastwineproduction = [];
                    var i = 0;
                    for(i in rsp.orders) {
                        var order = rsp.orders[i].order;
                        if( order.status < 50 ) {
                            p2.data.currentwineproduction.push(rsp.orders[i]);
                        } else  {
                            p2.data.pastwineproduction.push(rsp.orders[i]);
                        }
                    }
                    if( rsp.orders.length > 0 ) {
                        p2.sections._buttons.buttons.delete.visible = 'no';
                    }
                }

                M.stopLoad();
                p1.refresh();
                p1.show(cb);
            });
    };

    this.mergeProduct = function(pid1, pid2) {
        var rsp = M.api.getJSONCb('ciniki.products.productMerge', {'tnid':M.curTenantID, 
            'primary_product_id':pid1, 'secondary_product_id':pid2}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_products_duplicates.showMatch();
            });
    };

    this.deleteProduct = function(pid) {
        if( pid != null && pid > 0 ) {
            if( confirm("Are you sure you want to remove this product?") ) {
                var rsp = M.api.getJSONCb('ciniki.products.productDelete', 
                    {'tnid':M.curTenantID, 'product_id':pid}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_products_duplicates.match1.close();
                    });
            }
        }
    }
}
