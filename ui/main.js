//
function ciniki_products_main() {
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
		this.main = new M.panel('Products',
			'ciniki_products_main', 'main',
			'mc', 'medium', 'sectioned', 'ciniki.products.main');
		this.main.sections = {
//			'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':1, 'hint':'First or Last name', 'noData':'No products found',
//				},
			'categories':{'label':'', 'type':'simplelist', 'list':{
				'winekits':{'label':'Wine Kits', 'fn':'M.startApp(\'ciniki.products.winekits\', null, \'M.ciniki_products_main.main.show();\');'},
				}},
			};
		this.main.listValue = function(s, i, d) { return d['label']; };
		this.main.listFn = function(s, i, d) { 
			if( d['fn'] != null ) { 
				return d['fn']; 
			} 
			return '';
		};
		this.main.fieldValue = function(s, i, d) { return ''; }
		this.main.liveSearchCb = function(s, i, value) {
			if( s == 'search' && value != '' ) { 
				M.api.getJSONBgCb('ciniki.products.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10'}, 
				function(rsp) { 
					M.ciniki_products_main.main.liveSearchShow('search', null, M.gE(M.ciniki_products_main.main.panelUID + '_' + s), rsp.products); 
				}); 
			return true;
			}   
		};  
		// FIXME: Finish the search
		this.main.liveSearchResultValue = function(s, f, i, j, d) {
			if( s == 'search' ) { return d.product.name; }
			return ''; 
		}   
		this.main.liveSearchResultRowFn = function(s, f, i, j, d) { 
			return 'M.ciniki_products_main.showCustomer(\'' + d.product.id + '\', \'M.ciniki_products_main.showMain();\');'; 
		};  
		this.main.liveSearchSubmitFn = function(s, search_str) {
			M.ciniki_products_main.searchProducts('M.ciniki_products_main.showMain();', search_str);
		};  

		// this.main.addButton('add', 'Add', 'M.ciniki_products_main.add.show();');
		this.main.addButton('tools', 'Tools', 'M.ciniki_products_main.tools.show(\'M.ciniki_products_main.showMain();\');');
		this.main.addClose('Back');

		//
		// The search panel will list all search results for a string.  This allows more advanced searching,
		// and will search the entire strings, not just start of the string like livesearch
		//
		this.search = new M.panel('Search Results',
			'ciniki_products_main', 'search',
			'mc', 'medium', 'sectioned', 'ciniki.products.search');
		this.search.sections = {
			'results':{'label':'', 'num_cols':1, 'type':'simplegrid', 'class':'dayschedule',
				'headerValues':null, 
				},
		};
		this.search.data = {};
		this.search.noData = function() { return 'No products found'; }
		this.search.listValue = function(s, i, d) { return d['product']['first'] + ' ' + d['product']['last']; };
		this.search.listFn = function(s, i, d) { return 'M.ciniki_products_main.showProduct(\'' + d['product']['id'] + '\',\'M.ciniki_products_main.searchProducts(M.ciniki_products_main.search.search_str)\');'; }
		this.search.addLeftButton('back', 'Back', 'M.ciniki_products_main.showMain();');

		//
		// The tools available to work on product records
		//
		this.tools = new M.panel('Product Tools',
			'ciniki_products_main', 'tools',
			'mc', 'narrow', 'sectioned', 'ciniki.products.main.tools');
		this.tools.data = {};
		this.tools.sections = {
			'tools':{'label':'Cleanup', 'list':{
				'duplicates_exact':{'label':'Find Exact Duplicates', 'fn':'M.startApp(\'ciniki.products.duplicates\', null, \'M.ciniki_products_main.tools.show();\',\'mc\',{\'type\':\'exact\'});'},
				'duplicates_soundex':{'label':'Find Similar Duplicates', 'fn':'M.startApp(\'ciniki.products.duplicates\', null, \'M.ciniki_products_main.tools.show();\',\'mc\',{\'type\':\'soundex\'});'},
			}},
			};
		this.tools.addClose('Back');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_products_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.cb = cb;
		this.main.cb = cb;
//		if( args['product_id'] != null && args['product_id'] > 0 ) {
//			this.showProduct(args['product_id'], cb);
//		} else {
			this.showMain();
//		}
	}

	//
	// Grab the stats for the business from the database and present the list of products.
	//
	this.showMain = function() {
		this.main.show();
	}

}
