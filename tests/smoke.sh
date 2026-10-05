#!/bin/bash
# Kiểm thử nhanh các luồng chính và các bản vá bảo mật trên một trang ĐÃ cài đặt (dữ liệu thử, không dùng trang thật).
#   BASE=http://localhost:8080 USER=admin PASS=testpass123 bash tests/smoke.sh
# Cần: curl, php (để tạo ảnh thử). Trang nên mới cài: script tạo album, khách, ảnh và đổi vài cài đặt.
set -u
BASE="${BASE:-http://localhost:8080}"
USER="${USER_NAME:-admin}"
PASS="${PASS:-testpass123}"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
ADM="$TMP/admin.jar"
GUEST="$TMP/guest.jar"
PASSN=0; FAILN=0
ok()   { PASSN=$((PASSN+1)); printf '  \033[32mOK\033[0m   %s\n' "$1"; }
fail() { FAILN=$((FAILN+1)); printf '  \033[31mFAIL\033[0m %s\n' "$1"; }
check() { if [ "$2" = "$3" ]; then ok "$1"; else fail "$1 (mong $3, nhận $2)"; fi; }

code() { curl -s -o /dev/null -w '%{http_code}' "$@"; }
csrf() { grep -E 'anhcuoi_csrf' "$1" | awk '{print $7}' | tail -1; }
HOSTHDR="$(printf '%s' "$BASE" | sed -E 's~^https?://~~; s~/.*$~~')"
ORIGIN="$BASE"
RUN="r$(date +%s)"   # hậu tố riêng mỗi lần chạy: chạy lại nhiều lần trên cùng dữ liệu vẫn đúng

echo "== Đăng nhập & CSRF (#1)"
curl -s -c "$ADM" -b "$ADM" -o /dev/null "$BASE/admin/login"
TOK="$(csrf "$ADM")"
check "POST đăng nhập từ trang khác bị chặn" \
  "$(code -c "$ADM" -b "$ADM" -H 'Origin: https://evil.jagame.vn' -d "csrf_token=$TOK&username=$USER&password=$PASS" "$BASE/admin/login")" 403
check "Đăng nhập cùng nguồn" \
  "$(code -c "$ADM" -b "$ADM" -H "Origin: $ORIGIN" -d "csrf_token=$TOK&username=$USER&password=$PASS" "$BASE/admin/login")" 303
check "Vào được trang quản trị" "$(code -b "$ADM" "$BASE/admin")" 200
HDRS="$(curl -s -D - -o /dev/null "$BASE/admin/login")"
echo "$HDRS" | grep -qi 'set-cookie: anhcuoi_csrf=.*samesite=lax' && ok "Cookie CSRF có SameSite=Lax" || fail "Cookie CSRF thiếu SameSite"
echo "$HDRS" | grep -qi 'set-cookie: anhcuoi_session=.*samesite=lax' && ok "Cookie session có SameSite=Lax" || fail "Cookie session thiếu SameSite"
check "csrf_token dạng mảng không gây lỗi 500" \
  "$(code -b "$ADM" -H "Origin: $ORIGIN" --data-urlencode 'csrf_token[]=x' "$BASE/admin/login")" 403
TOK="$(csrf "$ADM")"
post() { curl -s -c "$ADM" -b "$ADM" -H "Origin: $ORIGIN" -o /dev/null -w '%{http_code}' --data-urlencode "csrf_token=$TOK" "$@"; }

echo "== Cài đặt, mật khẩu trang (#5)"
SETTINGS=(-d groom_name=Hung -d bride_name=Lan -d guest_upload=1 -d rsvp_enabled=1 -d wishes_enabled=1)
curl -s -c "$ADM" -b "$ADM" -H "Origin: $ORIGIN" --data-urlencode "csrf_token=$TOK" "${SETTINGS[@]}" \
  -d site_password_mode=set -d site_password=1234 "$BASE/admin/settings" | grep -q 'tối thiểu 8 ký tự' && ok "Mật khẩu trang 4 ký tự bị từ chối" || fail "Mật khẩu trang 4 ký tự không bị từ chối"
post "${SETTINGS[@]}" -d site_password_mode=off "$BASE/admin/settings" >/dev/null

