<?php

use Illuminate\Support\Str;

if (!function_exists('pm_resolveTrans')) {
    /**
     * Resolve translation strictly from the application's lang files.
     * Example: resources/lang/{locale}/{page}.php
     * Falls back to original string if key is missing.
     */
    function pm_resolveTrans($trans = '', $page = 'roles', $lang = null, $snaked = true): ?string
    {
        if (empty($trans)) {
            return '---';
        }

        app()->setLocale($lang ?? app()->getLocale());

        $key = $snaked ? Str::snake($trans) : $trans;

        $line = __("$page.$key");
        return Str::startsWith($line, "$page.") ? $trans : $line;
    }
}
