#!/bin/sh
# Thư mục dữ liệu là volume Docker (lần đầu thuộc root): tạo cấu trúc và trao quyền cho www-data.
set -e

# Nhiều cặp đôi (docker-compose.multi.yml): mã nguồn chỉ đọc, dữ liệu từng cặp nằm trong /sites/<tên>.
if [ "${THIEPCUOI_MULTI:-}" = "1" ]; then
	mkdir -p /sites
	chown www-data:www-data /sites /srv/thiepcuoi/application/logs
	exec "$@"
fi

cd /srv/thiepcuoi
mkdir -p database/sessions uploads/photos uploads/media cloudflared application/logs
chown -R www-data:www-data database uploads cloudflared
# application/logs nằm trong mã nguồn mount từ máy: đổi chủ có thể không được phép, khi đó mở quyền ghi.
chown www-data:www-data application/logs 2>/dev/null || chmod 0777 application/logs 2>/dev/null || true
chmod 0700 database cloudflared

# Không dùng tunnel trong Docker (Caddy phục vụ trực tiếp): ghi chế độ off nếu chưa có cấu hình,
# vì thiếu file này app mặc định bật tên miền tự động jagame.vn.
if [ ! -f cloudflared/tunnel.json ]; then
	printf '{"mode":"off","token":"","hostname":"","auto":false}\n' > cloudflared/tunnel.json
	chown www-data:www-data cloudflared/tunnel.json
	chmod 0600 cloudflared/tunnel.json
fi

exec "$@"
