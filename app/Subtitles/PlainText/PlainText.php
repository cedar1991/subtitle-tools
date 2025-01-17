<?php

namespace App\Subtitles\PlainText;

use App\Subtitles\TextFile;
use App\Subtitles\WithFileContent;
use App\Support\TextFile\Facades\TextFileIdentifier;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PlainText extends TextFile
{
    use WithFileContent;

    protected $extension = "txt";

    public function __construct()
    {

    }

    public function setContent($string)
    {
        $this->content = $string;

        return $this;
    }

    public static function isThisFormat($file)
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        return TextFileIdentifier::isTextFile($filePath);
    }
}
