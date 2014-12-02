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
		this.menu = new M.panel('Products',
			'ciniki_products_main', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.products.main.menu');
		this.menu.data = {'tools':[]};
		this.menu.sections = {
			'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':1, 
				'headerValues':null,
				'hint':'product name', 
				'noData':'No products found',
				},
			'tools':{'label':'', 'visible':'no', 'type':'simplegrid', 'num_cols':1},
			'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'addTxt':'Add',
				'addFn':'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_main.showMenu();\',\'mc\',{\'product_id\':\'0\'});',
				},
			'suppliers':{'label':'Suppliers', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				},
			};
		this.menu.liveSearchCb = function(s, i, value) {
			if( s == 'search' && value != '' ) { 
				M.api.getJSONBgCb('ciniki.products.productSearch', {'business_id':M.curBusinessID, 
					'start_needle':value, 'status':10, 'limit':'10', 'reserved':'yes'}, function(rsp) { 
						M.ciniki_products_main.menu.liveSearchShow('search', null, M.gE(M.ciniki_products_main.menu.panelUID + '_' + s), rsp.products); 
				}); 
			return true;
			}   
		};  
		this.menu.liveSearchResultValue = function(s, f, i, j, d) {
			if( s == 'search' ) {
				switch(j) {
//					case 0: return (d.product.category!=''?d.product.category:'Uncategorized') + ' - ' + d.product.name;
					case 0: return d.product.name;
					//case 1: return (d.product.inventory_current_num!=''?d.product.inventory_current_num + (d.product.inventory_reserved!=null&&d.product.inventory_current_num>=0?' <span class="subdue">[' + d.product.inventory_reserved + ']</span>':''):'');
					case 1: return d.product.inventory_current_num + ((d.product.inventory_reserved!=null&&parseFloat(d.product.inventory_current_num)>=0)?' <span class="subdue">[' + d.product.inventory_reserved + ']</span>':'');
				}
			}
			return ''; 
		}   
		this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
			return 'M.startApp(\'ciniki.products.product\',null,\'M.ciniki_products_main.showMenu();\',\'mc\',{\'product_id\':\'' + d.product.id + '\'});';
		};  
		this.menu.liveSearchSubmitFn = function(s, search_str) {
			M.ciniki_products_main.showSearch('M.ciniki_products_main.showMenu();', search_str);
		};  
		this.menu.sectionData = function(s) { return this.data[s]; }
		this.menu.cellValue = function(s, i, j, d) {
			if( s == 'categories' ) {
				switch(j) {
					case 0: return ((d.category.name!='')?d.category.name:'*Uncategorized') + ' <span class="count">' + d.category.product_count + '</span>';
					}
			} else if( s == 'suppliers' ) {
				switch(j) {
					case 0: return ((d.supplier.name!='')?d.supplier.name:'*No Supplier') + ' <span class="count">' + d.supplier.product_count + '</span>';
				}
			} else if( s == 'tools' ) {
				return d.label;
			}
		};
		this.menu.rowFn = function(s, i, d) {
			if( s == 'categories' ) {
				if( d.category.permalink == '' ) {
					return 'M.ciniki_products_main.showList(\'M.ciniki_products_main.showMenu();\',\'category\',\'' + escape(d.category.permalink) + '\',\'Uncategorized\');';
				} else {
					return 'M.ciniki_products_main.showCategory(\'M.ciniki_products_main.showMenu();\',\'' + d.category.permalink + '\');';
				}
			} else if( s == 'suppliers' ) {
				return 'M.ciniki_products_main.showList(\'M.ciniki_products_main.showMenu();\',\'supplier_id\',\'' + d.supplier.id + '\',\'' + escape(d.supplier.name) + '\');';
			} else if( s == 'tools' ) {
				return d.fn;
			}
		};
		this.menu.addButton('add', 'Add', 'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_main.showMenu();\',\'mc\',{\'product_id\':\'0\'});');
		this.menu.addButton('tools', 'Tools', 'M.ciniki_products_main.tools.show(\'M.ciniki_products_main.showMenu();\');');
		this.menu.addClose('Back');

		//
		// The details for a category
		//
		this.category = new M.panel('Product Category',
			'ciniki_products_main', 'category',
			'mc', 'medium', 'sectioned', 'ciniki.products.main.category');
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
				}
			} else {
				switch(j) {
					case 0: return (d.category.name!=null?d.category.name:'Unknown') + (d.category.num_products!=null?' <span class="count">'+d.category.num_products+'</span>':'');
				}
			}
		};
		this.category.rowFn = function(s, i, d) {
			if( s == 'products' ) {
				return 'M.startApp(\'ciniki.products.product\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'product_id\':\'' + d.product.id + '\',\'list\':M.ciniki_products_main.category.data[\'' + s + '\']});';
			} else {
				return 'M.ciniki_products_main.showList(\'M.ciniki_products_main.showCategory();\',\'subcategory\',\'' + escape(this.category_permalink) + '\',\'' + this.title + ' - ' + escape(d.category.name) + '\',\'' + escape(d.category.permalink) + '\');';
			}
		};
		this.category.addButton('add', 'Add', 'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'product_id\':\'0\',\'category\':M.ciniki_products_main.list._type});');
		this.category.addButton('edit', 'Edit', 'M.startApp(\'ciniki.products.category\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'category\':M.ciniki_products_main.category.category_permalink,\'subcategory\':\'\'});');
		this.category.addClose('Back');
	

		//
		// The list of products
		//
		this.list = new M.panel('Products',
			'ciniki_products_main', 'list',
			'mc', 'medium', 'sectioned', 'ciniki.products.main.list');
		this.list.data = {};
		this.list.category = '';
		this.list.subcategory = '';
		this.list.sections = {
			'products':{'label':'Products', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null, 
				'addTxt':'Add Product',
				'addFn':'M.ciniki_products_main.addProduct();',
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
			}
		};
		this.list.rowFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.products.product\',null,\'M.ciniki_products_main.showList();\',\'mc\',{\'product_id\':\'' + d.product.id + '\',\'list\':M.ciniki_products_main.list.data.products});';
		};
		this.list.addButton('add', 'Add', 'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_main.showList();\',\'mc\',{\'product_id\':\'0\',\'category\':M.ciniki_products_main.list._type});');
		this.list.addClose('Back');

		//
		// The search panel will list all search results for a string.  This allows more advanced searching,
		// and will search the entire strings, not just start of the string like livesearch
		//
		this.search = new M.panel('Search Results',
			'ciniki_products_main', 'search',
			'mc', 'medium', 'sectioned', 'ciniki.products.main.search');
		this.search.data = {};
		this.search.sections = {
			'products':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'headerValues':['Product'], 
				},
		};
		this.search.sectionData = function(s) { return this.data[s]; }
		this.search.noData = function() { return 'No products found'; }
		this.search.cellValue = function(s, i, j, d) {
			switch(j) {
//				case 0: return d.product.category!=''?d.product.category:'Uncategorized';
				case 0: return (d.product.code!=''?d.product.code + ' - ':'') + d.product.name;
				case 1: return d.product.inventory_current_num + (d.product.inventory_reserved!=null?' <span class="subdue">[' + d.product.inventory_reserved + ']</span>':'');
			}
		};
		this.search.rowFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.products.product\',null,\'M.ciniki_products_main.showSearch();\',\'mc\',{\'product_id\':\'' + d.product.id + '\'});';
		};
		this.search.addClose('Back');

		//
		// The tools available to work on product records
		//
		this.tools = new M.panel('Product Tools',
			'ciniki_products_main', 'tools',
			'mc', 'narrow', 'sectioned', 'ciniki.products.main.tools');
		this.tools.data = {};
		this.tools.sections = {
			'inventory':{'label':'Inventory', 'list':{
				'duplicates_exact':{'label':'Manage Inventory Levels', 'fn':'M.startApp(\'ciniki.products.inventory\', null, \'M.ciniki_products_main.tools.show();\');'},
			}},
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
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_products_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		// Check if inventory enabled
		if( (M.curBusiness.modules['ciniki.products'].flags&0x04) > 0 ) {
			this.menu.sections.search.livesearchcols = 2;
			this.menu.sections.search.headerValues = ['Product', 'Inv [Rsv]'];
			this.list.sections.products.num_cols = 2;
			this.list.sections.products.headerValues = ['Product', 'Inv [Rsv]'];
			this.search.sections.products.num_cols = 2;
			this.search.sections.products.headerValues = ['Product', 'Inv [Rsv]'];
		} else {
			this.menu.sections.search.livesearchcols = 1;
			this.menu.sections.search.headerValues = null;
			this.list.sections.products.num_cols = 1;
			this.list.sections.products.headerValues = null;
		}
		
		this.menu.data.tools = {};
		this.menu.sections.tools.visible = 'no';
		if( (M.curBusiness.modules['ciniki.products'].flags&0x04) > 0 ) {
			this.menu.sections.tools.visible = 'yes';
			this.menu.data.tools['duplicates_exact'] = {'label':'Manage Inventory Levels', 'fn':'M.startApp(\'ciniki.products.inventory\', null, \'M.ciniki_products_main.showMenu();\');'};
		}

		if( args.search != null && args.search != '' ) {
			this.showSearch(cb, args.search);
		} else {
			this.showMenu(cb);
		}
	}

	//
	// Grab the stats for the business from the database and present the list of products.
	//
	this.showMenu = function(cb) {
		this.menu.sections.suppliers.visible=((M.curBusiness.modules['ciniki.products'].flags&0x08)>0)?'yes':'no';
		M.api.getJSONCb('ciniki.products.productStats', 
			{'business_id':M.curBusinessID, 'status':10}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_products_main.menu;
				p.data.categories = rsp.categories
				p.data.suppliers = rsp.suppliers;
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
				var p = M.ciniki_products_main.category;
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
							'addTxt':'Add Product',
							'addFn':'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'product_id\':0});',
						};
						p.data[i] = rsp.subcategorytypes[i].type.categories;
					}
				}
				if( rsp.products != null && rsp.products.length > 0 ) {
					p.sections['products'] = {'label':plist_label, 'type':'simplegrid', 'num_cols':1,
						'headerValues':null, 
						'addTxt':'Add Product',
						'addFn':'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'product_id\':0});',
						};
					p.data.products = rsp.products;
				}
				p.refresh();
				p.show(cb);
		});
	};

	this.showSubCategory = function(cb, category, subcat) {
		
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
			this.list.addButton('edit', 'Edit', 'M.startApp(\'ciniki.products.category\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'category\':M.ciniki_products_main.list._type,\'subcategory\':\'\'});');
		} else if( this.list._listtype == 'subcategory' ) {
			args['category'] = this.list._type;
			args['subcategory'] = this.list._type2;
			this.list.sections.products.label = unescape(this.list._title);
			this.list.addButton('edit', 'Edit', 'M.startApp(\'ciniki.products.category\',null,\'M.ciniki_products_main.showCategory();\',\'mc\',{\'category\':M.ciniki_products_main.list._type,\'subcategory\':M.ciniki_products_main.list._type2});');
		} else if( this.list._listtype == 'supplier_id' ) {
			args['supplier_id'] = this.list._type;
			this.list.sections.products.label = unescape(this.list._title);
		} else {
			return false;
		}
		M.api.getJSONCb('ciniki.products.productList', args, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_products_main.list;
			p.data = {'products':rsp.products};
			p.refresh();
			p.show(cb);
		});
	};

	this.addProduct = function() {
		if( this.list._listtype == 'category' ) {
			M.startApp('ciniki.products.edit',null,'M.ciniki_products_main.showList();','mc',{'product_id':'0','category':M.ciniki_products_main.list._type});
		} else if( this.list._listtype == 'subcategory' ) {
			M.startApp('ciniki.products.edit',null,'M.ciniki_products_main.showList();','mc',{'product_id':'0','category':M.ciniki_products_main.list._type});
		} else if( this.list._listtype == 'supplier_id' ) {
			M.startApp('ciniki.products.edit',null,'M.ciniki_products_main.showList();','mc',{'product_id':'0','supplier_id':M.ciniki_products_main.list._type,'supplier_name':escape(M.ciniki_products_main.list.sections.products.label)});
		}
	};

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
				var p = M.ciniki_products_main.search;
				p.data = {'products':rsp.products};
				p.refresh();
				p.show(cb);
			});
	};

}
