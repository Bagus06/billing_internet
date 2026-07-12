<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pwa extends CI_Controller
{
    public function manifest()
    {
        $name = app_setting('isp_name', 'ISP BATARA NET');
        $logo = app_setting('logo_path', 'assets/img/logo.jpeg');
        $manifest = [
            'id' => base_url(),
            'name' => $name . ' Billing & Network Operations',
            'short_name' => substr($name, 0, 24),
            'description' => 'Aplikasi billing ISP, pelanggan, pembayaran, monitoring PPPoE, dan administrasi MikroTik RouterOS.',
            'lang' => app_language(), 'dir' => 'ltr', 'start_url' => base_url('?source=pwa'), 'scope' => base_url(),
            'display' => 'standalone', 'display_override' => ['window-controls-overlay', 'standalone', 'minimal-ui'],
            'orientation' => 'any', 'background_color' => '#050914', 'theme_color' => '#050914',
            'categories' => ['business', 'finance', 'productivity', 'utilities'], 'prefer_related_applications' => false,
            'icons' => [['src' => base_url($logo), 'sizes' => 'any', 'purpose' => 'any']],
            'shortcuts' => [
                ['name' => 'Monitoring PPPoE', 'short_name' => 'Monitoring', 'url' => site_url('monitoring?source=pwa-shortcut'), 'icons' => [['src' => base_url($logo), 'sizes' => 'any']]],
                ['name' => 'Data Pelanggan', 'short_name' => 'Pelanggan', 'url' => site_url('customers?source=pwa-shortcut'), 'icons' => [['src' => base_url($logo), 'sizes' => 'any']]],
                ['name' => 'Pembayaran', 'short_name' => 'Pembayaran', 'url' => site_url('payments?source=pwa-shortcut'), 'icons' => [['src' => base_url($logo), 'sizes' => 'any']]],
            ],
            'launch_handler' => ['client_mode' => ['navigate-existing', 'auto']], 'handle_links' => 'preferred',
        ];
        return $this->output->set_content_type('application/manifest+json')->set_output(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
