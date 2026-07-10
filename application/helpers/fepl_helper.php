<?php if (!defined("BASEPATH")) exit("No direct script access allowed");

if (!function_exists('fileicon')) {
    function fileicon($extension)
    {
        $reticon = '';
        $fileicon = [
            // Format Gambar
            'jpg' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'jpeg' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'png' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'jpe' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'jfif' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'gif' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'bmp' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'heif' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'webp' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'svg' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'eps' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'ai' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'psd' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'ico' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'tiff' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'raw' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',
            'indd' => '<i class="fas fa-file-image fa-lg" style="padding-right:10px"></i>',

            // Format Video
            'mp4' => '<i class="fas fa-file-video fa-lg" style="padding-right:10px"></i>',
            'wmv' => '<i class="fas fa-file-video fa-lg" style="padding-right:10px"></i>',
            'avi' => '<i class="fas fa-file-video fa-lg" style="padding-right:10px"></i>',
            'mov' => '<i class="fas fa-file-video fa-lg" style="padding-right:10px"></i>',
            'flv' => '<i class="fas fa-file-video fa-lg" style="padding-right:10px"></i>',
            '3gp' => '<i class="fas fa-file-video fa-lg" style="padding-right:10px"></i>',
            'webm' => '<i class="fas fa-file-video fa-lg" style="padding-right:10px"></i>',
            'mpg' => '<i class="fas fa-file-video fa-lg" style="padding-right:10px"></i>',
            'mpeg' => '<i class="fas fa-file-video fa-lg" style="padding-right:10px"></i>',
            'avchd' => '<i class="fas fa-file-video fa-lg" style="padding-right:10px"></i>',

            // Format Audio
            'wav' => '<i class="fas fa-file-audio fa-lg" style="padding-right:10px"></i>',
            'wma' => '<i class="fas fa-file-audio fa-lg" style="padding-right:10px"></i>',
            'pcm' => '<i class="fas fa-file-audio fa-lg" style="padding-right:10px"></i>',
            'aiff' => '<i class="fas fa-file-audio fa-lg" style="padding-right:10px"></i>',
            'ogg' => '<i class="fas fa-file-audio fa-lg" style="padding-right:10px"></i>',
            'flac' => '<i class="fas fa-file-audio fa-lg" style="padding-right:10px"></i>',
            'alac' => '<i class="fas fa-file-audio fa-lg" style="padding-right:10px"></i>',
            'midi' => '<i class="fas fa-file-audio fa-lg" style="padding-right:10px"></i>',
            'mp3' => '<i class="fas fa-file-audio fa-lg" style="padding-right:10px"></i>',

            // Format Archive
            'rar' => '<i class="fas fa-file-archive fa-lg" style="padding-right:10px"></i>',
            'zip' => '<i class="fas fa-file-archive fa-lg" style="padding-right:10px"></i>',
            'gz' => '<i class="fas fa-file-archive fa-lg" style="padding-right:10px"></i>',
            'jar' => '<i class="fas fa-file-archive fa-lg" style="padding-right:10px"></i>',
            'apk' => '<i class="fas fa-file-archive fa-lg" style="padding-right:10px"></i>',
            '7z' => '<i class="fas fa-file-archive fa-lg" style="padding-right:10px"></i>',
            'dmg' => '<i class="fas fa-file-archive fa-lg" style="padding-right:10px"></i>',
            'kgb' => '<i class="fas fa-file-archive fa-lg" style="padding-right:10px"></i>',
            'tib' => '<i class="fas fa-file-archive fa-lg" style="padding-right:10px"></i>',
            'cab' => '<i class="fas fa-file-archive fa-lg" style="padding-right:10px"></i>',

            // Format Code
            'html' => '<i class="fas fa-file-code fa-lg" style="padding-right:10px"></i>',
            'css' => '<i class="fas fa-file-code fa-lg" style="padding-right:10px"></i>',
            'scss' => '<i class="fas fa-file-code fa-lg" style="padding-right:10px"></i>',
            'js' => '<i class="fas fa-file-code fa-lg" style="padding-right:10px"></i>',
            'php' => '<i class="fas fa-file-code fa-lg" style="padding-right:10px"></i>',
            'sql' => '<i class="fas fa-file-code fa-lg" style="padding-right:10px"></i>',

            // Format MS.Office
            'xls' => '<i class="fas fa-file-excel fa-lg" style="padding-right:10px"></i>',
            'xlsx' => '<i class="fas fa-file-excel fa-lg" style="padding-right:10px"></i>',
            'xlsm' => '<i class="fas fa-file-excel fa-lg" style="padding-right:10px"></i>',
            'xltx' => '<i class="fas fa-file-excel fa-lg" style="padding-right:10px"></i>',
            'xltm' => '<i class="fas fa-file-excel fa-lg" style="padding-right:10px"></i>',
            'xlsb' => '<i class="fas fa-file-excel fa-lg" style="padding-right:10px"></i>',
            'xlam' => '<i class="fas fa-file-excel fa-lg" style="padding-right:10px"></i>',
            'ppt' => '<i class="fas fa-file-powerpoint fa-lg" style="padding-right:10px"></i>',
            'pptx' => '<i class="fas fa-file-powerpoint fa-lg" style="padding-right:10px"></i>',
            'pptm' => '<i class="fas fa-file-powerpoint fa-lg" style="padding-right:10px"></i>',
            'potx' => '<i class="fas fa-file-powerpoint fa-lg" style="padding-right:10px"></i>',
            'potm' => '<i class="fas fa-file-powerpoint fa-lg" style="padding-right:10px"></i>',
            'ppam' => '<i class="fas fa-file-powerpoint fa-lg" style="padding-right:10px"></i>',
            'ppsx' => '<i class="fas fa-file-powerpoint fa-lg" style="padding-right:10px"></i>',
            'ppsm' => '<i class="fas fa-file-powerpoint fa-lg" style="padding-right:10px"></i>',
            'sldx' => '<i class="fas fa-file-powerpoint fa-lg" style="padding-right:10px"></i>',
            'sldm' => '<i class="fas fa-file-powerpoint fa-lg" style="padding-right:10px"></i>',
            'thmx' => '<i class="fas fa-file-powerpoint fa-lg" style="padding-right:10px"></i>',
            'doc' => '<i class="fas fa-file-word fa-lg" style="padding-right:10px"></i>>',
            'docx' => '<i class="fas fa-file-word fa-lg" style="padding-right:10px"></i>>',
            'docm' => '<i class="fas fa-file-word fa-lg" style="padding-right:10px"></i>>',
            'dotx' => '<i class="fas fa-file-word fa-lg" style="padding-right:10px"></i>>',
            'dotm' => '<i class="fas fa-file-word fa-lg" style="padding-right:10px"></i>>',

            // Format PDF
            'pdf' => '<i class="fas fa-file-pdf fa-lg" style="padding-right:10px"></i>',
        ];

        if (!empty($fileicon[strtolower($extension)])) {
            $reticon = $fileicon[strtolower($extension)];
        } else {
            $reticon = '<i class="fas fa-file fa-lg" style="padding-right:10px"></i>';
        }
        return $reticon;
    }
}
