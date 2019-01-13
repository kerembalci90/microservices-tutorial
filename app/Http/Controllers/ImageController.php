<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Redis;
use Aws\S3\S3Client;

class ImageController extends Controller
{
    private $storage;
    
    public function __construct()
    {
        $this->storage = new S3Client([
            'region' => 'ca-central-1',
            'version' => 'latest',
            'credentials' => ['key' => env('S3ACCESSID'), 'secret' => env('S3SECRET')]
        ]);
    }

    /**
    * @param Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function upload(Request $request)
    {
        if (!$request->hasFile('photo')) {
            return response()->json(['error' => 'Photo is not attached.'], 200);
        }

        $file = $request->file('photo');
        $fileName = "imgManageUpload".time().".".$file->getClientOriginalExtension();
        $mime = $file->getMimeType();

        $file->move(env('TMP_DIR'), $fileName);
        $path = env('TMP_DIR')."/".$fileName;

        $hash = md5(file_get_contents($path));
        $saveFileName = "$hash.".$file->getClientOriginalExtension();

        // $cacheRet = Redis::get($hash);
        // if ($cacheRet != null) {
        //     unlink($path);
        //     return response()->json(['set' => $cacheRet, 'file' => $saveFileName], 200);
        // }

        try {
            $result = $this->storage->putObject([
                    'ACL' => 'public-read',
                    'Bucket' => env('BUCKET'),
                    'ContentType' => $mime,
                    'Key' => $saveFileName,
                    'ServerSideEncryption' => 'AES256',
                    'SourceFile' => $path,
                    'StorageClass' => 'REDUCED_REDUNDANCY'
                ]);
            echo $result;
        } catch (\Exception $e) {
            //dd($e->getAwsErrorMessage());
            return response()->json(['error' => $e->getMessage()], 401);
        }

        $setDate = date('c');
        return response()->json(['set' => $setDate], 200);
    }

    public function getImageUrl(Request $request)
    {
        $fileName = $request->input('imageName');
        $imgUrl = $this->storage->getObjectUrl(env('BUCKET'), $fileName);
        
        $h = get_headers($imgUrl);
        if ($h[0] == 'HTTP/1.1 200 OK') {
            return response()->json(['url' => $imgUrl], 200);
        }

        return response()->json(['error' => 'No Such File!'], 404);
    }

    public function getImage(Request $request)
    {
        $fileName = $request->input('imageName');
        $imgUrl = $this->storage->getObjectUrl(env('BUCKET'), $fileName);

        $h = get_headers($imgUrl);
        if ($h[0] == 'HTTP/1.1 200 OK') {
            return $this->display($fileName, $imgUrl);
        }

        //Display default image if none found.
        return $this->display($fileName);
    }

    private function display($id, $img)
    {
        header('Content-type: image/jpeg');
        readfile($img);
        // echo '<img src="'.$img.'"/>';
        // $image = new \Imagick();
        // $image->readImage($img);
        // $length = $image->getImageLength();
        // $mime = $image->getImageMimeType();
        // header('Content-type: '.$mime);
        // header('Pragma: public');
        // header('Content-Length: '.$length);
        // echo $image;
        // $image->destroy();
    }
}
