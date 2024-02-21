<?php

namespace App\Http\Controllers;

class FileController extends Controller
{

    public function getFile($type, $path)
    {
        abort_if(!in_array($type, ['file', 'image']), 404);

        try {
            $path = str($path)->replace('_masked.png', '')->__toString();
            $decrypted = self::encryptDecrypt($path, 'decrypt');

            return response()->redirectTo(asset_url_local_s3($decrypted));
        } catch (\Exception $e) {
            abort(404);
        }

    }

    public static function encryptDecrypt($string, $action = 'encrypt')
    {

        $encryptMethod = 'AES-256-CBC';
        $secret_key = 'worksuite'; // User define private key
        $secret_iv = 'froiden'; // User define secret key
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16); // sha256 is hash_hmac_algo

        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encryptMethod, $key, 0, $iv);

            return base64_encode($output);
        }

        if ($action == 'decrypt') {
            return openssl_decrypt(base64_decode($string), $encryptMethod, $key, 0, $iv);
        }

    }

}
