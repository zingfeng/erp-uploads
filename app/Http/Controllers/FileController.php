<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage as Storage;
use Illuminate\Support\Str;


class FileController extends Controller
{
    /**
     * Upload file qua Web và App
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function process(Request $request){
        if ($request->isMethod('OPTIONS')) return response('', 200);

        $max_size_allow = 10; // MB
        $ext_allow = [
            'jpg','jpeg','png','svg','bmp',
            'mp3','mp4',
            'doc','docx','xls','xlsx','csv',
            'pdf',
        ];
        $params = $request->all();

        foreach ($params as $key => $value) {
            if ($request->hasFile($key)) {
                $file = $request->{$key};
                $info = [
                    'name' => $file->getClientOriginalName(),
                    'ext' => trim(strtolower($file->getClientOriginalExtension())),
                    'tmp' => $file->getRealPath(),
                    'size' => $file->getSize(), // byte
                    'type' => $file->getMimeType(),
                ];
                if (! in_array($info['ext'],$ext_allow)){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Không thể upload file '.$info['ext'],
                    ], 422);
                }

                if ($info['size'] > ($max_size_allow*1024*1024)){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Dung lượng vượt quá '.$max_size_allow.' MB',
                    ], 201);
                }
                try{
                    $new_name = (Str::uuid()->toString()).'.'.$info['ext'];

                    //// ================  TMP OR NOT
                    if ( isset($params['tmp']) && ($params['tmp'] == 'false') ) {
                        // Put vào folder Upload luôn
                        $content = file_get_contents($file->getRealPath());
                        $url = 'uploads'.('/' . date('Y') . '/' . date('m') . '/' . date('d')).'/'.$new_name;
                        Storage::disk(config('filesystems.cloud'))->put($url, $content,'public');
                        return response()->json([
                            'status' => 'success',
                            'url' => config('filesystems.disks.s3.url'). '/'.$url,
                        ], 201);

                    }else{
                        // Put vào folder tmp, cần gọi hàm formalize để chuyển từ folder tmp sang upload
                        $content = file_get_contents($file->getRealPath());
                        Storage::disk(config('filesystems.cloud'))->put('tmp/'.$new_name, $content,'public');

                        // Dạng return này liên quan đến thư viện upload phía Client - filepond
                        echo 'uploads'.('/' . date('Y') . '/' . date('m') . '/' . date('d')).'/'.$new_name;
                        exit;
                    }


                }catch (\Exception $e){
                    return response()->json([
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ], 201);
                }
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'invalid param',
        ], 201);

    }

    /**
     * Chuyển file từ folder tmp sang upload trên S3
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function formalization(Request $request){
        $params = $request->all();
        $files = $params['files'];
        if (! is_array($files)) $files = [$files];
        $res = [];
        foreach ($files as $file_path){
            $file_path_now = 'tmp'.substr($file_path,strrpos($file_path,'/')); // tmp/a.pdf
            $exists = Storage::disk(config('filesystems.cloud'))->exists($file_path_now);
            if ($exists){
                Storage::disk(Config::get('cloud'))->move($file_path_now, $file_path);
                $res[] = true;
            }else{
                $res[] = false;
            }
        }
        return response()->json([
            'status' => 'success',
            'domain' => config('filesystems.disks.s3.url'),
            'message' => $res,
        ], 201);
    }

    /**
     * Xóa file trong folder TMP
     */
    public function revert(){
        Storage::disk(config('filesystems.cloud'))->delete(str_replace("uploads/","tmp/",file_get_contents('php://input')));
    }
}
