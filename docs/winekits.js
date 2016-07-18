//
function ciniki_products_winekits() {
    //
    // Panels
    //
    this.main = null;

    this.cb = null;
    this.toggleOptions = {'off':'Off', 'on':'On'};
    this.subscriptionOptions = {'off':'Unsubscribed', 'on':'Subscribed'};

    this.init = function() {
        //
        // The main panel, which lists the options for production
        //
        this.main = new M.panel('Wine Kits',
            'ciniki_products_winekits', 'main',
            'mc', 'medium', 'sectioned', 'ciniki.products.winekits.main');
        this.main.sections = {
            'wines':{'label':'', 'num_cols':3, 'type':'simplegrid', 'sortable':'yes', 
                'headerValues':['Name', 'Type', 'Duration'],
                'sortTypes':['text','text','number'],
                },
        };
        this.main.rowFn = function(s, i, d) {
            return 'M.ciniki_products_winekits.editProduct(\'M.ciniki_products_winekits.main.show();\',\'' + d.product.id + '\');';
        };
        this.main.sectionData = function(s) { return this.data; };
        this.main.noData = function(s) { return 'No wine kits found'; }
        this.main.dataMaps = {'wines':['name', 'wine_type', 'kit_length']};
        this.main.cellValue = function(s, i, col, d) { 
            return d.product[this.dataMaps[s][col]];
        };
        this.main.fieldValue = function(s, i, d) { return ''; }
        this.main.addClose('Back');

        this.edit = new M.panel('Edit Wine Kit',
            'ciniki_products_winekits', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.products.winekits.edit');
        this.edit.product_id = 0;
        this.edit.data = {};
        this.edit.sections = {
            'general':{'label':'', 'fields':{
                'name':{'label':'Name', 'type':'text'},
                'wine_type':{'label':'Type', 'type':'text'},
                'kit_length':{'label':'Duration', 'hint':'4, 5, 6, 8', 'type':'text'},
                }},
//          '_notes':{'label':'Notes', 'fields':{
//              'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
//              }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save wine kit', 'fn':'M.ciniki_products_winekits.saveProduct();'},
                }},
        };
        this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
        this.edit.addButton('save', 'Save', 'M.ciniki_products_winekits.saveProduct();');
        // this.edit.addLeftButton('cancel', 'Cancel', 'M.ciniki_products_winekits.main.show();');
        this.edit.addClose('Cancel');

        //
        // The search panel will list all search results for a string.  This allows more advanced searching,
        // and will search the entire strings, not just start of the string like livesearch
        //
        this.search = new M.panel('Search Results',
            'ciniki_products_winekits', 'search',
            'mc', 'medium', 'sectioned', 'ciniki.products.winekits.search');
        this.search.data = {};
        this.search.noData = function() { return 'No products found'; }
        this.search.listValue = function(s, i, d) { return d['product']['first'] + ' ' + d['product']['last']; };
        this.search.listFn = function(s, i, d) { return 'M.ciniki_products_winekits.editProduct(\'M.ciniki_products_winekits.searchProducts(M.ciniki_products_winekits.search.search_str)\',\'' + d.product.id + '\',);'; }
        this.search.addLeftButton('back', 'Back', 'M.ciniki_products_winekits.showMain();');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_products_winekits', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.cb = cb;
        this.main.cb = cb;
        if( args.product_id != null && args.product_id > 0 ) {
            this.editProduct(cb, args.product_id);
        } else {
            this.loadWineKits();
            this.showMain();
        }
    }

    //
    // Grab the stats for the business from the database and present the list of products.
    //
    this.showMain = function() {
        this.main.refresh();
        this.main.show();
    }

    this.reloadMain = function() {
        this.loadWineKits();
    }

    this.loadWineKits = function() {
        var rsp = M.api.getJSONCb('ciniki.products.listWineKits',{'business_id':M.curBusinessID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_products_winekits.main.data = rsp.products;
            M.ciniki_products_winekits.showMain();
        });
    }

    this.editProduct = function(cb, pID) {
        if( cb != null ) {
            this.edit.cb = cb;
        }
        M.ciniki_products_winekits.edit.product_id = pID;
        var rsp = M.api.getJSONCb('ciniki.products.get',{'business_id':M.curBusinessID, 'product_id':pID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_products_winekits.edit;
            p.data = rsp.product;
            p.refresh();
            p.show();
        });
    }

    this.saveProduct = function() {
        var c = this.edit.serializeForm('no');
        if( c != '' ) {
            var rsp = M.api.postJSONCb('ciniki.products.updateWineKit', 
                {'business_id':M.curBusinessID, 'product_id':M.ciniki_products_winekits.edit.product_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_products_winekits.reloadMain();
                });
        } else {
            M.ciniki_products_winekits.reloadMain();
        }
    }

}
