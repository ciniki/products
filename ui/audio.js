//
// The app to add/edit products product audio
//
function ciniki_products_audio() {
    this.webFlags = {
        '1':{'name':'Visible'},
        };
    this.init = function() {
        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('Edit Audio',
            'ciniki_products_audio', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.products.audio.edit');
        this.edit.default_data = {};
        this.edit.data = {};
        this.edit.product_id = 0;
        this.edit.product_audio_id = 0;
        this.edit.sections = {
            'info':{'label':'Information', 'type':'simpleform', 'fields':{
                'name':{'label':'Title', 'type':'text'},
                'sequence':{'label':'Order', 'type':'text', 'size':'small'},
                'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.webFlags},
            }},
            '_audio_mp3':{'label':'MP3 File', 'fields':{
                'mp3_audio_id':{'label':'MP3', 'hidelabel':'yes', 'type':'audio_id', 'controls':'all', 'history':'no'},
            }},
            '_audio_wav':{'label':'WAV File', 'fields':{
                'wav_audio_id':{'label':'WAV', 'hidelabel':'yes', 'type':'audio_id', 'controls':'all', 'history':'no'},
            }},
            '_audio_ogg':{'label':'OGG File', 'fields':{
                'ogg_audio_id':{'label':'OGG', 'hidelabel':'yes', 'type':'audio_id', 'controls':'all', 'history':'no'},
            }},
            '_description':{'label':'Description', 'type':'simpleform', 'fields':{
                'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
            }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_products_audio.saveAudio();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_products_audio.deleteAudio();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.products.audioHistory', 'args':{'business_id':M.curBusinessID, 
                'product_audio_id':this.product_audio_id, 'field':i}};
        };
        this.edit.addDropFile = function(s, i, iid, file) {
            if( file.type == 'audio/mpeg' || file.type == 'audio/mp3' ) {
                M.ciniki_products_audio.edit.setFieldValue('mp3_audio_id', iid);
                M.gE(this.panelUID + '_mp3_audio_id_audio_filename').innerHTML = file.name;
            }
            else if( file.type == 'audio/vnd.wave' || file.type == 'audio/wav' ) {
                M.ciniki_products_audio.edit.setFieldValue('wav_audio_id', iid);
                M.gE(this.panelUID + '_wav_audio_id_audio_filename').innerHTML = file.name;
            }
            else if( file.type == 'audio/ogg' ) {
                M.ciniki_products_audio.edit.setFieldValue('ogg_audio_id', iid);
                M.gE(this.panelUID + '_ogg_audio_id_audio_filename').innerHTML = file.name;
            }
            return true;
        };
        this.edit.deleteFile = function(i) {
            M.ciniki_products_audio.edit.setFieldValue(i, 0);
        };
        this.edit.addDropFileRefresh = function() {
//          if( M.ciniki_products_audio.edit.product_audio_id > 0 ) {
//              M.api.getJSONCb('ciniki.products.audioGet', {'business_id':M.curBusinessID, 
//                  'product_audio_id':M.ciniki_products_audio.edit.product_audio_id, 'audio':'yes'}, function(rsp) {
////                        if( rsp.stat != 'ok' ) {
//                          M.api.err(rsp);
//                          return false;
//                      }
//                      var p = M.ciniki_products_audio.edit;
//                      p.data = rsp.audio;
//                      p.refreshSection('_audio');
//                  });
//          } else {
// FIXME: Add code to update audio section
//              M.api.getJSONCb('ciniki.audio.get', {'business_id':M.curBusinessID, 
//                  'audio_id':M.ciniki_products_audio.edit.product_id, 'audio':'yes'}, function(rsp) {
//                      if( rsp.stat != 'ok' ) {
//                          M.api.err(rsp);
//                          return false;
//                      }
//                      var p = M.ciniki_products_product.edit;
//                      p.data.images = rsp.product.images;
//                      p.refreshSection('_audio');
//                  });
//          }
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_products_audio.saveAudio();');
        this.edit.addClose('Cancel');
    };

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_products_audio', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'yes' ) {
            this.showEdit(cb, 0, args.product_id);
        } else if( args.product_audio_id != null && args.product_audio_id > 0 ) {
            this.showEdit(cb, args.product_audio_id, 0);
        }
        return false;
    }

    this.showEdit = function(cb, iid, eid) {
        if( iid != null ) { this.edit.product_audio_id = iid; }
        if( eid != null ) { this.edit.product_id = eid; }
        if( this.edit.product_audio_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.products.audioGet', 
                {'business_id':M.curBusinessID, 'product_audio_id':this.edit.product_audio_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_products_audio.edit;
                    p.data = rsp.audio;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.data = {'webflags':1};
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveAudio = function() {
        if( this.edit.product_audio_id > 0 ) {
            var c = this.edit.serializeFormData('no');
            if( c != '' ) {
                var rsp = M.api.postJSONFormData('ciniki.products.audioUpdate', 
                    {'business_id':M.curBusinessID, 
                    'product_audio_id':this.edit.product_audio_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } else {
                                M.ciniki_products_audio.edit.close();
                            }
                        });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeFormData('yes');
            var rsp = M.api.postJSONFormData('ciniki.products.audioAdd', 
                {'business_id':M.curBusinessID, 'product_id':this.edit.product_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_products_audio.edit.close();
                        }
                    });
        }
    };

    this.deleteAudio = function() {
        if( confirm('Are you sure you want to delete this audio?') ) {
            var rsp = M.api.getJSONCb('ciniki.products.audioDelete', {'business_id':M.curBusinessID, 
                'product_audio_id':this.edit.product_audio_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_products_audio.edit.close();
                });
        }
    };
}
