#!/bin/bash
# Dừng tiến trình Ảnh Cưới CỦA THƯ MỤC CÀI ĐẶT NÀY còn treo (PHP ở cổng 8686–8688 + cloudflared của Ảnh Cưới).
# Chỉ tắt PHP có dòng lệnh chứa router.php VÀ thư mục làm việc là thư mục web của bản cài này — chương trình
# khác đang dùng cổng 8686 (hay một bản Ảnh Cưới khác) không bị đụng tới. Không xóa dữ liệu.
# Sau đó chạy lại: bash run_mac.sh
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
if [ -f "$SCRIPT_DIR/router.php" ]; then WEB_ROOT="$SCRIPT_DIR"; else WEB_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)/Web"; fi
WEB_REAL="$(cd "$WEB_ROOT" && pwd -P)"

# Thư mục làm việc của tiến trình (lsof trên macOS; /proc trên Linux).
proc_cwd() {
    if [ -r "/proc/$1/cwd" ]; then
        readlink "/proc/$1/cwd" 2>/dev/null
        return
    fi
    lsof -a -p "$1" -d cwd -Fn 2>/dev/null | sed -n 's/^n//p' | head -1
}

FOUND=""
for P in 8686 8687 8688; do
    for PID in $(lsof -nP -a -iTCP:$P -sTCP:LISTEN -t 2>/dev/null); do
        CMD="$(ps -o command= -p "$PID" 2>/dev/null)"
        case "$CMD" in
            *php*router.php*) ;;
            *) echo "Bỏ qua cổng $P (PID $PID): không phải Ảnh Cưới — $CMD"; continue ;;
        esac
        CWD="$(proc_cwd "$PID")"
        CWD_REAL="$( [ -n "$CWD" ] && cd "$CWD" 2>/dev/null && pwd -P)"
        if [ "$CWD_REAL" != "$WEB_REAL" ]; then
            echo "Bỏ qua cổng $P (PID $PID): Ảnh Cưới của thư mục khác ($CWD)."
            continue
        fi
        kill "$PID" 2>/dev/null && echo "Đã tắt Ảnh Cưới ở cổng $P (PID $PID)." && FOUND=1
    done
done
[ -z "$FOUND" ] && echo "Không có Ảnh Cưới nào của thư mục này đang chạy ở cổng 8686–8688."
# Chỉ tắt cloudflared của ĐÚNG bản cài này (mang --config <thư mục này>/cloudflared/anhcuoi-tunnel.yml).
pkill -f "$WEB_ROOT/cloudflared/anhcuoi-tunnel.yml" 2>/dev/null && echo "Đã tắt link công khai của Ảnh Cưới."
[ "$WEB_REAL" != "$WEB_ROOT" ] && pkill -f "$WEB_REAL/cloudflared/anhcuoi-tunnel.yml" 2>/dev/null
rm -f "$WEB_ROOT/database/.public_url" "$WEB_ROOT/cloudflared/web_tunnel.pid"
echo "Xong. Chạy lại: bash run_mac.sh"
