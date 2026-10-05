#!/bin/bash
# Quản lý nhiều cặp đôi trên một máy: mỗi cặp một thư mục SITES_DIR/<tên> (dữ liệu riêng: CSDL, ảnh, khóa bí
# mật, session) dùng chung mã nguồn ở CODE_DIR. Caddy (deploy/multi/Caddyfile) trỏ <tên>.tenmien.com vào đó.
#
#   bash deploy/multi/cap-doi.sh them lan-hung [--quota 5000]   # tạo cặp mới (hỏi tên, ngày, mật khẩu quản trị)
#   bash deploy/multi/cap-doi.sh ds                             # liệt kê các cặp
#   bash deploy/multi/cap-doi.sh xoa lan-hung                   # cất thư mục vào SITES_DIR/.da-xoa (khôi phục được)
#   bash deploy/multi/cap-doi.sh tam-dung lan-hung              # tạm tắt trang (subdomain trả 404), dữ liệu giữ nguyên
#   bash deploy/multi/cap-doi.sh mo-lai lan-hung                # bật lại trang đã tạm dừng
#   bash deploy/multi/cap-doi.sh doi-mat-khau lan-hung          # đặt lại mật khẩu quản trị của cặp (đọc từ stdin)
#
# Trong Docker:  docker compose -f docker-compose.multi.yml exec -u www-data php bash deploy/multi/cap-doi.sh ...
# Trên VPS:      sudo -u www-data SITES_DIR=/srv/sites bash /srv/thiepcuoi/deploy/multi/cap-doi.sh ...
set -e
CODE_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
SITES_DIR="${SITES_DIR:-/sites}"
PHP="${PHP:-php}"

# Subdomain dành riêng (không cấp cho cặp đôi).
RESERVED="www admin api app mail smtp imap pop ftp ns1 ns2 cdn static assets quantri platform root test demo"

die() { echo "$*" >&2; exit 1; }
valid_name() { [[ "$1" =~ ^[a-z0-9][a-z0-9-]{0,38}[a-z0-9]$ ]]; }

couple_title() {
	"$PHP" -r '
		$db = $argv[1] . "/database/anhcuoi.db";
		if (!is_file($db)) { echo "(chưa cài đặt)"; exit; }
		$d = new PDO("sqlite:" . $db);
		$g = $d->query("SELECT key, value FROM settings WHERE key IN (\"groom_name\",\"bride_name\",\"wedding_date\")")->fetchAll(PDO::FETCH_KEY_PAIR);
		echo trim(($g["groom_name"] ?? "") . " & " . ($g["bride_name"] ?? "")), ($g["wedding_date"] ?? "") !== "" ? "  ·  " . $g["wedding_date"] : "";
	' "$1"
}

cmd_them() {
	local name="$1"; shift || true
	local quota=""
	while [ $# -gt 0 ]; do
		case "$1" in
			--quota) quota="$2"; shift 2 ;;
			*) die "Tham số lạ: $1" ;;
		esac
	done
	valid_name "$name" || die "Tên '$name' không hợp lệ: 2–40 ký tự, chữ thường không dấu, số, gạch ngang (vd: lan-hung)."
	case " $RESERVED " in *" $name "*) die "Tên '$name' được dành riêng, hãy chọn tên khác." ;; esac
	local site="$SITES_DIR/$name"
	[ -e "$site" ] && die "Đã có cặp '$name' ($site)."
	[ -e "$SITES_DIR/.tam-dung/$name" ] && die "Đã có cặp '$name' đang tạm dừng."
	[ -w "$SITES_DIR" ] || die "Không ghi được vào $SITES_DIR (chạy bằng user www-data?)."

	mkdir -p "$site"/database/sessions "$site"/uploads/photos "$site"/uploads/media "$site"/cloudflared
	chmod 0700 "$site/database" "$site/cloudflared"
	# index.php riêng: FCPATH = thư mục của cặp (mọi dữ liệu tính theo FCPATH), mã nguồn trỏ về CODE_DIR.
	sed -e "s~^\t\$system_path = 'system';~\t\$system_path = '$CODE_DIR/system';~" \
	    -e "s~^\t\$application_folder = 'application';~\t\$application_folder = '$CODE_DIR/application';~" \
	    "$CODE_DIR/index.php" > "$site/index.php"
	grep -q "'$CODE_DIR/system'" "$site/index.php" && grep -q "'$CODE_DIR/application'" "$site/index.php" \
		|| { rm -rf "$site"; die "Không sửa được đường dẫn trong index.php (mã nguồn đã đổi?)."; }
	ln -s "$CODE_DIR/assets" "$site/assets"
	printf '{"mode":"off","token":"","hostname":"","auto":false}\n' > "$site/cloudflared/tunnel.json"
	chmod 0600 "$site/cloudflared/tunnel.json"
	if [ -n "$quota" ]; then
		[[ "$quota" =~ ^[0-9]+$ ]] || die "--quota phải là số MB."
		printf '{"mb":%d}\n' "$quota" > "$site/.quota"
	fi

	echo "== Cài đặt cặp '$name'"
	if ! SITE_DIR="$site" bash "$CODE_DIR/deploy/vps/tao-trang.sh"; then
		rm -rf "$site"
		die "Chưa tạo được, đã dọn thư mục $site."
	fi
	echo "Xong: $name  ->  https://$name.<tên-miền>/admin"
}

