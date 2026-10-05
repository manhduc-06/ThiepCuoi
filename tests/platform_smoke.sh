#!/bin/bash
# Kiểm thử trang quản trị nền tảng (platform/index.php) trên docker-compose.multi.yml.
#   P_USER=sieuadmin P_PASS='...' bash tests/platform_smoke.sh
# Tạo một cặp thử tên "thu-<số>", đổi mật khẩu, tạm dừng, mở lại, rồi gỡ (cất vào sites/.da-xoa).
set -u
ROOT="${ROOT:-http://localhost:8081}"
P_USER="${P_USER:-sieuadmin}"
P_PASS="${P_PASS:?Đặt P_PASS là mật khẩu quản trị nền tảng}"
NAME="thu-$(date +%s)"
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
J="$TMP/p.jar"
PASSN=0; FAILN=0
ok()   { PASSN=$((PASSN+1)); printf '  \033[32mOK\033[0m   %s\n' "$1"; }
fail() { FAILN=$((FAILN+1)); printf '  \033[31mFAIL\033[0m %s\n' "$1"; }
code() { curl -s -o /dev/null -w '%{http_code}' "$@"; }
tok()  { curl -s -b "$J" -c "$J" "$ROOT/" | grep -oE 'name="csrf" value="[a-f0-9]+"' | head -1 | sed -E 's/.*value="([a-f0-9]+)"/\1/'; }
post() { curl -s -b "$J" -c "$J" -H "Origin: $ROOT" -o /dev/null -w '%{http_code}' --data-urlencode "csrf=$(tok)" "$@" "$ROOT/"; }
page() { curl -s -b "$J" -c "$J" "$ROOT/"; }
SITE="$(printf '%s' "$ROOT" | sed -E "s~://~://$NAME.~")"

echo "== Đăng nhập nền tảng"
T="$(tok)"
check_403="$(curl -s -b "$J" -c "$J" -H 'Origin: https://evil.example' -o /dev/null -w '%{http_code}' -d "csrf=$T&action=login&username=$P_USER&password=$P_PASS" "$ROOT/")"
[ "$check_403" = 403 ] && ok "Đăng nhập từ trang khác bị chặn" || fail "Đăng nhập từ trang khác: $check_403"
post -d action=login -d username="$P_USER" -d password=sai-mat-khau >/dev/null
page | grep -q 'Sai tên đăng nhập' && ok "Sai mật khẩu báo lỗi" || fail "Sai mật khẩu không báo lỗi"
post -d action=login -d username="$P_USER" --data-urlencode password="$P_PASS" >/dev/null
page | grep -q 'Tạo trang mới' && ok "Đăng nhập được" || { fail "Không đăng nhập được"; exit 1; }
for p in /index.php /admin.json /.platform/admin.json /x; do c="$(code "$ROOT$p")"; [ "$c" = 404 ] && ok "$p -> 404" || fail "$p -> $c"; done

