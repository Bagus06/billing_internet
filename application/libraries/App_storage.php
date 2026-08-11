<?php
defined('BASEPATH') or exit('No direct script access allowed');

class App_storage
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->library('upload');
    }

    public function storePrivateImage($field, $category = 'customers/ktp', $maxKb = 4096)
    {
        $category = $this->cleanCategory($category);
        $relative = $category;
        $directory = $this->privateRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $this->ensureDirectory($directory, true);
        $data = $this->upload($field, $directory, 'jpg|jpeg|png|webp', $maxKb);
        $key = 'private://' . $relative . '/' . $data['file_name'];
        $this->register($key, 'private', $category, $data);
        return $key;
    }

    public function storePublicBrandImage($field, $maxKb = 2048)
    {
        $relative = 'assets/img/branding';
        $directory = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $this->ensureDirectory($directory, false);
        $data = $this->upload($field, $directory, 'jpg|jpeg|png|webp|gif', $maxKb);
        $key = $relative . '/' . $data['file_name'];
        $this->register($key, 'public', 'branding', $data);
        return $key;
    }

    public function resolvePrivate($key)
    {
        $prefix = 'private://';
        $key = str_replace('\\', '/', trim((string) $key));
        if (strpos($key, $prefix) !== 0 || strpos($key, '..') !== false || !preg_match('#^private://[a-zA-Z0-9/_\-.]+$#', $key)) {
            throw new RuntimeException('Referensi file private tidak valid.');
        }
        $relative = substr($key, strlen('private://'));
        $root = realpath($this->privateRoot());
        $path = realpath($this->privateRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
        if (!$root || !$path || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
            throw new RuntimeException('File private tidak ditemukan.');
        }
        return $path;
    }

    private function upload($field, $directory, $allowedTypes, $maxKb)
    {
        $this->CI->upload->initialize([
            'upload_path' => $directory,
            'allowed_types' => $allowedTypes,
            'max_size' => (int) $maxKb,
            'encrypt_name' => true,
            'remove_spaces' => true,
        ], true);
        if (!$this->CI->upload->do_upload($field)) {
            throw new InvalidArgumentException(strip_tags($this->CI->upload->display_errors('', '')));
        }
        return $this->CI->upload->data();
    }

    private function register($key, $visibility, $category, array $data)
    {
        // Metadata file tidak memerlukan tabel terpisah pada instalasi single ISP.
    }

    private function privateRoot() { return FCPATH . 'storage' . DIRECTORY_SEPARATOR . 'private'; }
    private function cleanCategory($category)
    {
        $category = trim(str_replace('\\', '/', (string) $category), '/');
        if ($category === '' || strpos($category, '..') !== false || !preg_match('#^[a-zA-Z0-9/_-]+$#', $category)) throw new InvalidArgumentException('Kategori storage tidak valid.');
        return $category;
    }
    private function ensureDirectory($directory, $private)
    {
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) throw new RuntimeException('Folder storage tidak dapat dibuat.');
        if ($private) {
            $root = $this->privateRoot();
            if (!is_file($root . DIRECTORY_SEPARATOR . '.htaccess')) @file_put_contents($root . DIRECTORY_SEPARATOR . '.htaccess', "Require all denied\nDeny from all\n");
            if (!is_file($root . DIRECTORY_SEPARATOR . 'index.html')) @file_put_contents($root . DIRECTORY_SEPARATOR . 'index.html', '');
        }
    }
}
