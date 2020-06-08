<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Intervention\Image\ImageManagerStatic as Image;

class ImageController extends Controller
{
    // =========== RESIZE
    private $size_allow_resize = [
        '50x50' => ['50', '50'],
        '0x100' => [null,100],
        '100x0' => [200,null],

        '0x200' => [null,200],
        '200x0' => [200,null],

        '100x100' => ['100', '100'],
        '200x200' => ['200', '200'],
        '320x240' => ['320', '240'],
        '350x350' => ['350', '350'],

    ];

    public function resize(Request $request)
    {
        $uri = $request->get('uri');
        $start_uri = '/img/public/';
        $real_folder = 'src';

        if (substr($uri, 0, strlen($start_uri)) !== $start_uri) {
            return response()->json([
                'error' => 'file not found',
            ], 404);
        }

        $s_link = substr($uri, strlen($start_uri));
        $s_link = substr($s_link,strlen("resize/"));

        $first_slash = strpos($s_link, '/');
        $size = substr($s_link, 0, $first_slash);
        $s_link = $real_folder . substr($s_link, strlen($size));
        $full_uri = $start_uri . $s_link;
        $r = $this->resize_image($full_uri,$size);
        if ($r){
            $type = 'image/png';
            $headers = ['Content-Type' => $type];
            $response = new BinaryFileResponse($r, 200, $headers);
            return $response;
        }
        return response()->json([
            'error' => 'file not found',
        ], 404);
    }

    private function resize_image($full_uri, $size)
    {
        $full_link_disk = $_SERVER['DOCUMENT_ROOT'] . $full_uri;

        if (file_exists($full_link_disk)) {
            $img = Image::make($full_link_disk);
            if (!isset($this->size_allow_resize[$size])) {
                return false;
            }
//            var_dump($full_uri);
            $new_uri = preg_replace('/src/',  'resize/'.$size , $full_uri, 1);
            $size_arr = $this->size_allow_resize[$size];

            if (in_array(null,$size_arr)){
                $img->resize($size_arr[0], $size_arr[1], function ($constraint) {
                    $constraint->aspectRatio();
                });
            } else {

                $size_arr = $this->size_allow_resize[$size];
                $target_w =  $size_arr[0];
                $target_h =  $size_arr[1];
                $target_ratio = $target_w/$target_h;
                // ==========
                $org_w = $img->width();
                $org_h = $img->height();
                $org_ratio = $org_w/$org_h;

                if ($org_ratio >= $target_ratio) {
                    // Anh goc dai hon - resize theo height
                    $img->resize($target_w,null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                }else{
                    $img->resize(null, $target_h, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                }
            }

            // make data directory
            $folder_link = substr($new_uri,0,strripos($new_uri,'/'));
//            dd($folder_link);

            if (!file_exists($_SERVER['DOCUMENT_ROOT'].$folder_link)) {
                mkdir($_SERVER['DOCUMENT_ROOT'].$folder_link, 0777, true);
            }
            $img->save($_SERVER['DOCUMENT_ROOT'].$new_uri);
            return $_SERVER['DOCUMENT_ROOT'].$new_uri;
        }
        return false;
    }

    // =========== CROP
    private $size_allow_crop = [
        '50x50' => ['50', '50'],
        '100x100' => ['100', '100'], // $width --  $height
        '200x200' => ['200', '200'],
        '320x240' => ['320', '240'],
        '350x350' => ['350', '350'],
    ];

    public function crop(Request $request)
    {
        $uri = $request->get('uri');
        $start_uri = '/img/public/';
        $real_folder = 'src';

        if (substr($uri, 0, strlen($start_uri)) !== $start_uri) {
            return response()->json([
                'error' => 'file not found',
            ], 404);
        }

        $s_link = substr($uri, strlen($start_uri));

        // remove "crop/"
        $s_link = substr($s_link,strlen("crop/"));


        $first_slash = strpos($s_link, '/');
        $size = substr($s_link, 0, $first_slash);
        $s_link = $real_folder . substr($s_link, strlen($size));
        $full_uri = $start_uri . $s_link;
        $params = array(
            'input' => $full_uri,
            'size' => $size
        );
        $r = $this->crop_image($params);
        if ($r){
            $type = 'image/png';
            $headers = ['Content-Type' => $type];
            $response = new BinaryFileResponse($r, 200, $headers);
            return $response;
        }
        return response()->json([
            'error' => 'file not found',
        ], 404);

    }

    private function crop_image($params){
        $full_uri = $params['input'];
        $size = $params['size'];
        $full_link_disk = $_SERVER['DOCUMENT_ROOT'] . $full_uri;

        if (file_exists($full_link_disk)) {
            $img = Image::make($full_link_disk);
            if (!isset($this->size_allow_crop[$size])) {
                return false;
            }
//            var_dump($full_uri);
            $new_uri = preg_replace('/src/',  'crop/'.$size , $full_uri, 1);
            // make data directory
            $folder_link = substr($new_uri,0,strripos($new_uri,'/'));
//            dd($folder_link);

            if (!file_exists($_SERVER['DOCUMENT_ROOT'].$folder_link)) {
                mkdir($_SERVER['DOCUMENT_ROOT'].$folder_link, 0777, true);
            }

            $size_arr = $this->size_allow_crop[$size];
            $target_w =  $size_arr[0];
            $target_h =  $size_arr[1];
            $target_ratio = $target_w/$target_h;
            // ==========
            $org_w = $img->width();
            $org_h = $img->height();
            $org_ratio = $org_w/$org_h;

            if ($org_ratio >= $target_ratio) {
                // Anh goc dai hon - resize theo height
                $img->resize(null, $target_h, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }else{
                $img->resize($target_w,null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
            $img->crop($target_w,$target_h);
            $img->save($_SERVER['DOCUMENT_ROOT'].$new_uri);
            return $_SERVER['DOCUMENT_ROOT'].$new_uri;
        }

        return false;
    }

}
