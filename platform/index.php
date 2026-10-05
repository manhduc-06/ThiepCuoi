<?php
/**
 * Quản trị nền tảng: tạo và quản lý trang cưới cho nhiều cặp đôi, mỗi cặp một subdomain (<tên>.BASE_DOMAIN).
 * Chạy ở tên miền gốc (deploy/multi/Caddyfile), pool PHP-FPM riêng (docker/php/platform-pool.conf) được phép
 * chạy deploy/multi/cap-doi.sh — mọi thao tác trên thư mục cặp đôi đi qua script đó (cùng đường với dòng lệnh).
 *
 * Đặt tài khoản quản trị nền tảng (lần đầu hoặc quên mật khẩu), trong container php:
 *   php /srv/thiepcuoi/platform/index.php dat-mat-khau [tên-đăng-nhập]
 */
declare(strict_types=1);

const RESERVED = array('www', 'admin', 'api', 'app', 'mail', 'smtp', 'imap', 'pop', 'ftp', 'ns1', 'ns2', 'cdn',
    'static', 'assets', 'quantri', 'platform', 'root', 'test', 'demo');
const NAME_RE = '/^[a-z0-9][a-z0-9-]{0,38}[a-z0-9]$/';

$SITES = rtrim(getenv('SITES_DIR') ?: '/sites', '/');
$CODE = dirname(__DIR__);
$DATA = $SITES . '/.platform';

function env_str(string $k, string $def = ''): string
{
    $v = getenv($k);
    return $v === false || $v === '' ? $def : (string) $v;
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ensure_data_dir(string $dir): void
{
    if (!is_dir($dir . '/sessions')) {
        @mkdir($dir . '/sessions', 0700, true);
    }
    @chmod($dir, 0700);
}

/* ------------------------------------------------------------------ CLI: đặt mật khẩu quản trị nền tảng */
if (PHP_SAPI === 'cli') {
    if (($argv[1] ?? '') !== 'dat-mat-khau') {
        fwrite(STDERR, "Cách dùng: php platform/index.php dat-mat-khau [tên-đăng-nhập]\n");
        exit(1);
    }
    $user = $argv[2] ?? 'admin';
    if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $user)) {
        fwrite(STDERR, "Tên đăng nhập 3–32 ký tự: chữ không dấu, số, . _ -\n");
        exit(1);
    }
    $tty = function_exists('posix_isatty') && posix_isatty(STDIN);
    $ask = function (string $q) use ($tty): string {
        if ($tty) {
            fwrite(STDOUT, $q);
            @shell_exec('stty -echo 2>/dev/null');
        }
        $v = rtrim((string) fgets(STDIN), "\r\n");
        if ($tty) {
            @shell_exec('stty echo 2>/dev/null');
            fwrite(STDOUT, "\n");
        }
        return $v;
    };
    $p1 = $ask('Mật khẩu quản trị nền tảng (ít nhất 12 ký tự): ');
    $p2 = $ask('Nhập lại: ');
    if (mb_strlen($p1) < 12 || $p1 !== $p2) {
        fwrite(STDERR, "Mật khẩu phải có ít nhất 12 ký tự và hai lần nhập phải khớp.\n");
        exit(1);
    }
    ensure_data_dir($DATA);
    $old = is_file($DATA . '/admin.json') ? json_decode((string) file_get_contents($DATA . '/admin.json'), true) : null;
    $ver = is_array($old) ? (int) ($old['version'] ?? 0) + 1 : 1;
    file_put_contents($DATA . '/admin.json', json_encode(array(
        'username' => $user, 'hash' => password_hash($p1, PASSWORD_DEFAULT), 'version' => $ver,
    ), JSON_PRETTY_PRINT), LOCK_EX);
    chmod($DATA . '/admin.json', 0600);
    echo "Đã đặt tài khoản quản trị nền tảng \"$user\". Các phiên đăng nhập cũ đã bị đăng xuất.\n";
    exit(0);
}

/* ------------------------------------------------------------------ Trang ủng hộ (/ung-ho) */
const DONATE_DEFAULT = array(
    'enabled' => false, 'title' => 'Ủng hộ dự án', 'message' => '', 'bank' => '', 'account' => '', 'holder' => '',
    'note' => 'Ung ho thiep cuoi', 'link_label' => '', 'link_url' => '',
);

function vietqr(string $code): Vietqr
{
    static $q = null;
    if ($q === null) {
        if (!defined('BASEPATH')) {
            define('BASEPATH', $code . '/system/');
        }
        require_once $code . '/application/libraries/Vietqr.php';
        $q = new Vietqr();
    }
    return $q;
}

