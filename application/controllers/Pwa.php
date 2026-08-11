<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pwa extends CI_Controller
{
    public function manifest()
    {
        $name = app_setting('isp_name', 'ISP BATARA NET');
        $logo = app_setting('logo_path', 'assets/img/logo.jpeg');
        $primaryColor = $this->color(app_setting('brand_primary_color', '#1687ff'), '#1687ff');
        $backgroundColor = $this->color(app_setting('brand_background_color', '#050914'), '#050914');
        $manifest = [
            'id' => base_url(),
            'name' => $name . ' Billing & Network Operations',
            'short_name' => substr($name, 0, 24),
            'description' => app_setting('pwa_description', 'Aplikasi billing ISP, pelanggan, pembayaran, monitoring PPPoE, dan administrasi MikroTik RouterOS.'),
            'lang' => app_setting('default_language', 'id'), 'dir' => 'ltr', 'start_url' => base_url('?source=pwa'), 'scope' => base_url(),
            'display' => 'standalone', 'display_override' => ['window-controls-overlay', 'standalone', 'minimal-ui'],
            'orientation' => 'any', 'background_color' => $backgroundColor, 'theme_color' => $primaryColor,
            'categories' => ['business', 'finance', 'productivity', 'utilities'], 'prefer_related_applications' => false,
            'icons' => [
                ['src' => site_url('pwa/icon/192'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => site_url('pwa/icon/512'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => site_url('pwa/icon/512'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => 'Monitoring PPPoE', 'short_name' => 'Monitoring', 'url' => site_url('monitoring?source=pwa-shortcut'), 'icons' => [['src' => base_url($logo), 'sizes' => 'any']]],
                ['name' => 'Data Pelanggan', 'short_name' => 'Pelanggan', 'url' => site_url('customers?source=pwa-shortcut'), 'icons' => [['src' => base_url($logo), 'sizes' => 'any']]],
                ['name' => 'Pembayaran', 'short_name' => 'Pembayaran', 'url' => site_url('payments?source=pwa-shortcut'), 'icons' => [['src' => base_url($logo), 'sizes' => 'any']]],
            ],
            'launch_handler' => ['client_mode' => ['navigate-existing', 'auto']], 'handle_links' => 'preferred',
        ];
        return $this->output->set_header('Cache-Control: private, max-age=300')->set_content_type('application/manifest+json')->set_output(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function icon($size = 192)
    {
        $size = in_array((int) $size, [192, 512], true) ? (int) $size : 192;
        $relative = ltrim(str_replace(['../', '..\\'], '', (string) app_setting('logo_path', 'assets/img/logo.jpeg')), '/\\');
        $path = FCPATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
        if (!is_file($path) || strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg') $path = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'logo.jpeg';
        $sourceData = is_file($path) ? @file_get_contents($path) : false;
        $source = $sourceData !== false ? @imagecreatefromstring($sourceData) : false;
        if (!$source) { show_404(); return; }
        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false); imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127); imagefill($canvas, 0, 0, $transparent);
        $sourceWidth = imagesx($source); $sourceHeight = imagesy($source);
        $padding = (int) round($size * .1); $available = $size - ($padding * 2);
        $scale = min($available / max(1, $sourceWidth), $available / max(1, $sourceHeight));
        $width = max(1, (int) round($sourceWidth * $scale)); $height = max(1, (int) round($sourceHeight * $scale));
        imagecopyresampled($canvas, $source, (int) (($size - $width) / 2), (int) (($size - $height) / 2), 0, 0, $width, $height, $sourceWidth, $sourceHeight);
        ob_start(); imagepng($canvas, null, 9); $png = ob_get_clean(); imagedestroy($source); imagedestroy($canvas);
        $this->output->set_header('Cache-Control: public, max-age=86400')->set_content_type('image/png')->set_output($png);
    }

    private function color($value, $fallback)
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $value) ? strtolower($value) : $fallback;
    }
}
