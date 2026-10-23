<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Telegram_notifier
{
    public function cronSummary(array $summary)
    {
        if ((string) app_setting('telegram_enabled', '0') !== '1') {
            return ['success' => true, 'skipped' => true, 'message' => 'Notifikasi Telegram nonaktif.'];
        }
        $token = trim((string) app_setting('telegram_bot_token', ''));
        $chat = trim((string) app_setting('telegram_chat_id', ''));
        if ($token === '' || $chat === '') return ['success' => false, 'message' => 'Bot Token atau Chat ID Telegram belum diisi.'];
        if (!function_exists('curl_init')) return ['success' => false, 'message' => 'Ekstensi PHP cURL belum tersedia.'];

        $labels = ['daily' => 'Harian', 'hourly' => 'Setiap Jam', 'five_minutes' => 'Setiap 5 Menit'];
        $lines = [app_setting('isp_name', 'Billing Internet') . ' — Hasil Cron',
            'Jadwal: ' . ($labels[$summary['schedule']] ?? $summary['schedule']),
            'Waktu: ' . $summary['finished_at'] . ' (' . date_default_timezone_get() . ')',
            'Status: ' . (!empty($summary['success']) ? 'BERHASIL' : 'ADA KEGAGALAN')];
        foreach ($summary['tasks'] as $task) {
            $result = $task['result'] ?? [];
            $lines[] = '';
            $lines[] = $task['key'] === 'customer_isolation' ? 'Isolir Pelanggan' : $task['key'];
            $lines[] = $task['message'];
            if (!empty($result['messages']) && is_array($result['messages'])) {
                foreach (array_slice($result['messages'], 0, 12) as $message) $lines[] = '- ' . $message;
                if (count($result['messages']) > 12) $lines[] = 'Detail lainnya tersedia di menu Log Cron.';
            }
        }
        $lines[] = '';
        $lines[] = 'Detail: ' . site_url('cron-logs');
        $text = implode("\n", $lines);
        // Batasi satu pesan agar tetap di bawah batas Telegram, termasuk Unicode.
        $text = function_exists('mb_strcut') ? mb_strcut($text, 0, 3800, 'UTF-8') : substr($text, 0, 3800);
        $curl = curl_init('https://api.telegram.org/bot' . $token . '/sendMessage');
        curl_setopt_array($curl, [CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['chat_id' => $chat, 'text' => $text]),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15]);
        $response = curl_exec($curl);
        $http = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_errno($curl);
        curl_close($curl);
        $data = is_string($response) ? json_decode($response, true) : null;
        if ($error || $http !== 200 || empty($data['ok'])) {
            // Jangan merekam URL API karena mengandung token bot.
            return ['success' => false, 'message' => 'Pengiriman Telegram gagal (HTTP ' . $http . ', cURL ' . $error . '). Periksa token, Chat ID, izin bot, dan koneksi hosting.'];
        }
        return ['success' => true, 'message' => 'Ringkasan cron berhasil dikirim ke Telegram.'];
    }
}
