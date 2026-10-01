<?php
// Router cho PHP built-in server (run_window.bat, run_mac.sh, Docker, gói Synology).
// Serve static files trực tiếp, còn lại qua index.php. Chép từ pos_banhang 5.0.3 (đã qua kiểm
// thử tấn công): built-in server KHÔNG đọc .htaccess nên nếu không chặn ở đây thì
// /database/anhcuoi.db (hash mật khẩu, mật khẩu album), /database/.secret_key,
// /cloudflared/tunnel.json (token tunnel) đều tải được qua Cloudflare Tunnel.
// Cơ chế:
//   1. Chuẩn hóa đường dẫn (bỏ '.', '//', '\'), từ chối '..', NUL, và các dạng tên chỉ có
//      nghĩa trên Windows: dấu chấm/khoảng trắng cuối tên ("database."), ADS ("x.db::$DATA"),
//      tên ngắn 8.3 ("DATABA~1") — NTFS coi chúng là cùng một file nên phải chặn trước.
//   2. Chặn cứng thư mục/đuôi file riêng tư (không phân biệt hoa thường).
//   3. File tĩnh chỉ được phục vụ khi đuôi nằm trong danh sách cho phép; PHP chỉ chạy qua index.php.
//   4. Kiểm lại bằng realpath (bắt mọi biến thể tên mà hệ điều hành tự quy về cùng một file).
$uri = (string) (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
$cut = strcspn($uri, '?#');                 // KHÔNG dùng parse_url: "//database/x" bị hiểu là host
$uri = rb_router_normalize(rawurldecode(substr($uri, 0, $cut)));

if ($uri === null || rb_router_is_private($uri)) {
    rb_router_404();
}

$file = __DIR__ . $uri;

// Serve static files directly (not directories). File .php lẻ KHÔNG được thực thi trực tiếp:
// mọi request động đi qua index.php (CodeIgniter) như trên Apache.
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    if ($uri === '/index.php') {
        return false;
    }
    if (!rb_router_static_allowed($uri) || !rb_router_realpath_ok($file)) {
        rb_router_404();
    }
    // Media: built-in server không hỗ trợ HTTP Range -> Chromium/iOS không tua được (R3-21). Tự phục vụ.
    if (preg_match('~\.(mp3|m4a|ogg)$~i', $uri)) {
        rb_router_serve_media($file);
    }
    return false;
}

// Serve index.html for directory requests (e.g. /help/)
if (is_dir($file)) {
    $index = rtrim($file, '/\\') . '/index.html';
    if (file_exists($index) && rb_router_realpath_ok($index)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($index);
        exit;
    }
}

// File tải lên không còn (ảnh đã xóa/thay): 404 thật, không đẩy sang ứng dụng.
if (strncasecmp($uri, '/uploads/', 9) === 0) {
    rb_router_404();
}

// Built-in server đặt SCRIPT_NAME = đường dẫn yêu cầu khi thư mục cha tồn tại (vd /uploads/photos/2a/x.jpg
// đã bị xóa) -> CodeIgniter cắt nhầm URI thành trang chủ và base_url sai. Luôn quy về front controller.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
unset($_SERVER['PATH_INFO'], $_SERVER['ORIG_PATH_INFO']);

require_once __DIR__ . '/index.php';

function rb_router_404()
{
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not Found';
    exit;
}

/**
 * Phục vụ file media có hỗ trợ "Range: bytes=a-b" (một khoảng): 206 / 416 / 200.
 * Nhiều khoảng (a-b,c-d) hoặc cú pháp lạ -> bỏ qua Range, trả 200 cả file (RFC 7233 cho phép).
 */
