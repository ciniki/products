<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
//require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/dropbox/lib/Dropbox/autoload.php');
//use \Dropbox as dbx;

function ciniki_products_dropboxDownloadAudio(&$ciniki, $business_id, $client, $product, $details) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'audio', 'hooks', 'insertFromFile');

    if( !isset($ciniki['config']['ciniki.core']['sox']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2923', 'msg'=>'Missing audio converter'));
    }
    $sox = $ciniki['config']['ciniki.core']['sox'];

    foreach($details as $file) {
        $filename = preg_replace("/^.*\/([^\/]+)$/", "$1", $file['path']);
        $name = preg_replace("/^([^\/]+)\.([^\/\.]+)$/", "$1", $filename);
        $extension = preg_replace("/^.*\.([^\.]+)$/", "$1", $filename);

        print "filename: $filename\n";
        print "name: $name\n";
        print "extension: $extension\n";

        if( $extension != 'wav' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2924', 'msg'=>'Incorrect file format'));
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
        curl_setopt($ch, CURLOPT_URL, "https://api-content.dropbox.com/1/files/auto" . $file['path']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        //
        // Convert
        //
        $output = exec("$sox $tmp_filename -r 44.1k -b 16 $wav_filename gain -n -1");
        $output = exec("$sox $tmp_filename -r 44.1k -C 0.2 $mp3_filename gain -n -1");
        $output = exec("$sox $tmp_filename -r 44.1k -C 10 $ogg_filename gain -n -1");

        //
        // Insert the files
        //
        $rc = ciniki_audio_hooks_insertFromFile($ciniki, $business_id, array(
            'filename'=>$wav_filename,
            'name'=>$name,
            'original_filename'=>$name . '.wav',
            ));
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2925', 'msg'=>'Unable to add file', 'err'=>$rc['err']));
        }
        $wav_audio_id = $rc['id'];

        $rc = ciniki_audio_hooks_insertFromFile($ciniki, $business_id, array(
            'filename'=>$mp3_filename,
            'name'=>$name,
            'original_filename'=>$name . '.mp3',
            ));
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2926', 'msg'=>'Unable to add file', 'err'=>$rc['err']));
        }
        $mp3_audio_id = $rc['id'];

        $rc = ciniki_audio_hooks_insertFromFile($ciniki, $business_id, array(
            'filename'=>$ogg_filename,
            'name'=>$name,
            'original_filename'=>$name . '.ogg',
            ));
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2927', 'msg'=>'Unable to add file', 'err'=>$rc['err']));
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
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.products.audio', array(
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
            $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.products.audio', $update_args, $audio_id, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
