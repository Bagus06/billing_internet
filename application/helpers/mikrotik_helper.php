<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('mikrotik_normalize_router')) {
    function mikrotik_normalize_router(array $router)
    {
        $router['ssl'] = !empty($router['use_ssl']) || !empty($router['ssl']);
        $router['timeout'] = isset($router['timeout']) ? max(1, (int) $router['timeout']) : 5;
        return $router;
    }
}

if (!function_exists('mikrotik_find_row')) {
    function mikrotik_find_row(array $rows, $value, $field = '.id')
    {
        foreach ($rows as $row) {
            if (isset($row['!done']) || !isset($row[$field])) continue;
            if (strcasecmp((string) $row[$field], (string) $value) === 0) return $row;
        }
        return null;
    }
}

if (!function_exists('mikrotik_clean_rows')) {
    function mikrotik_clean_rows(array $rows)
    {
        return array_values(array_filter($rows, function ($row) { return is_array($row) && !isset($row['!done']); }));
    }
}