echo "== Album, ảnh gốc, GPS (#4, #10)"
php -r '
$i = imagecreatetruecolor(400, 300); imagefill($i, 0, 0, imagecolorallocate($i, 200, 120, 150)); imagejpeg($i, $argv[1] . "/raw.jpg", 90);
$r = file_get_contents($argv[1] . "/raw.jpg"); $p = "Exif\0\0GPSLatitude=21.0285;GPSLongitude=105.8542";
file_put_contents($argv[1] . "/gps.jpg", substr($r, 0, 2) . "\xFF\xE1" . pack("n", strlen($p) + 2) . $p . substr($r, 2));' "$TMP"
post -d title=PublicAlbum$RUN -d visibility=public -d allow_guest_upload=1 "$BASE/admin/albums/create" >/dev/null
post -d title=SecretAlbum$RUN -d visibility=password -d password=abc "$BASE/admin/albums/create" >/dev/null
curl -s -b "$ADM" "$BASE/admin/albums" | grep -q "SecretAlbum$RUN" && fail "Album mật khẩu 3 ký tự vẫn được tạo" || ok "Mật khẩu album 3 ký tự bị từ chối"
post -d title=SecretAlbum$RUN -d visibility=password -d password=matkhau123 -d allow_guest_upload=1 "$BASE/admin/albums/create" >/dev/null
ALBUMS="$(curl -s -b "$ADM" "$BASE/admin/albums")"
PUB_ID="$(echo "$ALBUMS" | grep -oE 'admin/albums/view/[0-9]+[^<]*' | grep -m1 "PublicAlbum$RUN" | grep -oE '[0-9]+' | head -1)"
SEC_ID="$(echo "$ALBUMS" | grep -oE 'admin/albums/view/[0-9]+[^<]*' | grep -m1 "SecretAlbum$RUN" | grep -oE '[0-9]+' | head -1)"
[ -n "$PUB_ID" ] && [ -n "$SEC_ID" ] && ok "Tạo album công khai #$PUB_ID và album mật khẩu #$SEC_ID" || { fail "Không tìm thấy id album (PUB=$PUB_ID SEC=$SEC_ID)"; }
up() { curl -s -b "$ADM" -H "Origin: $ORIGIN" -F "csrf_token=$TOK" -F "album_id=$1" -F "photo=@$TMP/gps.jpg;type=image/jpeg" "$BASE/admin/photos/upload"; }
R1="$(up "$PUB_ID")"; R2="$(up "$SEC_ID")"
KEY1="$(echo "$R1" | grep -oE '[0-9a-f]{32}' | head -1)"; KEY2="$(echo "$R2" | grep -oE '[0-9a-f]{32}' | head -1)"
[ -n "$KEY1" ] && [ -n "$KEY2" ] && ok "Tải ảnh lên 2 album" || fail "Tải ảnh lên lỗi: $R1 $R2"
P1="uploads/photos/${KEY1:0:2}/${KEY1}"
check "Ảnh thu nhỏ vẫn phục vụ tĩnh" "$(code "$BASE/${P1}_t.jpg")" 200
check "Đường tĩnh tới bản gốc _o bị chặn" "$(code "$BASE/${P1}_o.jpg")" 404
check "Chủ nhà tải được bản gốc qua /anh-goc" "$(code -b "$ADM" "$BASE/anh-goc/${KEY1}.jpg")" 200
curl -s -b "$ADM" "$BASE/anh-goc/${KEY1}.jpg" -o "$TMP/orig.jpg"
grep -q GPSLatitude "$TMP/orig.jpg" && fail "Bản gốc vẫn còn GPS" || ok "Bản gốc đã bỏ EXIF/GPS"
curl -s -b "$ADM" -H "Origin: $ORIGIN" -o /dev/null --data-urlencode "csrf_token=$TOK" "$BASE/admin/content/publish"
check "Khách xem được trang cưới sau khi xuất bản" "$(code -c "$GUEST" -b "$GUEST" "$BASE/")" 200
check "Khách KHÔNG tải được bản gốc khi tắt 'cho tải'" "$(code -b "$GUEST" "$BASE/anh-goc/${KEY1}.jpg")" 404
post "${SETTINGS[@]}" -d site_password_mode=off -d album_download=1 "$BASE/admin/settings" >/dev/null
check "Khách tải được bản gốc album công khai khi bật 'cho tải'" "$(code -b "$GUEST" "$BASE/anh-goc/${KEY1}.jpg")" 200
check "Khách KHÔNG tải được bản gốc album mật khẩu chưa mở khóa" "$(code -b "$GUEST" "$BASE/anh-goc/${KEY2}.jpg")" 404
check "Khóa sai định dạng -> 404" "$(code -b "$GUEST" "$BASE/anh-goc/../../database.jpg")" 404