cmd_ds() {
	shopt -s nullglob
	local n=0
	for site in "$SITES_DIR"/*/; do
		local name; name="$(basename "$site")"
		valid_name "$name" || continue
		printf '%-24s %s\n' "$name" "$(couple_title "$site")"
		n=$((n+1))
	done
	[ "$n" -eq 0 ] && echo "Chưa có cặp nào. Tạo: bash deploy/multi/cap-doi.sh them <tên>"
	return 0
}

cmd_xoa() {
	local name="$1"
	valid_name "$name" || die "Tên không hợp lệ."
	local site="$SITES_DIR/$name"
	[ -d "$site" ] || die "Không có cặp '$name'."
	echo "Cặp '$name': $(couple_title "$site")"
	read -r -p "Gõ lại tên '$name' để xác nhận gỡ trang (dữ liệu được cất vào $SITES_DIR/.da-xoa): " confirm
	[ "$confirm" = "$name" ] || die "Không khớp, chưa xóa gì."
	mkdir -p "$SITES_DIR/.da-xoa"
	local dest; dest="$SITES_DIR/.da-xoa/$name-$(date +%Y%m%d-%H%M%S)"
	mv "$site" "$dest"
	echo "Đã gỡ. Dữ liệu ở $dest (khôi phục: mv \"$dest\" \"$site\")."
}

# Tạm dừng = chuyển thư mục sang SITES_DIR/.tam-dung (Caddy không tìm thấy -> 404, không xin chứng chỉ).
cmd_tam_dung() {
	local name="$1"
	valid_name "$name" || die "Tên không hợp lệ."
	[ -d "$SITES_DIR/$name" ] || die "Không có cặp '$name' đang chạy."
	mkdir -p "$SITES_DIR/.tam-dung"
	[ -e "$SITES_DIR/.tam-dung/$name" ] && die "Đã có bản tạm dừng của '$name'."
	mv "$SITES_DIR/$name" "$SITES_DIR/.tam-dung/$name"
	echo "Đã tạm dừng '$name'."
}

cmd_mo_lai() {
	local name="$1"
	valid_name "$name" || die "Tên không hợp lệ."
	[ -d "$SITES_DIR/.tam-dung/$name" ] || die "Không có cặp '$name' đang tạm dừng."
	[ -e "$SITES_DIR/$name" ] && die "Đã có cặp '$name' đang chạy."
	mv "$SITES_DIR/.tam-dung/$name" "$SITES_DIR/$name"
	echo "Đã mở lại '$name'."
}

# Mật khẩu mới đọc từ stdin (không lộ trong danh sách tiến trình). Đổi xong mọi phiên đăng nhập cũ bị đăng xuất.
cmd_doi_mat_khau() {
	local name="$1"
	valid_name "$name" || die "Tên không hợp lệ."
	local db="$SITES_DIR/$name/database/anhcuoi.db"
	[ -f "$db" ] || db="$SITES_DIR/.tam-dung/$name/database/anhcuoi.db"
	[ -f "$db" ] || die "Không có cặp '$name'."
	local pass
	if [ -t 0 ]; then read -r -s -p "Mật khẩu mới (ít nhất 8 ký tự): " pass; echo; else IFS= read -r pass; fi
	[ "${#pass}" -ge 8 ] || die "Mật khẩu phải có ít nhất 8 ký tự."
	printf '%s' "$pass" | "$PHP" -r '
		$d = new PDO("sqlite:" . $argv[1]);
		$h = password_hash(stream_get_contents(STDIN), PASSWORD_DEFAULT);
		$id = $d->query("SELECT id FROM users ORDER BY id LIMIT 1")->fetchColumn();
		if (!$id) { fwrite(STDERR, "Cặp này chưa có tài khoản.\n"); exit(1); }
		$st = $d->prepare("UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?");
		$st->execute(array($h, $id));
		echo "Đã đặt lại mật khẩu cho tài khoản \"", $d->query("SELECT username FROM users WHERE id = " . (int) $id)->fetchColumn(), "\".\n";
	' "$db"
}

case "${1:-}" in
	them) shift; [ -n "${1:-}" ] || die "Cách dùng: cap-doi.sh them <tên> [--quota MB]"; cmd_them "$@" ;;
	ds) cmd_ds ;;
	xoa) [ -n "${2:-}" ] || die "Cách dùng: cap-doi.sh xoa <tên>"; cmd_xoa "$2" ;;
	tam-dung) [ -n "${2:-}" ] || die "Cách dùng: cap-doi.sh tam-dung <tên>"; cmd_tam_dung "$2" ;;
	mo-lai) [ -n "${2:-}" ] || die "Cách dùng: cap-doi.sh mo-lai <tên>"; cmd_mo_lai "$2" ;;
	doi-mat-khau) [ -n "${2:-}" ] || die "Cách dùng: cap-doi.sh doi-mat-khau <tên>"; cmd_doi_mat_khau "$2" ;;
	*) sed -n '2,13p' "$0" | sed 's/^# \{0,1\}//'; exit 1 ;;
esac
