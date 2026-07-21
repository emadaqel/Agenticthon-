<?php

namespace App\Foundation\Bootstrap;

use Illuminate\Foundation\Bootstrap\HandleExceptions as BaseHandleExceptions;

class HandleExceptions extends BaseHandleExceptions
{
    /**
     * Laravel 8 predates PHP 8.5's implicit-nullable deprecations. Suppress
     * framework-owned deprecations while continuing to report application
     * and third-party deprecations through Laravel's configured channel.
     */
    public function handleDeprecation($message, $file, $line)
    {
        $frameworkPath = DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'laravel'
            .DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR;

        if (str_contains($file, $frameworkPath)) {
            return;
        }

        parent::handleDeprecation($message, $file, $line);
    }
}