echo "== Tên cô dâu chú rể ăn theo Cài đặt"
post -d groom_name=Minh -d bride_name=Thao -d guest_upload=1 -d rsvp_enabled=1 -d wishes_enabled=1 -d site_password_mode=off -d album_download=1 "$BASE/admin/settings" >/dev/null
PAGE="$(curl -s -b "$GUEST" "$BASE/")"
echo "$PAGE" | grep -q '<title>Minh &amp; Thao' && ok "Tiêu đề trang khách đổi ngay theo Cài đặt" || fail "Tiêu đề trang khách chưa đổi: $(echo "$PAGE" | grep -o '<title>[^<]*')"
echo "$PAGE" | grep -q '>Hung<\|>Lan<' && fail "Thân trang còn tên cũ" || ok "Thân trang không còn tên cũ"
curl -s -b "$GUEST" "$BASE/gui-anh" | grep -q 'Minh &amp; Thao' && ok "Trang gửi ảnh dùng tên mới" || fail "Trang gửi ảnh chưa dùng tên mới"

echo "== Không còn giao diện/mẫu thiệp VIP"
for pg in /admin/settings /admin/guests "/?"; do
  curl -s -b "$ADM" "$BASE$pg" | grep -qiE 'VIP|có trên thiep\.site' && fail "$pg còn VIP" || ok "$pg không còn VIP"
done

echo "== Link thiệp có hậu tố ngẫu nhiên (#6)"
post -d name=Lan -d salutation=Cô -d side=bride "$BASE/admin/guests/create" >/dev/null
SLUG="$(curl -s -b "$ADM" "$BASE/admin/guests" | grep -oE "${HOSTHDR}/[a-z0-9-]*lan[a-z0-9-]*" | head -1 | sed 's~.*/~~')"
[[ "$SLUG" =~ -[a-z2-9]{4}$ ]] && ok "Slug thiệp: $SLUG" || fail "Slug không có hậu tố ngẫu nhiên: '$SLUG'"
[ -n "$SLUG" ] && check "Mở được thiệp theo slug" "$(code -b "$GUEST" "$BASE/$SLUG")" 200
check "Slug đoán theo tên không mở được" "$(code -b "$GUEST" "$BASE/co-lan")" 404

echo "== Khách gửi ảnh (#3, #9)"
GT="$(curl -s -c "$GUEST" -b "$GUEST" -o /dev/null "$BASE/gui-anh"; csrf "$GUEST")"
GR="$(curl -s -b "$GUEST" -c "$GUEST" -H "Origin: $ORIGIN" -F "csrf_token=$GT" -F "photo=@$TMP/raw.jpg;type=image/jpeg" "$BASE/gui-anh/upload")"
echo "$GR" | grep -q '"ok":true' && ok "Khách gửi ảnh thành công" || fail "Khách gửi ảnh lỗi: $GR"
GR2="$(curl -s -b "$GUEST" -c "$GUEST" -H "Origin: $ORIGIN" -F "csrf_token=$GT" -F "photo=@$TMP/raw.jpg;type=image/jpeg" "$BASE/gui-anh/upload?album=secretalbum$RUN")"
echo "$GR2" | grep -q '"pending":true' && ok "Ảnh khách vào album mật khẩu phải chờ duyệt" || fail "Ảnh khách vào album mật khẩu không phải chờ duyệt: $GR2"

echo "== Giới hạn đoán mật khẩu đăng nhập"
J="$TMP/bf.jar"; curl -s -c "$J" -o /dev/null "$BASE/admin/login"; BT="$(csrf "$J")"
for i in $(seq 1 11); do curl -s -b "$J" -c "$J" -H "Origin: $ORIGIN" -o "$TMP/bf.html" -d "csrf_token=$BT&username=$USER&password=sai$i" "$BASE/admin/login"; done
grep -qiE 'thử lại sau|quá nhiều|nhiều lần' "$TMP/bf.html" && ok "Bị chặn sau 10 lần sai" || fail "Không thấy thông báo chặn sau 11 lần sai"

echo "== Đường dẫn riêng tư"
for p in /database/anhcuoi.db /database/.secret_key /cloudflared/tunnel.json /application/config/config.php /system/core/CodeIgniter.php /.git/config /setup; do
  c="$(code "$BASE$p")"; [ "$c" = 404 ] || [ "$c" = 403 ] || [ "$c" = 307 ] && ok "$p -> $c" || fail "$p -> $c"
done

echo
echo "Kết quả: $PASSN đạt, $FAILN lỗi"
[ "$FAILN" -eq 0 ]