function rb_router_serve_media($file)
{
    $size = filesize($file);
    $fp = ($size === false) ? false : fopen($file, 'rb');
    if ($fp === false) {
        rb_router_404();
    }
    $types = array('mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4', 'ogg' => 'audio/ogg');
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mtime = filemtime($file);
    $start = 0;
    $end = $size - 1;
    $status = 200;
    $range = isset($_SERVER['HTTP_RANGE']) ? trim((string) $_SERVER['HTTP_RANGE']) : '';
    if ($range !== '' && preg_match('~^bytes\s*=\s*(\d*)\s*-\s*(\d*)$~i', $range, $m) && ($m[1] !== '' || $m[2] !== '')) {
        if ($m[1] === '') {                       // bytes=-N: N byte cuối
            $n = (int) $m[2];
            $start = $n >= $size ? 0 : $size - $n;
            $ok = $n > 0 && $size > 0;
        } else {
            $start = (int) $m[1];
            if ($m[2] !== '') {
                $end = min((int) $m[2], $size - 1);
            }
            $ok = $start < $size && $start <= (int) ($m[2] === '' ? $start : $m[2]);
        }
        if (!$ok) {
            fclose($fp);
            http_response_code(416);
            header('Accept-Ranges: bytes');
            header('Content-Range: bytes */' . $size);
            header('Content-Length: 0');
            exit;
        }
        $status = 206;
    }
    $length = $size > 0 ? $end - $start + 1 : 0;
    http_response_code($status);
    header('Content-Type: ' . (isset($types[$ext]) ? $types[$ext] : 'application/octet-stream'));
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . $length);
    if ($mtime !== false) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    }
    header('Cache-Control: public, max-age=86400');
    if ($status === 206) {
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }
    if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'HEAD') {
        fclose($fp);
        exit;
    }
    if ($start > 0) {
        fseek($fp, $start);
    }
    $left = $length;
    while ($left > 0 && !feof($fp)) {
        $chunk = fread($fp, (int) min(65536, $left));
        if ($chunk === false || $chunk === '') {
            break;
        }
        echo $chunk;
        $left -= strlen($chunk);
        if (connection_aborted()) {
            break;
        }
    }
    fclose($fp);
    exit;
}

/**
 * '/a//b/./c' -> '/a/b/c'. Trả null nếu đường dẫn có dạng nguy hiểm.
 */
function rb_router_normalize($path)
{
    if (strpos($path, "\0") !== false) {
        return null;
    }
    $out = array();
    foreach (explode('/', str_replace('\\', '/', $path)) as $seg) {
        if ($seg === '' || $seg === '.') {
            continue;
        }
        if ($seg === '..'
            || strpos($seg, ':') !== false                 // ADS "::$DATA", ổ đĩa "C:"
            || preg_match('~[. ]$~', $seg)                 // "database." / "x.db " (Windows bỏ đuôi này)
            || preg_match('#~[0-9]#', $seg)                // tên ngắn 8.3: DATABA~1
            || preg_match('~[\x00-\x1f<>"|*?]~', $seg)) {  // ký tự không hợp lệ trong tên file
            return null;
        }
        $out[] = $seg;
    }
    return '/' . implode('/', $out);
}

/**
 * Đường dẫn không bao giờ được phục vụ trực tiếp qua HTTP.
 * - thư mục chứa dữ liệu, mã nguồn, runtime, token tunnel.
 * - dotfile (.secret_key, .public_url, .app_port, .git...).
 * - đuôi file dữ liệu/cấu hình/script.
 */
function rb_router_is_private($uri)
{
    if (preg_match('~^/(database|cloudflared|application|system|vendor|php|Tools|Script|logs)(/|$)~i', $uri)) {
        return true;
    }
    if (preg_match('~/\.[^/]~', $uri)) {
        return true;
    }
    if (preg_match('~\.(db|db-wal|db-shm|db-journal|sqlite|sqlite3|sql|log|ini|sh|bat|cmd|ps1|exe|dll|lock|pid|env|bak|yml|yaml|key|pem|crt)$~i', $uri)) {
        return true;
    }
    if (preg_match('~^/[^/]+\.(txt|md)$~i', $uri)) {   // HUONG-DAN-CAI-DAT.txt, README... ở thư mục gốc bản phát hành
        return true;
    }
    return (bool) preg_match('~^/(composer\.(json|lock)|web\.config|router\.php)$~i', $uri);
}

/** File tĩnh: chỉ các đuôi giao diện/tài nguyên công khai. Mọi đuôi khác -> 404. */
function rb_router_static_allowed($uri)
{
    return (bool) preg_match(
        '~\.(css|js|map|html?|txt|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|otf|mp3|m4a|ogg)$~i',
        $uri
    );
}

/**
 * Kiểm lại trên đường dẫn THẬT của file: hệ điều hành có thể quy nhiều cách viết về cùng một
 * file (hoa/thường, tên ngắn...). Nếu file thật nằm trong web root thì đường dẫn tương đối của
 * nó cũng phải qua được các luật trên. (Thư mục trỏ symlink ra ngoài root — vd uploads/ trên
 * Docker/gói Synology — đã được kiểm theo URL ở trên.)
 */
function rb_router_realpath_ok($file)
{
    $real = realpath($file);
    $root = realpath(__DIR__);
    if ($real === false || $root === false) {
        return false;
    }
    $real = str_replace('\\', '/', $real);
    $root = rtrim(str_replace('\\', '/', $root), '/');
    if (stripos($real, $root . '/') !== 0) {
        return true;
    }
    $rel = '/' . substr($real, strlen($root) + 1);
    if (rb_router_is_private($rel)) {
        return false;
    }
    return substr($rel, -11) === '/index.html' || rb_router_static_allowed($rel);
}
