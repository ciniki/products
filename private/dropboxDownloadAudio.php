<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
//require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/dropbox/lib/Dropbox/autoload.php');
//use \Dropbox as dbx;

function ciniki_products_dropboxDownloadAudio(&$ciniki, $tnid, $client, $product, $details) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'audio', 'hooks', 'insertFromFile');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'audio', 'hooks', 'dropboxFileRevs');

    if( !isset($ciniki['config']['ciniki.core']['sox']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.28', 'msg'=>'Missing audio converter'));
    }
    $sox = $ciniki['config']['ciniki.core']['sox'];

    foreach($details as $file) {
        //
        // Load the existing audio to see if there is new versions
        //
        $rc = ciniki_audio_hooks_dropboxFileRevs($ciniki, $tnid, array('path'=>$file['path']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['files']) ) {
            $file_revs = $rc['files'];
        } else {
            $file_revs = array();
        }

        //
        // Setup the filename
        //
        $filename = preg_replace("/^.*\/([^\/]+)$/", "$1", $file['path']);
        $name = preg_replace("/^([^\/]+)\.([^\/\.]+)$/", "$1", $filename);
        $extension = preg_replace("/^.*\.([^\.]+)$/", "$1", $filename);

        if( $extension != 'wav' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.29', 'msg'=>'Incorrect file format'));
        }

        //
        // Download to tmp directory
        //
        $tmp_filename = '/tmp';
        if( isset($ciniki['config']['ciniki.core']['tmp_dir']) && $ciniki['config']['ciniki.core']['tmp_dir'] != '' ) {
            $tmp_filename = $ciniki['config']['ciniki.core']['tmp_dir'];
        }
        $wav_filename = $tmp_filename . '/' . preg_replace("/\.wav/", ".wav", $filename);
        $mp3_filename = $tmp_filename . '/' . preg_replace("/\.wav/", ".mp3", $filename);
        $ogg_filename = $tmp_filename . '/' . preg_replace("/\.wav/", ".ogg", $filename);
        $tmp_filename .= '/ORG-' . $filename;

        //
        // Check the revisions before downloading
        //
        $updates = 'no';
        if( !isset($file_revs[$name . '.wav']) || $file_revs[$name . '.wav']['dropbox_rev'] != $file['rev'] ) {
            $updates = 'yes';
        }
        if( !isset($file_revs[$name . '.mp3']) || $file_revs[$name . '.mp3']['dropbox_rev'] != $file['rev'] ) {
            $updates = 'yes';
        }
        if( !isset($file_revs[$name . '.ogg']) || $file_revs[$name . '.ogg']['dropbox_rev'] != $file['rev'] ) {
            $updates = 'yes';
        }

        if( $updates == 'no' ) {
            return array('stat'=>'ok');
        }

        //
        // Get the file from dropbox
        //
        $ch = curl_init();
        $fp = fopen($tmp_filename, 'w');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $client->getAccessToken()));
        if( $file['path'][0] != '/' ) { $file['path'] = '/' . $file['path']; }
        curl_setopt($ch, CURLOPT_URL, "https://api-content.dropbox.com/1/files/auto" . rawurlencode($file['path']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        //
        // Use the checksum from the main file as checksum will be different each time a file is transcoded to mp3/ogg/wav
        //
        $checksum = hash_file('md5', $tmp_filename);

        //
        // Convert
        //
        $output = exec("$sox '$tmp_filename' -r 44.1k -b 16 '$wav_filename' gain -n -1");
        $output = exec("$sox '$tmp_filename' -r 44.1k -C 0.2 '$mp3_filename' gain -n -1");
        $output = exec("$sox '$tmp_filename' -r 44.1k -C 10 '$ogg_filename' gain -n -1");

        //
        // Insert the files
        //
        $rc = ciniki_audio_hooks_insertFromFile($ciniki, $tnid, array(
            'filename'=>$wav_filename,
            'name'=>$name,
            'checksum'=>$checksum,
            'original_filename'=>$name . '.wav',
            'dropbox_path'=>$file['path'],
            'dropbox_rev'=>$file['rev'],
            ));
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.30', 'msg'=>'Unable to add file', 'err'=>$rc['err']));
        }
        $wav_audio_id = $rc['id'];

        $rc = ciniki_audio_hooks_insertFromFile($ciniki, $tnid, array(
            'filename'=>$mp3_filename,
            'name'=>$name,
            'checksum'=>$checksum,
            'original_filename'=>$name . '.mp3',
            'dropbox_path'=>$file['path'],
            'dropbox_rev'=>$file['rev'],
            ));
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.31', 'msg'=>'Unable to add file', 'err'=>$rc['err']));
        }
        $mp3_audio_id = $rc['id'];

        $rc = ciniki_audio_hooks_insertFromFile($ciniki, $tnid, array(
            'filename'=>$ogg_filename,
            'name'=>$name,
            'checksum'=>$checksum,
            'original_filename'=>$name . '.ogg',
            'dropbox_path'=>$file['path'],
            'dropbox_rev'=>$file['rev'],
            ));
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.products.32', 'msg'=>'Unable to add file', 'err'=>$rc['err']));
        }
        $ogg_audio_id = $rc['id'];

        //
        // Check if the audio already exists
        //
        $audio_id = 0;
        $update_args = array();
        if( isset($product['audio']) ) {
            foreach($product['audio'] as $audio) {
                if( $audio['audio']['name'] == $name ) {
                    $audio_id = $audio['audio']['id'];
                    if( $audio['audio']['mp3_audio_id'] != $mp3_audio_id ) {
                        $update_args['mp3_audio_id'] = $mp3_audio_id;
                    }
                    if( $audio['audio']['wav_audio_id'] != $wav_audio_id ) {
                        $update_args['wav_audio_id'] = $wav_audio_id;
                    }
                    if( $audio['audio']['ogg_audio_id'] != $ogg_audio_id ) {
                        $update_args['ogg_audio_id'] = $ogg_audio_id;
                    }
                    break;
                }
            }
        }

        if( $audio_id == 0 ) {
            $permalink = ciniki_core_makePermalink($ciniki, $name);
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.products.audio', array(
                'product_id'=>$product['id'],
                'name'=>$name,
                'permalink'=>$permalink,
                'sequence'=>1,
                'webflags'=>0x01,
                'mp3_audio_id'=>$mp3_audio_id,
                'wav_audio_id'=>$wav_audio_id,
                'ogg_audio_id'=>$ogg_audio_id,
                'description'=>'',
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        } elseif( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.products.audio', $audio_id, $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }

        unlink($tmp_filename);
        unlink($ogg_filename);
        unlink($mp3_filename);
        unlink($wav_filename);
    }

    return array('stat'=>'ok');
}
?>