function load_donate(string $dir): array
{
    $j = is_file($dir . '/donate.json') ? json_decode((string) file_get_contents($dir . '/donate.json'), true) : null;
    return array_merge(DONATE_DEFAULT, is_array($j) ? array_intersect_key($j, DONATE_DEFAULT) : array());
}

function render_donate_page(array $d, string $code, string $nonce): void
{
    $q = vietqr($code);
    $ready = $d['enabled'] && $d['bank'] !== '' && $d['account'] !== '';
    $payload = $ready ? $q->payload($d['bank'], $d['account'], $d['note']) : '';
    if (!$ready || $payload === '') {
        http_response_code(404);
    }
    header('Cache-Control: no-cache');
    $bank = $ready ? $q->bank_name($d['bank']) : '';
    $qrjs = $payload !== '' ? (string) @file_get_contents($code . '/assets/js/vendor/qrcode.js') : '';
    ?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($d['title']) ?></title>
<style>
:root{--bg:#f6f2ee;--card:#fff;--ink:#2f2626;--muted:#7b6e6a;--line:#e8ddd6;--accent:#9a5b67;--accent-ink:#fff}
@media (prefers-color-scheme:dark){:root{--bg:#1d1a19;--card:#272322;--ink:#f1e9e6;--muted:#b3a7a3;--line:#3a3432;--accent:#d08a97;--accent-ink:#1d1a19}}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--bg);color:var(--ink);font:16px/1.6 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;padding:24px 16px}
main{width:100%;max-width:420px;background:var(--card);border:1px solid var(--line);border-radius:18px;padding:28px 24px;text-align:center}
.heart{font-size:1.6rem;color:var(--accent);margin:0}
h1{font:600 1.5rem Georgia,"Times New Roman",serif;margin:4px 0 8px}
.msg{color:var(--muted);margin:0 0 18px;white-space:pre-line}
.qr{background:#fff;border-radius:14px;padding:12px;width:min(280px,100%);margin:0 auto 6px;border:1px solid var(--line)}
.qr svg{display:block;width:100%;height:auto}
.hint{font-size:.82rem;color:var(--muted);margin:0 0 18px}
dl{text-align:left;margin:0 0 18px;border-top:1px solid var(--line)}
dl div{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 2px;border-bottom:1px solid var(--line)}
dt{color:var(--muted);font-size:.88rem}
dd{margin:0;font-weight:600;text-align:right;overflow-wrap:anywhere}
button,.link{font:inherit;border-radius:10px;padding:8px 14px;min-height:40px;cursor:pointer}
button{border:1px solid var(--line);background:transparent;color:var(--ink);font-size:.85rem;padding:4px 10px;min-height:32px}
.link{display:inline-flex;align-items:center;justify-content:center;background:var(--accent);color:var(--accent-ink);text-decoration:none;font-weight:600;width:100%}
.empty{color:var(--muted)}
</style>
</head>
<body>
<main>
<?php if ($payload === ''): ?>
  <p class="heart">♡</p>
  <h1>Trang ủng hộ chưa sẵn sàng</h1>
  <p class="empty">Cảm ơn bạn đã ghé qua. Thông tin ủng hộ đang được cập nhật.</p>
<?php else: ?>
  <p class="heart">♡</p>
  <h1><?= h($d['title']) ?></h1>
  <?php if ($d['message'] !== ''): ?><p class="msg"><?= h($d['message']) ?></p><?php endif; ?>
  <div class="qr" id="qr" role="img" aria-label="Mã VietQR chuyển khoản tới <?= h($bank) ?> <?= h($d['account']) ?>"></div>
  <p class="hint">Mở app ngân hàng → Quét QR. Số tài khoản và nội dung đã điền sẵn, bạn chỉ nhập số tiền.</p>
  <dl>
    <div><dt>Ngân hàng</dt><dd><?= h($bank) ?></dd></div>
    <div><dt>Số tài khoản</dt><dd><span id="acc"><?= h($d['account']) ?></span> <button type="button" id="copy">Sao chép</button></dd></div>
    <?php if ($d['holder'] !== ''): ?><div><dt>Chủ tài khoản</dt><dd><?= h($d['holder']) ?></dd></div><?php endif; ?>
    <?php if ($d['note'] !== ''): ?><div><dt>Nội dung</dt><dd><?= h($q->note($d['note'])) ?></dd></div><?php endif; ?>
  </dl>
  <?php if ($d['link_url'] !== ''): ?><a class="link" href="<?= h($d['link_url']) ?>" target="_blank" rel="noopener noreferrer"><?= h($d['link_label'] !== '' ? $d['link_label'] : 'Ủng hộ qua cách khác') ?> ↗</a><?php endif; ?>
  <script nonce="<?= h($nonce) ?>"><?= $qrjs ?></script>
  <script nonce="<?= h($nonce) ?>">
  (function () {
    var q = qrcode(0, 'M');
    q.addData(<?= json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
    q.make();
    document.getElementById('qr').innerHTML = q.createSvgTag({ cellSize: 6, margin: 2, scalable: true });
    var b = document.getElementById('copy');
    b.addEventListener('click', function () {
      var t = document.getElementById('acc').textContent;
      (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function () { b.textContent = 'Đã chép ✓'; },
        function () { var r = document.createRange(); r.selectNodeContents(document.getElementById('acc')); getSelection().removeAllRanges(); getSelection().addRange(r); });
      setTimeout(function () { b.textContent = 'Sao chép'; }, 2000);
    });
  })();
  </script>
<?php endif; ?>
</main>
</body>
</html>
<?php
}

/* ------------------------------------------------------------------ Web */
$BASE_DOMAIN = env_str('BASE_DOMAIN', 'tenmien.com');
$SITE_SCHEME = env_str('SITE_SCHEME', 'https');
$SITE_PORT = env_str('SITE_PORT');   // vd ":8081" khi thử trên máy
$https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
$client_ip = filter_var($_SERVER['CLIENT_IP'] ?? '', FILTER_VALIDATE_IP) ?: (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
$path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$nonce = base64_encode(random_bytes(16));
header("Content-Security-Policy: default-src 'none'; script-src 'nonce-$nonce'; style-src 'unsafe-inline'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

if ($path === '/ung-ho') {
    render_donate_page(load_donate($DATA), $CODE, $nonce);
    exit;
}
header('X-Robots-Tag: noindex, nofollow');

ensure_data_dir($DATA);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_save_path($DATA . '/sessions');
session_name('tc_platform');
session_set_cookie_params(array('lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict'));
session_start();

$admin = is_file($DATA . '/admin.json') ? json_decode((string) file_get_contents($DATA . '/admin.json'), true) : null;
$admin = is_array($admin) && !empty($admin['hash']) ? $admin : null;
$logged_in = $admin && ($_SESSION['uid'] ?? null) === $admin['username'] && ($_SESSION['ver'] ?? -1) === (int) ($admin['version'] ?? 0);

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function flash(?string $type = null, ?string $msg = null, array $extra = array())
{
    if ($type === null) {
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f;
    }
    $_SESSION['flash'] = array('type' => $type, 'msg' => $msg) + $extra;
    return null;
}

function back(): void
{
    header('Location: /', true, 303);
    exit;
}

/** Giới hạn số lần đăng nhập sai: 10 lần / 15 phút mỗi IP, 50 lần / 15 phút cho tất cả. */
function rate_check(string $dir, string $ip, bool $record): bool
{
    $f = $dir . '/login_fail.json';
    $fp = fopen($f, 'c+');
    if (!$fp) {
        return true;
    }
    flock($fp, LOCK_EX);
    $all = json_decode((string) stream_get_contents($fp), true);
    $all = is_array($all) ? $all : array();
    $now = time();
    $all = array_values(array_filter($all, function ($e) use ($now) { return is_array($e) && $e[1] > $now - 900; }));
    $mine = count(array_filter($all, function ($e) use ($ip) { return $e[0] === $ip; }));
    $ok = $mine < 10 && count($all) < 50;
    if ($record) {
        $all[] = array($ip, $now);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($all));
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    return $ok;
}

/** Chạy deploy/multi/cap-doi.sh (không qua shell: mảng tham số), stdin truyền dữ liệu nhạy cảm như mật khẩu. */
function run_couple_cmd(string $code, string $sites, array $args, string $stdin = ''): array
{
    $cmd = array_merge(array('bash', $code . '/deploy/multi/cap-doi.sh'), $args);
    $env = array('SITES_DIR' => $sites, 'PATH' => '/usr/local/bin:/usr/bin:/bin', 'HOME' => '/tmp', 'LANG' => 'C.UTF-8');
    $p = proc_open($cmd, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, $code, $env);
    if (!is_resource($p)) {
        return array(1, 'Không chạy được cap-doi.sh (máy chủ chặn proc_open?).');
    }
    fwrite($pipes[0], $stdin);
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $rc = proc_close($p);
    return array($rc, trim(preg_replace('/\e\[[0-9;]*m/', '', (string) $out)));
}

function gen_password(): string
{
    $abc = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < 14; $i++) {
        $s .= $abc[random_int(0, strlen($abc) - 1)];
    }
    return $s;
}

function dir_bytes(string $dir): int
{
    if (!is_dir($dir)) {
        return 0;
    }
    $n = 0;
    try {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
            if ($f->isFile()) {
                $n += (int) $f->getSize();
            }
        }
    } catch (Exception $e) {
        // thư mục đang bị đổi tên / xóa giữa chừng
    }
    return $n;
}

function size_text(int $b): string
{
    if ($b >= 1073741824) {
        return number_format($b / 1073741824, 1, ',', '.') . ' GB';
    }
    return max(0, (int) round($b / 1048576)) . ' MB';
}

/** Đọc thông tin hiển thị của một cặp từ CSDL của họ (chỉ đọc). */
function couple_info(string $site): array
{
    $info = array('groom' => '', 'bride' => '', 'date' => '', 'user' => '', 'photos' => 0, 'published' => false,
        'quota' => 0, 'bytes' => dir_bytes($site . '/uploads'), 'ok' => false);
    $db = $site . '/database/anhcuoi.db';
    if (is_file($db)) {
        try {
            $d = new PDO('sqlite:' . $db, null, null, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            $s = $d->query("SELECT key, value FROM settings WHERE key IN ('groom_name','bride_name','wedding_date','published')")
                ->fetchAll(PDO::FETCH_KEY_PAIR);
            $info['groom'] = (string) ($s['groom_name'] ?? '');
            $info['bride'] = (string) ($s['bride_name'] ?? '');
            $info['date'] = (string) ($s['wedding_date'] ?? '');
            $info['published'] = !empty($s['published']);
            $info['user'] = (string) $d->query('SELECT username FROM users ORDER BY id LIMIT 1')->fetchColumn();
            $info['photos'] = (int) $d->query('SELECT COUNT(*) FROM photos')->fetchColumn();
            $info['ok'] = true;
        } catch (Exception $e) {
            // CSDL đang được tạo / bị khóa
        }
    }
    if (is_file($site . '/.quota')) {
        $q = json_decode((string) file_get_contents($site . '/.quota'), true);
        $info['quota'] = (int) ($q['mb'] ?? 0);
    }
    return $info;
}

function list_couples(string $sites): array
{
    $out = array();
    foreach (array('' => 'active', '/.tam-dung' => 'paused') as $sub => $state) {
        foreach ((array) glob($sites . $sub . '/*', GLOB_ONLYDIR) as $dir) {
            $name = basename((string) $dir);
            if (preg_match(NAME_RE, $name)) {
                $out[] = array('name' => $name, 'state' => $state) + couple_info((string) $dir);
            }
        }
    }
    usort($out, function ($a, $b) { return strcmp($a['name'], $b['name']); });
    return $out;
}

/* ------------------------------------------------------------------ Xử lý POST */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Request từ trang khác: Origin (hoặc Referer) phải trùng host. Token CSRF lưu trong session.
    $src = (string) ($_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $src_host = $src !== '' ? strtolower((string) parse_url($src, PHP_URL_HOST) . (parse_url($src, PHP_URL_PORT) ? ':' . parse_url($src, PHP_URL_PORT) : '')) : $host;
    if ($src_host !== $host || !hash_equals((string) $_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ (CSRF). Tải lại trang rồi thử lại.');
    }
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'login') {
        if (!$admin) {
            flash('error', 'Chưa có tài khoản quản trị nền tảng. Tạo bằng lệnh: php platform/index.php dat-mat-khau');
            back();
        }
        if (!rate_check($DATA, $client_ip, false)) {
            flash('error', 'Sai quá nhiều lần. Thử lại sau 15 phút.');
            back();
        }
        $u = (string) ($_POST['username'] ?? '');
        $p = (string) ($_POST['password'] ?? '');
        if (hash_equals((string) $admin['username'], $u) && password_verify($p, (string) $admin['hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = $admin['username'];
            $_SESSION['ver'] = (int) ($admin['version'] ?? 0);
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            back();
        }
        rate_check($DATA, $client_ip, true);
        flash('error', 'Sai tên đăng nhập hoặc mật khẩu.');
        back();
    }

    if (!$logged_in) {
        back();
    }

    if ($action === 'logout') {
        $_SESSION = array();
        session_regenerate_id(true);
        back();
    }

    if ($action === 'donate_save') {
        $d = load_donate($DATA);
        $in = function (string $k, int $max) { return mb_substr(trim(str_replace("\r", '', (string) ($_POST[$k] ?? ''))), 0, $max); };
        $d['enabled'] = !empty($_POST['enabled']);
        $d['title'] = $in('title', 80) ?: DONATE_DEFAULT['title'];
        $d['message'] = $in('message', 600);
        $d['bank'] = $in('bank', 6);
        $d['account'] = preg_replace('/[^0-9A-Za-z]/', '', $in('account', 30));
        $d['holder'] = mb_strtoupper($in('holder', 60));
        $d['note'] = $in('note', 60);
        $d['link_label'] = $in('link_label', 40);
        $d['link_url'] = $in('link_url', 300);
        $errors = array();
        if ($d['bank'] !== '' && vietqr($CODE)->bank_name($d['bank']) === '') {
            $errors[] = 'Ngân hàng không hợp lệ.';
        }
        if ($d['account'] !== '' && strlen($d['account']) > 19) {
            $errors[] = 'Số tài khoản tối đa 19 ký tự.';
        }
        if ($d['enabled'] && ($d['bank'] === '' || $d['account'] === '')) {
            $errors[] = 'Bật trang ủng hộ cần chọn ngân hàng và nhập số tài khoản.';
        }
        if ($d['link_url'] !== '' && !preg_match('~^https://[^\s"<>]+$~i', $d['link_url'])) {
            $errors[] = 'Link ngoài phải bắt đầu bằng https://';
        }
        if ($errors) {
            flash('error', implode(' ', $errors), array('donate' => $d));
            back();
        }
        file_put_contents($DATA . '/donate.json', json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        @chmod($DATA . '/donate.json', 0600);
        flash('ok', $d['enabled'] ? 'Đã lưu trang ủng hộ.' : 'Đã lưu (trang ủng hộ đang tắt).');
        back();
    }

    $name = strtolower(trim((string) ($_POST['name'] ?? '')));
    if (!preg_match(NAME_RE, $name)) {
        flash('error', 'Tên subdomain không hợp lệ: 2–40 ký tự, chữ thường không dấu, số, gạch ngang.');
        back();
    }

    if ($action === 'create') {
        $groom = trim((string) ($_POST['groom'] ?? ''));
        $bride = trim((string) ($_POST['bride'] ?? ''));
        $date = trim((string) ($_POST['date'] ?? ''));
        $user = trim((string) ($_POST['username'] ?? '')) ?: 'admin';
        $quota = trim((string) ($_POST['quota'] ?? ''));
        $errors = array();
        if (in_array($name, RESERVED, true)) {
            $errors[] = "Tên \"$name\" được dành riêng.";
        }
        if ($groom === '' || $bride === '' || preg_match('/[\r\n]/', $groom . $bride)) {
            $errors[] = 'Nhập tên chú rể và cô dâu.';
        }
        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors[] = 'Ngày cưới không hợp lệ.';
        }
        if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $user)) {
            $errors[] = 'Tên đăng nhập 3–32 ký tự: chữ không dấu, số, . _ -';
        }
        if ($quota !== '' && !preg_match('/^[1-9][0-9]{0,6}$/', $quota)) {
            $errors[] = 'Dung lượng tối đa phải là số MB.';
        }
        if ($errors) {
            flash('error', implode(' ', $errors), array('form' => compact('name', 'groom', 'bride', 'date', 'user', 'quota')));
            back();
        }
        $pass = gen_password();
        $args = array('them', $name);
        if ($quota !== '') {
            array_push($args, '--quota', $quota);
        }
        // tao-trang.sh đọc lần lượt: tên đăng nhập, chú rể, cô dâu, ngày cưới, mật khẩu, nhập lại mật khẩu.
        list($rc, $out) = run_couple_cmd($CODE, $SITES, $args, "$user\n$groom\n$bride\n$date\n$pass\n$pass\n");
        if ($rc !== 0) {
            flash('error', 'Chưa tạo được: ' . $out, array('form' => compact('name', 'groom', 'bride', 'date', 'user', 'quota')));
            back();
        }
        flash('created', "Đã tạo trang cho $groom & $bride.", array(
            'url' => $SITE_SCHEME . '://' . $name . '.' . $BASE_DOMAIN . $SITE_PORT . '/admin', 'user' => $user, 'pass' => $pass,
        ));
        back();
    }

    if ($action === 'reset') {
        $pass = gen_password();
        list($rc, $out) = run_couple_cmd($CODE, $SITES, array('doi-mat-khau', $name), $pass . "\n");
        if ($rc !== 0) {
            flash('error', $out);
        } else {
            flash('created', "Đã đặt lại mật khẩu quản trị của \"$name\". Phiên đăng nhập cũ của họ đã bị đăng xuất.", array(
                'url' => $SITE_SCHEME . '://' . $name . '.' . $BASE_DOMAIN . $SITE_PORT . '/admin', 'user' => couple_info($SITES . '/' . $name)['user'] ?: 'admin', 'pass' => $pass,
            ));
        }
        back();
    }

    if ($action === 'pause' || $action === 'resume') {
        list($rc, $out) = run_couple_cmd($CODE, $SITES, array($action === 'pause' ? 'tam-dung' : 'mo-lai', $name));
        flash($rc === 0 ? 'ok' : 'error', $out);
        back();
    }

    if ($action === 'delete') {
        if ((string) ($_POST['confirm'] ?? '') !== $name) {
            flash('error', "Gõ đúng tên \"$name\" vào ô xác nhận để gỡ trang.");
            back();
        }
        $paused = is_dir($SITES . '/.tam-dung/' . $name);
        if ($paused) {
            run_couple_cmd($CODE, $SITES, array('mo-lai', $name));
        }
        list($rc, $out) = run_couple_cmd($CODE, $SITES, array('xoa', $name), $name . "\n");
        flash($rc === 0 ? 'ok' : 'error', $out);
        back();
    }

    flash('error', 'Thao tác không hợp lệ.');
    back();
}

/* ------------------------------------------------------------------ Giao diện */
$flash = flash();
$form = is_array($flash['form'] ?? null) ? $flash['form'] : array();
$csrf = h($_SESSION['csrf']);
$couples = $logged_in ? list_couples($SITES) : array();
$site_url = function (string $n) use ($SITE_SCHEME, $BASE_DOMAIN, $SITE_PORT): string {
    return $SITE_SCHEME . '://' . $n . '.' . $BASE_DOMAIN . $SITE_PORT;
};
?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản trị nền tảng · <?= h($BASE_DOMAIN) ?></title>
<style>
:root{--bg:#f6f2ee;--card:#fff;--ink:#2f2626;--muted:#7b6e6a;--line:#e8ddd6;--accent:#9a5b67;--accent-ink:#fff;--ok:#2f7a4d;--err:#b03a3a;--warn:#9a6a1f}
@media (prefers-color-scheme:dark){:root{--bg:#1d1a19;--card:#272322;--ink:#f1e9e6;--muted:#b3a7a3;--line:#3a3432;--accent:#d08a97;--accent-ink:#1d1a19;--ok:#7fcf9c;--err:#f08c8c;--warn:#e6b86a}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 20px;border-bottom:1px solid var(--line);background:var(--card)}
header h1{font:600 1.05rem Georgia,serif;margin:0}
header small{color:var(--muted)}
main{max-width:1080px;margin:0 auto;padding:20px 16px 48px}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:18px;margin-bottom:18px}
h2{font:600 1.1rem Georgia,serif;margin:0 0 12px}
label{display:flex;flex-direction:column;gap:4px;font-size:.88rem;color:var(--muted)}
input,select,textarea{font:inherit;color:var(--ink);background:transparent;border:1px solid var(--line);border-radius:8px;padding:8px 10px;min-height:40px}
select option{color:#2f2626}
input:focus{outline:2px solid var(--accent);outline-offset:1px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end}
.sub{display:flex;align-items:center;border:1px solid var(--line);border-radius:8px;overflow:hidden}
.sub input{border:0;border-radius:0;flex:1;min-width:0}
.sub span{padding:0 10px;color:var(--muted);white-space:nowrap;font-size:.85rem}
button{font:inherit;cursor:pointer;border-radius:8px;border:1px solid var(--line);background:transparent;color:var(--ink);padding:7px 12px;min-height:36px}
a.btn{display:inline-flex;align-items:center;border-radius:8px;border:1px solid var(--line);color:var(--ink);padding:7px 12px;min-height:36px;text-decoration:none}
button.primary{background:var(--accent);border-color:var(--accent);color:var(--accent-ink);font-weight:600;min-height:40px}
button.danger{color:var(--err);border-color:color-mix(in srgb,var(--err) 40%,var(--line))}
.flash{border-radius:10px;padding:12px 14px;margin-bottom:16px;border:1px solid var(--line);background:var(--card)}
.flash.error{border-color:var(--err);color:var(--err)}
.flash.ok{border-color:var(--ok)}
.flash.created{border-color:var(--ok)}
.flash pre{margin:8px 0 0;padding:10px;background:var(--bg);border-radius:8px;white-space:pre-wrap;color:var(--ink);font-size:.9rem}
table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:10px 8px;border-bottom:1px solid var(--line);vertical-align:top}
th{font-size:.8rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.03em}
td small{color:var(--muted)}
.tag{display:inline-block;font-size:.75rem;padding:1px 8px;border-radius:99px;border:1px solid var(--line)}
.tag.on{color:var(--ok);border-color:var(--ok)}
.tag.off{color:var(--warn);border-color:var(--warn)}
.acts{display:flex;flex-wrap:wrap;gap:6px}
.acts form{margin:0}
details summary{cursor:pointer;color:var(--err);font-size:.9rem;list-style:none;padding:7px 0}
details form{display:flex;gap:6px;margin-top:6px}
details input{min-height:36px;width:150px}
a{color:var(--accent)}
.login{max-width:360px;margin:12vh auto}
.login form{display:flex;flex-direction:column;gap:12px}
.muted{color:var(--muted)}
@media (max-width:720px){thead{display:none}tr{display:block;border-bottom:1px solid var(--line);padding:8px 0}td{display:block;border:0;padding:4px 0}}
</style>
</head>
<body>
<?php if (!$logged_in): ?>
<main class="login">
  <div class="card">
    <h2>Quản trị nền tảng</h2>
    <p class="muted"><?= h($BASE_DOMAIN) ?></p>
    <?php if ($flash): ?><div class="flash <?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div><?php endif; ?>
    <?php if (!$admin): ?>
      <p>Chưa có tài khoản. Tạo trong máy chủ:</p>
      <pre class="muted">docker compose -f docker-compose.multi.yml exec -u www-data php php platform/index.php dat-mat-khau</pre>
    <?php else: ?>
    <form method="post" action="/">
      <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="login">
      <label>Tên đăng nhập<input name="username" autocomplete="username" required autofocus></label>
      <label>Mật khẩu<input type="password" name="password" autocomplete="current-password" required></label>
      <button class="primary" type="submit">Đăng nhập</button>
    </form>
    <?php endif; ?>
  </div>
</main>
<?php else: ?>
<header>
  <div><h1>Trang cưới · <?= h($BASE_DOMAIN) ?></h1><small><?= count($couples) ?> cặp đôi</small></div>
  <form method="post" action="/"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="logout"><button type="submit">Đăng xuất</button></form>
</header>
<main>
  <?php if ($flash): ?>
  <div class="flash <?= h($flash['type']) ?>" role="status">
    <?= h($flash['msg']) ?>
    <?php if (!empty($flash['pass'])): ?>
      <pre>Trang quản trị: <?= h($flash['url']) ?>

Tên đăng nhập: <?= h($flash['user']) ?>

Mật khẩu:      <?= h($flash['pass']) ?></pre>
      <small class="muted">Mật khẩu chỉ hiện một lần — chép gửi cho cặp đôi và nhắc họ đổi trong Cài đặt.</small>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <section class="card">
    <h2>Tạo trang mới</h2>
    <form method="post" action="/" class="grid">
      <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="create">
      <label>Subdomain
        <span class="sub"><input name="name" required pattern="[a-z0-9][a-z0-9-]{0,38}[a-z0-9]" placeholder="lan-hung" value="<?= h($form['name'] ?? '') ?>"><span>.<?= h($BASE_DOMAIN) ?></span></span></label>
      <label>Tên chú rể<input name="groom" required maxlength="80" value="<?= h($form['groom'] ?? '') ?>"></label>
      <label>Tên cô dâu<input name="bride" required maxlength="80" value="<?= h($form['bride'] ?? '') ?>"></label>
      <label>Ngày cưới<input type="date" name="date" value="<?= h($form['date'] ?? '') ?>"></label>
      <label>Tên đăng nhập<input name="username" value="<?= h($form['user'] ?? 'admin') ?>" pattern="[A-Za-z0-9_.\-]{3,32}"></label>
      <label>Dung lượng tối đa (MB)<input name="quota" inputmode="numeric" placeholder="không giới hạn" value="<?= h($form['quota'] ?? '') ?>"></label>
      <button class="primary" type="submit">Tạo trang</button>
    </form>
    <p class="muted" style="margin:10px 0 0;font-size:.85rem">Mật khẩu quản trị của cặp đôi được tạo ngẫu nhiên và hiện một lần sau khi tạo. Trang chạy ngay tại subdomain (DNS dạng *.<?= h($BASE_DOMAIN) ?>).</p>
  </section>

  <section class="card">
    <h2>Các cặp đôi</h2>
    <?php if (!$couples): ?><p class="muted">Chưa có trang nào.</p><?php else: ?>
    <table>
      <thead><tr><th>Trang</th><th>Cặp đôi</th><th>Ảnh · dung lượng</th><th>Thao tác</th></tr></thead>
      <tbody>
      <?php foreach ($couples as $c): $n = $c['name']; ?>
        <tr>
          <td>
            <?php if ($c['state'] === 'active'): ?><a href="<?= h($site_url($n)) ?>" target="_blank" rel="noopener"><?= h($n) ?></a><?php else: ?><?= h($n) ?><?php endif; ?>
            <br><span class="tag <?= $c['state'] === 'active' ? 'on' : 'off' ?>"><?= $c['state'] === 'active' ? ($c['published'] ? 'đang chạy' : 'chưa xuất bản') : 'tạm dừng' ?></span>
          </td>
          <td><?= h(trim($c['groom'] . ' & ' . $c['bride'], ' &')) ?><br><small><?= $c['date'] !== '' ? h(date('d/m/Y', strtotime($c['date']) ?: 0)) : 'chưa có ngày' ?> · tài khoản <?= h($c['user'] ?: '—') ?></small></td>
          <td><?= (int) $c['photos'] ?> ảnh<br><small><?= h(size_text((int) $c['bytes'])) ?><?= $c['quota'] ? ' / ' . h(size_text($c['quota'] * 1048576)) : '' ?></small></td>
          <td>
            <div class="acts">
              <?php if ($c['state'] === 'active'): ?>
                <a class="btn" href="<?= h($site_url($n) . '/admin') ?>" target="_blank" rel="noopener">Quản trị ↗</a>
                <form method="post" action="/"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="name" value="<?= h($n) ?>"><button name="action" value="reset">Đặt lại mật khẩu</button></form>
                <form method="post" action="/"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="name" value="<?= h($n) ?>"><button name="action" value="pause">Tạm dừng</button></form>
              <?php else: ?>
                <form method="post" action="/"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="name" value="<?= h($n) ?>"><button name="action" value="resume">Mở lại</button></form>
              <?php endif; ?>
            </div>
            <details>
              <summary>Gỡ trang…</summary>
              <form method="post" action="/"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="name" value="<?= h($n) ?>">
                <input name="confirm" placeholder="gõ <?= h($n) ?>" aria-label="Gõ <?= h($n) ?> để xác nhận" autocomplete="off"><button class="danger" type="submit">Gỡ</button></form>
              <small class="muted">Dữ liệu được cất vào sites/.da-xoa, khôi phục được.</small>
            </details>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>

  <?php $don = is_array($flash['donate'] ?? null) ? $flash['donate'] : load_donate($DATA); $banks = vietqr($CODE)->banks(); asort($banks); ?>
  <section class="card" id="ung-ho">
    <h2>Trang ủng hộ</h2>
    <p class="muted" style="margin-top:-6px">Nút “♡ Ủng hộ” trong trang quản trị của mọi cặp đôi dẫn tới
      <a href="/ung-ho" target="_blank" rel="noopener"><?= h($SITE_SCHEME . '://' . $BASE_DOMAIN . $SITE_PORT) ?>/ung-ho</a>
      — <?= $don['enabled'] ? 'đang bật' : 'đang tắt (khách vào thấy “chưa sẵn sàng”)' ?>.</p>
    <form method="post" action="/" class="grid">
      <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="donate_save">
      <label>Tiêu đề<input name="title" maxlength="80" value="<?= h($don['title']) ?>"></label>
      <label>Ngân hàng
        <select name="bank"><option value="">— chọn —</option>
          <?php foreach ($banks as $bin => $bn): ?><option value="<?= h((string) $bin) ?>"<?= (string) $bin === $don['bank'] ? ' selected' : '' ?>><?= h($bn) ?></option><?php endforeach; ?>
        </select></label>
      <label>Số tài khoản<input name="account" inputmode="numeric" maxlength="30" value="<?= h($don['account']) ?>"></label>
      <label>Chủ tài khoản<input name="holder" maxlength="60" placeholder="NGUYEN VAN A" value="<?= h($don['holder']) ?>"></label>
      <label>Nội dung chuyển khoản<input name="note" maxlength="60" value="<?= h($don['note']) ?>"></label>
      <label>Link ngoài (không bắt buộc)<input name="link_url" placeholder="https://…" value="<?= h($don['link_url']) ?>"></label>
      <label>Chữ trên nút link ngoài<input name="link_label" maxlength="40" placeholder="Ủng hộ qua Momo" value="<?= h($don['link_label']) ?>"></label>
      <label style="grid-column:1/-1">Lời nhắn<textarea name="message" rows="3" maxlength="600"><?= h($don['message']) ?></textarea></label>
      <label style="flex-direction:row;align-items:center;gap:8px;color:var(--ink)"><input type="checkbox" name="enabled" value="1" style="min-height:auto"<?= $don['enabled'] ? ' checked' : '' ?>> Bật trang ủng hộ</label>
      <button class="primary" type="submit">Lưu</button>
    </form>
  </section>
</main>
<?php endif; ?>
</body>
</html>
