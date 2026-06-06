<?php

use Illuminate\Support\Str;

if (!function_exists('pm_resolveTrans')) {
    /**
     * Resolve translation strictly from the application's lang files.
     * Example: resources/lang/{locale}/{page}.php
     * Falls back to the original string if the key is missing.
     */
    function pm_resolveTrans($trans = '', $page = null, $lang = null, $snaked = true): ?string
    {
        if ($page === null && function_exists('config')) {
            try {
                $page = config('roles.translate.file', 'roles');
            } catch (\Throwable $exception) {
                $page = 'roles';
            }
        }

        $page = $page ?? 'roles';

        if (empty($trans)) {
            return '---';
        }

        if (!function_exists('app') || !function_exists('__')) {
            return $trans;
        }

        try {
            $application = app();
        } catch (\Throwable $exception) {
            return $trans;
        }

        if (!method_exists($application, 'getLocale') || !method_exists($application, 'setLocale')) {
            return $trans;
        }

        $originalLocale = $application->getLocale();
        $application->setLocale($lang ?? $originalLocale);

        try {
            $key = $snaked ? Str::snake($trans) : $trans;
            $line = __("$page.$key");

            return Str::startsWith($line, "$page.") ? $trans : $line;
        } finally {
            $application->setLocale($originalLocale);
        }
    }
}
