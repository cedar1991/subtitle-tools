<?php

namespace App\Http\Controllers\Admin;

use SjorsO\TextFile\Facades\TextFileReader;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $supervisorWorkers = [
        'st-worker-default',
        'st-worker-broadcast',
        'st-worker-subidx',
    ];

    public function index()
    {
        $logsWithErrors = collect(scandir(storage_path('logs')))->filter(function($name) {
            return !starts_with($name, '.') && filesize(storage_path("logs/{$name}")) > 0;
        })->values()->all();

        $supervisorInfo = $this->getSupervisorInfo();

        $diskUsageFilePath = storage_disk_file_path('diagnostic/disk-usage.txt');
        $diskUsage = file_exists($diskUsageFilePath) ? file_get_contents($diskUsageFilePath) : 'NONE (100%)';
        $diskUsageWarning = str_before(str_after($diskUsage, '('), ')') > 60;

        $notFoundRequests = $this->get404Info();

        return view('admin.dashboard', [
            'logs'       => $logsWithErrors,
            'supervisor' => $supervisorInfo,
            'goodSupervisor' => count($this->supervisorWorkers) === count($supervisorInfo),
            'diskUsage' => strtolower($diskUsage),
            'diskUsageWarning' => $diskUsageWarning,
            'notFoundRequests' => $notFoundRequests,
        ]);
    }

    public function getLog($name)
    {
        $filePath = storage_path("logs/{$name}");

        if(!file_exists($filePath)) {
            return back();
        }

        return implode('<br />', TextFileReader::getLines($filePath));
    }

    public function deleteLog($name)
    {
        $filePath = storage_path("logs/{$name}");

        if(file_exists($filePath)) {
            unlink($filePath);
        }

        return back();
    }

    private function getSupervisorInfo()
    {
        $lines = app()->environment('local') ? [
            "st-worker-broadcast:st-worker-broadcast_00   RUNNING   pid 27243, uptime 0:04:36",
            "st-worker-default:st-worker-default_00       RUNNING   pid 27245, uptime 0:13:51",
            "st-worker-subidx:st-worker-subidx_00         RUNNING   pid 27244, uptime 2:23:40",
        ] : explode("\n", shell_exec('supervisorctl status'));

        return collect($lines)->filter(function($line) {
            return !empty($line);
        })->map(function($line) {
           return preg_split('/ {3,}|, /', $line);
        })->map(function($parts) {
            return (object)[
                'worker'    => str_before($parts[0], ':'),
                'name'      => str_after($parts[0], ':'),
                'status'    => strtolower($parts[1] ?? 'UNKNOWN'),
                'isRunning' => $parts[1] ?? 'UNKNOWN' === 'RUNNING',
                'pid'       => str_after($parts[2] ?? '?', 'pid '),
                'uptime'    => str_after($parts[3] ?? '?:??:??', 'uptime '),
            ];
        })->all();
    }

    private function get404Info()
    {
        $logFilePath = storage_disk_file_path('diagnostic/404.txt');

        if(!file_exists($logFilePath)) {
            return [];
        }

        $blacklistFilePath = storage_disk_file_path('diagnostic/404-blacklist.txt');

        if(!file_exists($blacklistFilePath)) {
            touch($blacklistFilePath);
        }

        $blackList = TextFileReader::getLines($blacklistFilePath);


        $entries = TextFileReader::getLines($logFilePath);

        $paths = collect($entries)->filter()->map(function($line) {
            return explode('|', $line)[2];
        })->all();

        $uniquePaths = [];

        foreach($paths as $path) {
            if(in_array($path, $blackList)) {
                continue;
            }

            if(!isset($uniquePaths[$path])) {
                $uniquePaths[$path] = ['path' => $path, 'count' => 0];
            }

            $uniquePaths[$path]['count']++;
        }

        return array_values($uniquePaths);
    }

    public function open404Log()
    {
        $filePath = storage_disk_file_path('diagnostic/404.txt');

        if(!file_exists($filePath)) {
            return back();
        }

        return implode('<br />', TextFileReader::getLines($filePath));
    }

    public function delete404Log()
    {
        $filePath = storage_disk_file_path('diagnostic/404.txt');

        if(file_exists($filePath)) {
            unlink($filePath);
        }

        return back();
    }

    public function append404Blacklist(Request $request)
    {
        $values = $request->validate(['path' => 'required']);

        file_put_contents(
            storage_disk_file_path('diagnostic/404-blacklist.txt'),
            $values['path'] . "\r\n",
            FILE_APPEND
        );

        return back();
    }
}