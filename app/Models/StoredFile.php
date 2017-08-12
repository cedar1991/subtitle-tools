<?php

namespace App;

use App\Facades\FileHash;
use App\Subtitles\TextFile;
use App\Utils\TempFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * App\StoredFile
 *
 * @property int $id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property string $hash
 * @property string $storage_file_path
 * @method static \Illuminate\Database\Eloquent\Builder|\App\StoredFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\StoredFile whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\StoredFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\StoredFile whereStorageFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\StoredFile whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StoredFile extends Model
{
    protected $fillable = ['hash', 'storage_file_path'];

    protected function getFilePathAttribute()
    {
        return storage_disk_file_path($this->storage_file_path);
    }

    public static function getOrCreate($file)
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        $hash = FileHash::make($filePath);

        $fromCache = StoredFile::where('hash', $hash);

        if($fromCache->count() > 0) {
            return $fromCache->first();
        }

        $storagePath = "stored-files/" . date('Y-m');

        if(!File::isDirectory($storagePath)) {
            Storage::makeDirectory($storagePath);
        }

        $storageFilePath = "{$storagePath}/" . time() . "-" . substr($hash, 0, 16);

        // copy instead of moving to prevent from moving test files
        copy($filePath, storage_disk_file_path($storageFilePath));

        return StoredFile::create([
            'storage_file_path' => $storageFilePath,
            'hash' => $hash,
        ]);
    }

    public static function createFromTextFile(TextFile $textFile)
    {
        $filePath = (new TempFile())->make($textFile->getContent());

        return self::getOrCreate($filePath);
    }
}
