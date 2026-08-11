<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#071326">
    <meta name="robots" content="noindex,nofollow">
    <title>Layanan Terisolir - <?= html_escape($isp_name) ?></title>
    <style>
        * { box-sizing: border-box; }
        html, body { min-height: 100%; margin: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 22px;
            color: #eaf4ff;
            background: radial-gradient(circle at 20% 10%, #12366d 0, transparent 38%), linear-gradient(145deg, #030711, #071326 55%, #081a31);
            font-family: Arial, Helvetica, sans-serif;
        }
        .notice {
            width: 100%;
            max-width: 520px;
            padding: 30px 24px;
            text-align: center;
            border: 1px solid rgba(132, 190, 255, .3);
            border-radius: 24px;
            background: rgba(9, 24, 48, .94);
            box-shadow: 0 22px 65px rgba(0, 0, 0, .45), 0 0 35px rgba(34, 129, 255, .12);
        }
        .logo { width: 82px; height: 82px; object-fit: contain; border-radius: 18px; background: #fff; padding: 6px; }
        .warning { width: 52px; height: 52px; line-height: 52px; margin: 20px auto 14px; border-radius: 50%; color: #211500; background: #ffc247; font-size: 30px; font-weight: 800; }
        h1 { margin: 0 0 12px; font-size: clamp(25px, 7vw, 34px); line-height: 1.16; }
        p { margin: 0; color: #b9cae0; font-size: 16px; line-height: 1.65; }
        .info { margin-top: 22px; padding: 16px; color: #ffe6a4; border: 1px solid rgba(255, 194, 71, .38); border-radius: 15px; background: rgba(255, 194, 71, .1); line-height: 1.55; }
        .isp { margin-top: 20px; color: #78baff; font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        @media (max-width: 480px) { body { padding: 14px; } .notice { padding: 25px 18px; border-radius: 20px; } }
    </style>
</head>
<body>
    <main class="notice">
        <img class="logo" src="<?= html_escape($logo_url) ?>" alt="<?= html_escape($isp_name) ?>">
        <div class="warning" aria-hidden="true">!</div>
        <h1>Layanan Internet Terisolir</h1>
        <p>Layanan internet Anda sementara dibatasi karena pembayaran bulan berjalan belum tercatat.</p>
        <div class="info">Silakan lakukan pembayaran atau hubungi layanan pelanggan. Akses akan dipulihkan setelah pembayaran berhasil dicatat.</div>
        <div class="isp"><?= html_escape($isp_name) ?></div>
    </main>
</body>
</html>
