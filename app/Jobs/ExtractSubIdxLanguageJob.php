<?php

namespace App\Jobs;

use App\Events\ExtractingSubIdxLanguageChanged;
use App\Models\SubIdxLanguage;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ExtractSubIdxLanguageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    // the shell_exec in VobSub2Srt times out after 300 seconds
    public $timeout = 330;

    protected $subIdxLanguage;

    public function __construct(SubIdxLanguage $subIdxLanguage)
    {
        $this->subIdxLanguage = $subIdxLanguage;
    }

    public function handle()
    {
        $this->subIdxLanguage->update(['started_at' => Carbon::now()]);

        $VobSub2Srt = $this->subIdxLanguage->subIdx->getVobSub2Srt();

        // See the readme for more information about vobsub2srt behavior
        $outputFilePath = $VobSub2Srt->extractLanguage($this->subIdxLanguage->index);

        $newName = null;

        if(file_exists($outputFilePath)) {
            if(filesize($outputFilePath) === 0) {
                unlink($outputFilePath);
            }
            else {
                // todo: parse it as srt and save it again

                $newName = substr($outputFilePath, 0, strlen($outputFilePath) - 4) . '-' . $this->subIdxLanguage->index . '-' . $this->subIdxLanguage->language . '.srt';

                rename($outputFilePath, $newName);
            }

        }

        if($newName !== null) {
            $this->subIdxLanguage->update([
                'filename' => $this->subIdxLanguage->subIdx->filename . '-' . $this->subIdxLanguage->index . '-' . $this->subIdxLanguage->language . '.srt',
                'has_error' => false,
                'finished_at' => Carbon::now(),
            ]);
        }
        else {
            $this->subIdxLanguage->update([
                'has_error' => true,
                'finished_at' => Carbon::now(),
            ]);
        }


    }

    public function failed()
    {
        $this->subIdxLanguage->update([
            'has_error' => true,
            'finished_at' => Carbon::now(),
        ]);
    }

}