#!/bin/bash
# Dừng mọi tiến trình Ảnh Cưới còn treo (PHP ở cổng 8686–8688 + cloudflared của Ảnh Cưới).
# Không xóa dữ liệu. Sau đó chạy lại: bash run_mac.sh
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
if [ -f "$SCRIPT_DIR/router.php" ]; then WEB_ROOT="$SCRIPT_DIR"; else WEB_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)/Web"; fi

for P in 8686 8687 8688; do
    PID=$(lsof -ti tcp:$P -sTCP:LISTEN 2>/dev/null)
    if [ -n "$PID" ]; then
        kill $PID 2>/dev/null && echo "Đã tắt tiến trình cổng $P (PID $(echo $PID))."
    fi
done
# Chỉ tắt cloudflared của Ảnh Cưới (mang --config anhcuoi-tunnel.yml), không đụng tunnel khác của máy.
pkill -f 'cloudflared.*anhcuoi-tunnel.yml' 2>/dev/null && echo "Đã tắt link công khai của Ảnh Cưới."
rm -f "$WEB_ROOT/database/.public_url"
echo "Xong. Chạy lại: bash run_mac.sh"
