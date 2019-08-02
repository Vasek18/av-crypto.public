<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class LogsController extends Controller
{
    public function index()
    {
        $logFileContent = file_get_contents(storage_path().'/logs/laravel.log');
        $logMessages = [];

        if (strlen($logFileContent)) {
            $logMessages = preg_split('/\}\s*\[/', $logFileContent);

            foreach ($logMessages as &$logMessage) {
                $logMessage = trim($logMessage);

                // возвращаем левую скобочку
                $firstCharacter = substr($logMessage, 0, 1);
                if ($firstCharacter != '[') {
                    $logMessage = '['.$logMessage;
                }

                // возвращаем скобочку скобочку
                $firstCharacter = substr($logMessage, -1, 1);
                if ($firstCharacter != '}') {
                    $logMessage = $logMessage.'}';
                }
            }
        }

        return view(
            'admin.logs.index',
            [
                'logs' => $logMessages,
            ]
        );
    }
}