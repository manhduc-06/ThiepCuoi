#!/bin/bash
# Cài đặt lần đầu trên VPS bằng dòng lệnh (trang /setup bị chặn ở Caddy).
# Tạo tài khoản quản trị + tên cô dâu chú rể, và tắt tunnel (VPS đã có tên miền + HTTPS qua Caddy).
#   sudo -u www-data bash deploy/vps/tao-trang.sh
#   SITE_DIR=/sites/lan-hung bash deploy/vps/tao-trang.sh   # một cặp trong chế độ nhiều cặp (cap-doi.sh gọi)
set -e
cd "${SITE_DIR:-$(dirname "$0")/../..}"
PHP="${PHP:-php}"

read -r -p "Tên đăng nhập quản trị [admin]: " USERNAME
USERNAME="${USERNAME:-admin}"
read -r -p "Tên chú rể: " GROOM
read -r -p "Tên cô dâu: " BRIDE
read -r -p "Ngày cưới (YYYY-MM-DD, bỏ trống nếu chưa có): " WDATE
read -r -s -p "Mật khẩu quản trị (ít nhất 8 ký tự): " PASS; echo
read -r -s -p "Nhập lại mật khẩu: " PASS2; echo
if [ "$PASS" != "$PASS2" ]; then echo "Hai lần nhập không khớp."; exit 1; fi
if [ "${#PASS}" -lt 8 ]; then echo "Mật khẩu phải có ít nhất 8 ký tự."; exit 1; fi

mkdir -p database/sessions uploads/photos uploads/media cloudflared
chmod 0700 database cloudflared

# Mật khẩu đi qua stdin, không lộ trong danh sách tiến trình.
HASH_B64="$(printf '%s' "$PASS" | "$PHP" -r 'echo base64_encode(password_hash(stream_get_contents(STDIN), PASSWORD_DEFAULT));')"
B64() { printf '%s' "$1" | "$PHP" -r 'echo base64_encode(stream_get_contents(STDIN));'; }

"$PHP" index.php cli tao_trang "$USERNAME" "$HASH_B64" "$(B64 "$GROOM")" "$(B64 "$BRIDE")" "${WDATE:--}"

# Tắt tunnel và tên miền tự động (jagame.vn): không có file này app mặc định bật chế độ tự động.
"$PHP" -r 'file_put_contents("cloudflared/tunnel.json", json_encode(array("mode"=>"off","token"=>"","hostname"=>"","auto"=>false), JSON_PRETTY_PRINT)); chmod("cloudflared/tunnel.json", 0600);'
echo "Xong. Đăng nhập tại https://<tên-miền>/admin"
