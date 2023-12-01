<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Exception;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{

    public function downloadFile($id ) {
        try {
            $file = Document::find($id);
            if ( !$file ) {
                return response()->json([
                    'message' => 'File not found.'
                ], 404);
            }
            $config  = json_decode(file_get_contents(base_path('public/'.config('filesystems.disks.gcs.key_file_path'))), true);
            $storage = new StorageClient($config);
            $bucket  = $storage->bucket(config('filesystems.disks.gcs.bucket'));
            $object  = $bucket->object($file->url);
$tempFile = $file->name;
            $result = Storage::disk('public')->put($tempFile, $object->downloadAsStream());
            if ( $result ) {
                return response()->download(storage_path('app/public/'.$tempFile));
            }
        }catch(Exception $e){
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }
    public function downloadFilesZip(Request $request ) {
        try {
            $ids = $request->input('ids');
            $files = Document::whereIn('id',$ids)->get();
            $zip = new \ZipArchive();
            $zip_file = storage_path('app/public/'.Auth::id().'/zips/'.count($files).'_my_doc_files.zip');
            $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $config  = json_decode(file_get_contents(base_path('public/'.config('filesystems.disks.gcs.key_file_path'))), true);
            $storage = new StorageClient($config);
            $bucket  = $storage->bucket(config('filesystems.disks.gcs.bucket'));
            //                        dd(storage_path('app/public/'.Auth::id().'/zips/hop.zip'),Storage::url(Auth::id().'/zips/hop.zip'));
            $files->each(function (Document $record) use ($zip,$bucket) {
                if (Storage::exists($record->url)) {

                    $object  = $bucket->object($record->url);
                    $tempFile = 'file-'.$record->id.'.' . $record->extension;
                    $result = Storage::disk('public')->put($tempFile, $object->downloadAsStream());
                    if($result) {
                        $zip->addFile(storage_path('app/public/'.$tempFile), $record->name);
                    }
                }
            });
            $zip->close();
            $files->each(function (Document $record){
                $tempFile = 'file-'.$record->id.'.' . $record->extension;
                Storage::disk('public')->delete($tempFile);
            });
            return response()->download($zip_file);
//            $file = Document::find($id);
//            if ( !$file ) {
//                return response()->json([
//                    'message' => 'File not found.'
//                ], 404);
//            }
//            $config  = json_decode(file_get_contents(storage_path('gcs.json')), true);
//            $storage = new StorageClient($config);
//            $bucket  = $storage->bucket(config('filesystems.disks.gcs.bucket'));
//            $object  = $bucket->object($file->url);
//$tempFile = $file->name;
//            $result = Storage::disk('public')->put($tempFile, $object->downloadAsStream());
//            if ( $result ) {
//                return response()->download(storage_path('app/public/'.$tempFile));
//            }
        }catch(Exception $e){
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }
}
