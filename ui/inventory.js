//
function ciniki_products_inventory() {
    //
    // Panels
    //
    this.init = function() {
        //
        // The inventory panel, which lists the options for production
        //
        this.menu = new M.panel('Product Inventory',
            'ciniki_products_inventory', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.products.inventory.menu');
        this.menu.sections = {
            'search':{'label':'Search', 'autofocus':'yes', 'type':'livesearchgrid', 'livesearchcols':1, 
                'headerValues':null,
                'hint':'product name', 
                'noData':'No products found',
                },
            'reports':{'label':'', 'list':{
                'full':{'label':'All Products', 'fn':'M.ciniki_products_inventory.showList(\'M.ciniki_products_inventory.showMenu();\',\'\',\'\',\'All Products\');'},
                }},
            'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null,
                },
            };
        this.menu.liveSearchCb = function(s, i, value) {
            if( s == 'search' && value != '' ) { 
                M.api.getJSONBgCb('ciniki.products.productSearch', {'business_id':M.curBusinessID, 
                    'start_needle':value, 'status':10, 'limit':'10', 'reserved':'yes', 'inventoried':'yes'}, function(rsp) { 
                        M.ciniki_products_inventory.menu.liveSearchShow('search', null, M.gE(M.ciniki_products_inventory.menu.panelUID + '_' + s), rsp.products); 
                }); 
            return true;
            }   
        };  
        this.menu.liveSearchResultValue = function(s, f, i, j, d) {
            if( s == 'search' ) {
                switch(j) {
                    case 0: return (d.product.code!=''?d.product.code + ' - ':'') + d.product.name;
                    case 1: return d.product.inventory_current_num;
                    case 2: return (parseFloat(d.product.inventory_current_num)>=0&&d.product.rsv!=null?(d.product.rsv==0?'<span class="subdue">0</span>':d.product.rsv):'');
                    case 3: return (parseFloat(d.product.inventory_current_num)>=0&&d.product.bo!=null?(d.product.bo==0?'<span class="subdue">0</span>':d.product.bo):'');
                }
            }
            return ''; 
        }   
        this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
            if( M.curBusiness.permissions.owners != null 
                || M.curBusiness.permissions.employees != null 
                || (M.userPerms&0x01) == 1 
                ) {
                return 'M.ciniki_products_inventory.showEdit(\'M.ciniki_products_inventory.showMenu();\',\'' + d.product.id + '\');';
            }
            return '';
        };  
        this.menu.liveSearchSubmitFn = function(s, search_str) {
            // FIXME: Check if one entry returned, and display inventory edit.
            M.ciniki_products_inventory.showSearch('M.ciniki_products_inventory.showMenu();', search_str);
        };  
        this.menu.sectionData = function(s) { 
            if( s == 'reports' ) { return this.sections[s].list; }
            return this.data[s]; 
        }
        this.menu.cellValue = function(s, i, j, d) {
            if( s == 'categories' ) {
                switch(j) {
                    case 0: return ((d.category.name!='')?d.category.name:'*Uncategorized') + ' <span class="count">' + d.category.product_count + '</span>';
                    }
            } else if( s == 'suppliers' ) {
                switch(j) {
                    case 0: return ((d.supplier.name!='')?d.supplier.name:'*No Supplier') + ' <span class="count">' + d.supplier.product_count + '</span>';
                }
            }
        };
        this.menu.rowFn = function(s, i, d) {
            if( s == 'categories' ) {
                if( d.category.permalink == '' ) {
                    return 'M.ciniki_products_inventory.showList(\'M.ciniki_products_inventory.showMenu();\',\'category\',\'' + escape(d.category.permalink) + '\',\'Uncategorized\');';
                } else {
                    return 'M.ciniki_products_inventory.showCategory(\'M.ciniki_products_inventory.showMenu();\',\'' + d.category.permalink + '\');';
                }
            } else if( s == 'suppliers' ) {
                return 'M.ciniki_products_inventory.showList(\'M.ciniki_products_inventory.showMenu();\',\'supplier_id\',\'' + d.supplier.id + '\',\'' + escape(d.supplier.name) + '\');';
            }
        };
        this.menu.addClose('Back');

        //
        // The details for a category
        //
        this.category = new M.panel('Product Category',
            'ciniki_products_inventory', 'category',
            'mc', 'medium', 'sectioned', 'ciniki.products.inventory.category');
        this.category.data = {};
        this.category.category = '';
        this.category.sections = {};
        this.category.sectionData = function(s) {
            return this.data[s];
        };
        this.category.noData = function() { return 'No products found'; }
        this.category.cellValue = function(s, i, j, d) {
            if( s == 'products' ) {
                switch(j) {
                    case 0: return (d.product.code!=''?d.product.code+' - ':'') + d.product.name;
                    case 1: return d.product.inventory_current_num;
                    case 2: return (d.product.rsv!=null?(d.product.rsv==0?'<span class="subdue">0</span>':d.product.rsv):'');
                    case 3: return (d.product.bo!=null?(d.product.bo==0?'<span class="subdue">0</span>':d.product.bo):'');
                }
            } else {
                switch(j) {
                    case 0: return (d.category.name!=null?d.category.name:'Unknown') + (d.category.num_products!=null?' <span class="count">'+d.category.num_products+'</span>':'');
                }
            }
        };
        this.category.rowFn = function(s, i, d) {
            if( s == 'products' ) {
                return 'M.ciniki_products_inventory.showEdit(\'M.ciniki_products_inventory.showCategory();\',\'' + d.product.id + '\');';
            } else {
                return 'M.ciniki_products_inventory.showList(\'M.ciniki_products_inventory.showCategory();\',\'subcategory\',\'' + escape(this.category_permalink) + '\',\'' + this.title + ' - ' + escape(d.category.name) + '\',\'' + escape(d.category.permalink) + '\');';
            }
        };
        this.category.addClose('Back');
    

        //
        // The list of products
        //
        this.list = new M.panel('Products',
            'ciniki_products_inventory', 'list',
            'mc', 'medium mediumflex', 'sectioned', 'ciniki.products.inventory.list');
        this.list.data = {};
        this.list.category = '';
        this.list.subcategory = '';
        this.list.sections = {
            'products':{'label':'Products', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null, 
                },
        };
        this.list.sectionData = function(s) {
            return this.data[s];
        };
        this.list.noData = function() { return 'No products found'; }
        this.list.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return (d.product.code!=''?d.product.code+' - ':'') + d.product.name;
                case 1: return d.product.inventory_current_num;
                case 2: return (d.product.rsv!=null?(d.product.rsv==0?'<span class="subdue">0</span>':d.product.rsv):'');
                case 3: return (d.product.bo!=null?(d.product.bo==0?'<span class="subdue">0</span>':d.product.bo):'');
            }
        };
        this.list.rowFn = function(s, i, d) {
            if( M.curBusiness.permissions.owners != null 
                || M.curBusiness.permissions.employees != null 
                || (M.userPerms&0x01) == 1 
                ) {
                return 'M.ciniki_products_inventory.showEdit(\'M.ciniki_products_inventory.showList();\',\'' + d.product.id + '\');';
            }
            return '';
        };
        this.list.addClose('Back');

        //
        // The edit panel for inventory
        //
        this.edit = new M.panel('Product Inventory',
            'ciniki_products_inventory', 'edit',
            'mc', 'narrow', 'sectioned', 'ciniki.products.inventory.edit');
        this.edit.product_id = 0;
        this.edit.sections = {
//          'details':{'label':'Product', 'type':'simplegrid', 'num_cols':2,
//              'cellClasses':['label',''],
//          },
            'details':{'label':'', 'fields':{
                'name':{'label':'', 'hidelabel':'yes', 'type':'noedit', 'history':'no'},
            }},
            'inventory':{'label':'Inventory', 'fields':{
                'old_inventory_current_num':{'label':'Old', 'type':'noedit', 'history':'no'},
                'inventory_current_num':{'label':'New', 'type':'number', 'autofocus':'yes', 'enterFn':'M.ciniki_products_inventory.saveInventory();', 'size':'small'},
            }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_products_inventory.saveInventory();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) {
            if( i == 'old_inventory_current_num' ) { return this.data['inventory_current_num']; }
            if( i == 'inventory_current_num' ) { return ''; }
            return this.data[i];
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.products.productHistory', 'args':{'business_id':M.curBusinessID,
                'product_id':this.product_id, 'field':i}};
        }
        this.edit.addButton('save', 'Save', 'M.ciniki_products_inventory.saveInventory();');
        this.edit.addClose('Cancel');

        //
        // The search panel will list all search results for a string.  This allows more advanced searching,
        // and will search the entire strings, not just start of the string like livesearch
        //
        this.search = new M.panel('Search Results',
            'ciniki_products_inventory', 'search',
            'mc', 'medium', 'sectioned', 'ciniki.products.inventory.search');
        this.search.data = {};
        this.search.sections = {
            'products':{'label':'', 'type':'simplegrid', 'num_cols':4,
                'headerValues':['Product', 'Inventory', 'Reserved', 'Backordered'], 
                },
        };
        this.search.sectionData = function(s) { return this.data[s]; }
        this.search.noData = function() { return 'No products found'; }
        this.search.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return ((d.product.code!=null&&d.product.code!='')?d.product.code+' - ':'') + d.product.name;
                case 1: return d.product.inventory_current_num;
                case 2: return (d.product.rsv!=null?(d.product.rsv==0?'<span class="subdue">0</span>':d.product.rsv):'');
                case 3: return (d.product.bo!=null?(d.product.bo==0?'<span class="subdue">0</span>':d.product.bo):'');
            }
        };
        this.search.rowFn = function(s, i, d) {
            return 'M.ciniki_products_inventory.showEdit(\'M.ciniki_products_inventory.showSearch();\',\'' + d.product.id + '\');';
        };
        this.search.addClose('Back');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_products_inventory', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        // Check if inventory enabled
        if( (M.curBusiness.modules['ciniki.products'].flags&0x04) > 0 ) {
            this.menu.sections.search.livesearchcols = 4;
            this.menu.sections.search.headerValues = ['Product', 'Inventory', 'Reserved', 'Backordered'];
            this.list.sections.products.num_cols = 4;
            this.list.sections.products.headerValues = ['Product', 'Inventory', 'Reserved', 'Backordered'];
        } else {
            this.menu.sections.search.livesearchcols = 1;
            this.menu.sections.search.headerValues = null;
            this.list.sections.products.num_cols = 1;
            this.list.sections.products.headerValues = null;
        }
    
        var perms = M.curBusiness.permissions;
    
        if( perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1 ) {
            this.list.addButton('download', 'Export', 'M.ciniki_products_inventory.downloadList();');
        } else {
            this.list.delButton('download');
        }
        

        if( args.product_id != null && args.product_id > 0 
            // Check owner, employee or sysadmin
            && (perms.owners != null || perms.employees != null || (M.userPerms&0x01)==1) ) {
            this.showEdit(cb, args.product_id);
        } else if( args.search != null && args.search != '' ) {
            this.showSearch(cb, args.search);
        } else {
            this.showMenu(cb);
        }
    }

    //
    // Grab the stats for the business from the database and present the list of products.
    //
    this.showMenu = function(cb) {
        M.api.getJSONCb('ciniki.products.productStats', 
            {'business_id':M.curBusinessID, 'status':10}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_products_inventory.menu;
                p.data = {'categories':rsp.categories, 'suppliers':rsp.suppliers};
                p.refresh();
                p.show(cb);
            });
    };

    //
    // Show the details about a category
    //
    this.showCategory = function(cb, c) {
        if( c != null ) { this.category.category_permalink = c; }
        M.api.getJSONCb('ciniki.products.categoryDetails', {'business_id':M.curBusinessID,
            'category':this.category.category_permalink}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_products_inventory.category;
                if( rsp.details.category_title != null ) {
                    p.title = rsp.details.category_title;
                }
                p.data = {};
                p.sections = {};
                var plist_label = 'Products';
                if( rsp.subcategorytypes != null ) {
                    for(i in rsp.subcategorytypes) {
                        plist_label = 'Uncategorized Products';
                        p.sections[i] = {'label':rsp.subcategorytypes[i].type.name, 
                            'type':'simplegrid', 'num_cols':1,
                            'headerValues':null,
                        };
                        p.data[i] = rsp.subcategorytypes[i].type.categories;
                    }
                }
                if( rsp.products != null && rsp.products.length > 0 ) {
                    if( (M.curBusiness.modules['ciniki.products'].flags&0x04) > 0 ) {
                        p.sections['products'] = {'label':plist_label, 'type':'simplegrid', 'num_cols':4,
                            'headerValues':['Product', 'Inventory', 'Reserved', 'Backordered'], 
                            };
                    } else {
                        p.sections['products'] = {'label':plist_label, 'type':'simplegrid', 'num_cols':1,
                            'headerValues':null, 
                            };
                    }
                    p.data.products = rsp.products;
                }
                p.refresh();
                p.show(cb);
        });
    };

    //
    // Show the list of products for a category
    //
    this.showList = function(cb, listtype, type, title, type2) {
        var args = {'business_id':M.curBusinessID};
        if( listtype != null ) {
            this.list._listtype = listtype;
            this.list._type = unescape(type);
            if( type2 != null ) { this.list._type2 = unescape(type2); } else { this.list._type2 = ''; }
            this.list._title = unescape(title);
        }
        this.list.sections.products.label = 'Products';
        this.list.delButton('edit');
        if( this.list._listtype == 'category' ) {
            args['category'] = this.list._type;
            this.list.sections.products.label = unescape(this.list._title);
        } else if( this.list._listtype == 'subcategory' ) {
            args['category'] = this.list._type;
            args['subcategory'] = this.list._type2;
            this.list.sections.products.label = unescape(this.list._title);
        } else if( this.list._listtype == 'supplier_id' ) {
            args['supplier_id'] = this.list._type;
            this.list.sections.products.label = unescape(this.list._title);
        } else if( this.list._listtype == '' ) {
            this.list.sections.products.label = unescape(this.list._title);
        } else {
            return false;
        }
        args['reserved'] = 'yes';
        M.api.getJSONCb('ciniki.products.productList', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_products_inventory.list;
            p.data = {'products':rsp.products};
            p.refresh();
            p.show(cb);
        });
    };

    this.downloadList = function() {
        var args = {'business_id':M.curBusinessID, 'output':'excel'};
        if( this.list._listtype == 'category' ) {
            args['category'] = this.list._type;
        } else if( this.list._listtype == 'subcategory' ) {
            args['category'] = this.list._type;
            args['subcategory'] = this.list._type2;
        } else if( this.list._listtype == 'supplier_id' ) {
            args['supplier_id'] = this.list._type;
        }
        args['reserved'] = 'yes';
        args['status'] = 10;
        M.api.openFile('ciniki.products.productList', args);
    }


    //
    // Show the full search results for active and inactive products
    //
    this.showSearch = function(cb, search_str) {
        if( search_str != null ) { this.search.search_str = search_str; }
        M.api.getJSONCb('ciniki.products.productSearch', {'business_id':M.curBusinessID, 
            'start_needle':this.search.search_str, 'limit':'101', 'reserved':'yes'}, function(rsp) { 
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                if( rsp.products != null && rsp.products[0] != null && rsp.products[1] == null ) {
                    var p = M.ciniki_products_inventory.edit;
                    p.product_id = rsp.products[0].product.id;
                    p.data = rsp.products[0].product;
                    p.refresh();
                    p.show(cb);
                } else {
                    var p = M.ciniki_products_inventory.search;
                    p.data = {'products':rsp.products};
                    p.refresh();
                    p.show(cb);
                }
            });
    };

    this.showEdit = function(cb, pid) {
        if( pid != null ) { this.edit.product_id = pid; }
        M.api.getJSONCb('ciniki.products.productGet', {'business_id':M.curBusinessID,
            'product_id':this.edit.product_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_products_inventory.edit;
                p.data = rsp.product;
                p.refresh();
                p.show(cb); 
        });
    };

    this.saveInventory = function() {
        var c = this.edit.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.products.productUpdate', {'business_id':M.curBusinessID,
                'product_id':M.ciniki_products_inventory.edit.product_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                M.ciniki_products_inventory.edit.close();
            });
        }
    };

}
