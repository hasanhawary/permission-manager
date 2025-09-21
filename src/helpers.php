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
        $page = $page ?? config('roles.translate.file', 'roles');

        if (empty($trans)) {
            return '---';
        }

        app()->setLocale($lang ?? app()->getLocale());

        $key = $snaked ? Str::snake($trans) : $trans;

        $line = __("$page.$key");
        return Str::startsWith($line, "$page.") ? $trans : $line;
    }
}
