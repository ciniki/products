//
function ciniki_products_relationships() {
	//
	// Panels
	//
	this.main = null;

	this.relationshipOptions = {
		'10':'Reciprocal',
		'11':'One Way',
		};

	this.init = function() {
		//
		// The panel to edit an existing relationship
		//
		this.edit = new M.panel('Relationship',
			'ciniki_products_relationships', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.products.relationships.edit');
		this.edit.data = {};
		this.edit.sections = {
			'relationship':{'label':'Similar Product', 'fields':{
				'product_id':{'label':'Product', 'hidelabel':'no', 'hint':'Search for product', 'active':'no', 'type':'fkid', 'livesearch':'yes'},
				'related_id':{'label':'Product', 'hidelabel':'no', 'hint':'Search for product', 'active':'no', 'type':'fkid', 'livesearch':'yes'},
				}},
			'_type':{'label':'', 'fields':{
				'relationship_type':{'label':'Type', 'type':'toggle', 'toggles':this.relationshipOptions},
				}},
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save Relationship', 'fn':'M.ciniki_products_relationships.saveRelationship();'},
				'delete':{'label':'Delete Relationship', 'fn':'M.ciniki_products_relationships.deleteRelationship();'},
				}},
			};
		this.edit.fieldValue = function(s, i, d) { 
			if( i == 'related_id_fkidstr' || i == 'product_id_fkidstr' ) { return this.data['product_name']; }
			if( this.data[i] == null ) { return ''; }
			return this.data[i]; 
		};
		this.edit.liveSearchCb = function(s, i, value) {
			if( i == 'related_id' || i == 'product_id' ) {
				var rsp = M.api.getJSONBgCb('ciniki.products.productSearch',
					{'business_id':M.curBusinessID, 'start_needle':value, 'limit':25},
					function(rsp) {
						M.ciniki_products_relationships.edit.liveSearchShow(s, i, M.gE(M.ciniki_products_relationships.edit.panelUID + '_' + i), rsp.products);
					});
			}
		};
		this.edit.liveSearchResultValue = function(s, f, i, j, d) {
			if( f == 'related_id' || f == 'product_id' ) { return d.product.name; }
			return '';
		};
		this.edit.liveSearchResultRowFn = function(s, f, i, j, d) {
			if( f == 'related_id' || f == 'product_id' ) {
				return 'M.ciniki_products_relationships.edit.updateCustomer(\'' + s + '\',\'' + f + '\',\'' + escape(d.product.name) + '\',\'' + d.product.id + '\');';
			}
		};
		this.edit.updateCustomer = function(s, fid, product_name, product_id) {
			M.gE(this.panelUID + '_' + fid).value = product_id;
			M.gE(this.panelUID + '_' + fid + '_fkidstr').value = unescape(product_name);
			this.removeLiveSearch(s, fid);
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.products.relationshipHistory', 'args':{'business_id':M.curBusinessID, 
				'product_id':this.product_id, 'relationship_id':this.relationship_id, 'field':i}};
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_products_relationships.saveRelationship();');
		this.edit.addClose('cancel');
	};

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
		var appContainer = M.createContainer(appPrefix, 'ciniki_products_relationships', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		if( args.relationship_id != null && args.relationship_id > 0 
			&& args.product_id != null && args.product_id > 0 ) {
			// Edit an existing relationship
			this.showEdit(cb, args.product_id, args.relationship_id);
		} else if( args.product_id != null && args.product_id > 0 ) {
			// Add a new relationship for a product
			this.showEdit(cb, args.product_id, 0);
		}
	};

	this.showEdit = function(cb, pid, rid) {
		if( pid != null ) { this.edit.product_id = pid; }
		if( rid != null ) { this.edit.relationship_id = rid; }
		if( this.edit.relationship_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			var rsp = M.api.getJSONCb('ciniki.products.relationshipGet', 
				{'business_id':M.curBusinessID, 'product_id':this.edit.product_id, 
				'relationship_id':this.edit.relationship_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_products_relationships.edit;
					p.data = rsp.relationship;
					if( rsp.relationship.related_id == p.product_id ) {
						p.sections.relationship.fields.product_id.active = 'yes';
						p.sections.relationship.fields.related_id.active = 'no';
					} else {
						p.sections.relationship.fields.product_id.active = 'no';
						p.sections.relationship.fields.related_id.active = 'yes';
					}
					p.refresh();
					p.show(cb);
				});
		} else {
			this.edit.reset();
			this.edit.data = {};
			this.edit.sections.relationship.fields.product_id.active = 'no';
			this.edit.sections.relationship.fields.related_id.active = 'yes';
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.saveRelationship = function() {
		if( this.edit.relationship_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.products.relationshipUpdate', 
					{'business_id':M.curBusinessID, 
					'relationship_id':this.edit.relationship_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_products_relationships.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.products.relationshipAdd', 
					{'business_id':M.curBusinessID, 'product_id':this.edit.product_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_products_relationships.edit.close();
					});
			} else {
				this.edit.close();
			}
		}
	};

	this.deleteRelationship = function() {
		if( confirm("Are you sure you want to remove this similar product?") ) {
			var rsp = M.api.getJSONCb('ciniki.products.relationshipDelete', 
				{'business_id':M.curBusinessID, 'relationship_id':this.edit.relationship_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_products_relationships.edit.close();
				});
		}	
	};
}