echo "== Tạo cặp $NAME"
post -d action=create -d name=www -d groom=A -d bride=B >/dev/null
page | grep -q 'dành riêng' && ok "Tên dành riêng 'www' bị từ chối" || fail "Tên 'www' không bị từ chối"
post -d action=create -d name="$NAME" -d groom=Tùng -d bride=Vy -d date=2027-05-01 -d username=admin -d quota=300 >/dev/null
P="$(page)"
PW="$(echo "$P" | grep -oE 'Mật khẩu: +[A-Za-z0-9]+' | awk '{print $NF}')"
[ -n "$PW" ] && ok "Tạo xong, mật khẩu hiện một lần" || { fail "Tạo cặp lỗi: $(echo "$P" | grep -o 'class="flash[^<]*' | head -1)"; exit 1; }
page | grep -q "$PW" && fail "Mật khẩu vẫn hiện khi tải lại trang" || ok "Tải lại trang không còn hiện mật khẩu"
page | grep -q ">$NAME<" && ok "Cặp mới có trong danh sách" || fail "Cặp mới không có trong danh sách"
page | grep -q '300 MB' && ok "Hiện dung lượng tối đa 300 MB" || fail "Không thấy dung lượng tối đa"
post -d action=create -d name="$NAME" -d groom=X -d bride=Y >/dev/null
page | grep -q 'Đã có cặp' && ok "Tạo trùng tên bị từ chối" || fail "Tạo trùng tên không bị từ chối"

echo "== Trang của cặp mới"
CJ="$TMP/c.jar"; curl -s -c "$CJ" -o /dev/null "$SITE/admin/login"; CT="$(grep anhcuoi_csrf "$CJ" | awk '{print $7}')"
check="$(curl -s -b "$CJ" -c "$CJ" -H "Origin: $SITE" -o /dev/null -w '%{http_code}' -d "csrf_token=$CT&username=admin&password=$PW" "$SITE/admin/login")"
[ "$check" = 303 ] && ok "Cặp mới đăng nhập được bằng mật khẩu vừa tạo" || fail "Cặp mới đăng nhập: $check"
curl -s -b "$CJ" "$SITE/admin/settings" | grep -q 'value="Tùng"' && ok "Tên chú rể đúng" || fail "Tên chú rể sai"

echo "== Đặt lại mật khẩu"
post -d action=reset -d name="$NAME" >/dev/null
PW2="$(page | grep -oE 'Mật khẩu: +[A-Za-z0-9]+' | awk '{print $NF}')"
[ -n "$PW2" ] && [ "$PW2" != "$PW" ] && ok "Có mật khẩu mới" || fail "Không có mật khẩu mới"
check="$(code -b "$CJ" "$SITE/admin")"
[ "$check" != 200 ] && ok "Phiên đăng nhập cũ của cặp bị đăng xuất ($check)" || fail "Phiên cũ vẫn vào được"
CJ2="$TMP/c2.jar"; curl -s -c "$CJ2" -o /dev/null "$SITE/admin/login"; CT="$(grep anhcuoi_csrf "$CJ2" | awk '{print $7}')"
check="$(curl -s -b "$CJ2" -c "$CJ2" -H "Origin: $SITE" -o /dev/null -w '%{http_code}' -d "csrf_token=$CT&username=admin&password=$PW2" "$SITE/admin/login")"
[ "$check" = 303 ] && ok "Đăng nhập bằng mật khẩu mới" || fail "Mật khẩu mới không đăng nhập được: $check"

echo "== Tạm dừng / mở lại"
post -d action=pause -d name="$NAME" >/dev/null
check="$(code "$SITE/admin/login")"; [ "$check" = 404 ] && ok "Tạm dừng -> subdomain 404" || fail "Tạm dừng: $check"
page | grep -q 'tạm dừng' && ok "Danh sách hiện 'tạm dừng'" || fail "Danh sách không hiện tạm dừng"
post -d action=resume -d name="$NAME" >/dev/null
check="$(code "$SITE/admin/login")"; [ "$check" = 200 ] && ok "Mở lại -> trang chạy" || fail "Mở lại: $check"

echo "== Gỡ trang"
post -d action=delete -d name="$NAME" -d confirm=sai >/dev/null
check="$(code "$SITE/admin/login")"; [ "$check" = 200 ] && ok "Xác nhận sai thì không gỡ" || fail "Xác nhận sai vẫn gỡ"
post -d action=delete -d name="$NAME" -d confirm="$NAME" >/dev/null
check="$(code "$SITE/admin/login")"; [ "$check" = 404 ] && ok "Gỡ xong -> subdomain 404" || fail "Gỡ: $check"
page | grep -q ">$NAME<" && fail "Vẫn còn trong danh sách" || ok "Không còn trong danh sách"

echo "== Đăng xuất"
post -d action=logout >/dev/null
page | grep -q 'Tạo trang mới' && fail "Vẫn đăng nhập sau khi đăng xuất" || ok "Đăng xuất được"

echo; echo "Kết quả: $PASSN đạt, $FAILN lỗi"
[ "$FAILN" -eq 0 ]
