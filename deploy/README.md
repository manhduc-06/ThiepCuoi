# Tự host với tên miền riêng

Nhánh `self-host` là bản **mã nguồn đã giải mã** (PHP + JS) để sửa được tính năng. Bản phát hành gốc
mã hóa toàn bộ code và tự kiểm hash; ở nhánh này lớp đó đã được bỏ. Lưu ý:

- Code giải mã **không còn comment** và **tên biến cục bộ là chuỗi ngẫu nhiên** (`$_vx43qgo`…). Tên class,
  hàm, thuộc tính, khóa mảng giữ nguyên. Nên đặt lại tên biến dần ở những chỗ bạn sửa.
- Repo gốc không có file LICENSE: giữ repo này **private**, hoặc xin phép tác giả trước khi công khai.
- `system/libraries/Profiler.php` đã sửa cú pháp cho PHP 8 (giống bản vá của CodeIgniter 3.1.13).

Có hai cách chạy. Cả hai đều **không dùng `jagame.vn`**: tên miền và tunnel nằm trong tài khoản của bạn.

---

## Cách 1: Máy cá nhân + Docker + Cloudflare Tunnel (khuyên dùng)

Ảnh nằm trên máy bạn, miễn phí, không cần mở cổng router hay IP tĩnh. Máy phải bật (và Docker đang chạy) thì
khách mới xem được. HTTPS do Cloudflare lo.

**Bước 1 — Đưa tên miền về Cloudflare** (gói Free): *Add a domain* → nhập `tenmien.com` → Cloudflare cho 2
nameserver → vào nơi mua tên miền đổi nameserver sang 2 cái đó → đợi Cloudflare báo *Active* (vài phút đến vài giờ).
Trong *SSL/TLS → Edge Certificates* bật **Always Use HTTPS**.

**Bước 2 — Tạo tunnel**: Cloudflare Zero Trust → *Networks → Tunnels → Create a tunnel* → loại **Cloudflared** →
đặt tên (vd `thiep-may-nha`) → ở màn cài đặt chọn **Docker**, chép **chuỗi token** (phần sau `--token`).
Không cần chạy lệnh Cloudflare đưa ra.

**Bước 3 — Trỏ tên miền vào tunnel** (*Public Hostname* / *Published application routes*):

| | Một cặp đôi | Nhiều cặp đôi |
|---|---|---|
| Subdomain | `thiep` (→ `thiep.tenmien.com`) | `*` (→ `*.tenmien.com`) |
| Service | `HTTP` · `caddy:80` | `HTTP` · `caddy:80` |

Nhiều cặp: nếu Cloudflare không tự tạo bản ghi DNS cho `*`, vào *DNS → Records* thêm `CNAME` tên `*`, nội dung
`<id-tunnel>.cfargotunnel.com`, bật đám mây cam (Proxied).

**Bước 4 — Chạy trên máy** (thư mục repo, nhánh `self-host`):

```bash
echo 'CLOUDFLARE_TUNNEL_TOKEN=<token ở bước 2>' > .env      # .env đã gitignore, đừng gửi file này cho ai

# Một cặp đôi
docker compose --profile tunnel up -d --build
docker compose exec -u www-data php bash deploy/vps/tao-trang.sh
#   -> mở https://thiep.tenmien.com/admin

# Hoặc nhiều cặp đôi
docker compose -f docker-compose.multi.yml --profile tunnel up -d --build
docker compose -f docker-compose.multi.yml exec -u www-data php bash deploy/multi/cap-doi.sh them lan-hung
#   -> mở https://lan-hung.tenmien.com/admin
```

Kiểm tra tunnel: `docker compose logs cloudflared` phải có dòng `Registered tunnel connection`; trong Zero Trust
tunnel hiện **Healthy**. Tắt: `docker compose --profile tunnel down` (dữ liệu vẫn giữ trong volume).

**Bước 5 — Giữ máy luôn sẵn sàng**
- Docker Desktop → *Settings → General* → bật **Start Docker Desktop when you sign in**. Các container có
  `restart: unless-stopped` nên tự chạy lại sau khi bật máy.
- macOS: *System Settings → Displays/Energy* → không cho máy ngủ khi cắm sạc (hoặc chạy `caffeinate -s` trong
  Terminal), gập màn hình MacBook sẽ ngủ trừ khi cắm màn hình ngoài.
- Sao lưu định kỳ: *Quản trị → Cài đặt → Sao lưu* (tải `.zip`).

Ghi chú kỹ thuật: cloudflared → Caddy → PHP. Caddy tin header của proxy trong mạng nội bộ
(`trusted_proxies private_ranges`) nên app thấy **IP thật của khách** (giới hạn số lần thử tính riêng từng người)
và biết khách vào bằng **https** (link, cookie Secure). Cổng 8080/8081 chỉ mở trên `127.0.0.1` của máy bạn.

### Cách 1b: không dùng Docker (run_mac.sh / run_window.bat)

1. Làm bước 1–2 ở trên; ở bước 3 đặt Service `HTTP` · `localhost:8686`.
2. `bash run_mac.sh` (Windows: `run_window.bat`). Lần đầu script tự tải `cloudflared`. Ở trang cài đặt lần đầu
   (`http://localhost:8686`) chọn **Chỉ trong mạng nhà** (không dùng link `jagame.vn` tự động).
3. *Quản trị → Gửi link & QR → Tùy chọn nâng cao* → **Tunnel của riêng bạn**, dán token và hostname → Lưu,
   rồi khởi động lại chương trình. Cổng phải khớp cổng in ra trong cửa sổ chương trình
   (cố định bằng `ANHCUOI_PORT=8686 bash run_mac.sh`). Cách này chỉ một cặp đôi.

## Cách 2: VPS (chạy 24/7)

Ví dụ trên Ubuntu 24.04 (PHP 8.3). VPS 1 vCPU / 1–2 GB RAM là đủ; ổ đĩa tùy lượng ảnh.

```bash
# 1. Gói cần thiết
sudo apt update
sudo apt install -y caddy php8.3-fpm php8.3-sqlite3 php8.3-gd php8.3-zip php8.3-curl php8.3-mbstring php8.3-intl git

# 2. Mã nguồn (repo private: dùng deploy key hoặc rsync từ máy bạn)
sudo git clone -b self-host git@github.com:<bạn>/ThiepCuoi.git /srv/thiepcuoi
sudo chown -R www-data:www-data /srv/thiepcuoi

# 3. PHP-FPM và Caddy
sudo cp /srv/thiepcuoi/deploy/vps/php-fpm-thiepcuoi.conf /etc/php/8.3/fpm/pool.d/thiepcuoi.conf
sudo systemctl restart php8.3-fpm
echo 'import /srv/thiepcuoi/deploy/vps/Caddyfile' | sudo tee /etc/caddy/Caddyfile
sudo systemctl edit caddy             # thêm 2 dòng: [Service]  và  Environment=SITE_ADDRESS=thiep.tenmien.com
sudo systemctl restart caddy

# 4. Cài đặt lần đầu (trang /setup bị chặn trên Internet)
cd /srv/thiepcuoi && sudo -u www-data bash deploy/vps/tao-trang.sh
```

5. **DNS**: tạo bản ghi `A` `thiep.tenmien.com` → IP của VPS. Nếu dùng Cloudflare, để **DNS only**
   (đám mây xám). Lý do: app chỉ tin `CF-Connecting-IP` khi request đến từ 127.0.0.1; qua proxy Cloudflare,
   mọi khách sẽ trông như cùng vài IP của Cloudflare và giới hạn số lần thử bị dồn chung.
6. Mở `https://thiep.tenmien.com/admin`. Caddy tự xin chứng chỉ HTTPS lần đầu.

Cấu hình VPS khác bản máy nhà ở các điểm:
- `Caddyfile` (định tuyến chung ở `deploy/caddy/routes.caddy`) chỉ phục vụ file tĩnh trong `/assets` và `/uploads` (đuôi giao diện), mọi thứ khác qua `index.php`.
- PHP-FPM tắt `exec`/`shell_exec`… (không cần tunnel) và giới hạn `open_basedir`.
- `tao-trang.sh` ghi `cloudflared/tunnel.json` ở chế độ `off`: app không gọi ra máy chủ ngoài nào.

## Nhiều cặp đôi, mỗi cặp một subdomain

Một máy chạy được nhiều trang cưới: `lan-hung.tenmien.com`, `minh-thao.tenmien.com`… Mã nguồn dùng chung,
mỗi cặp một thư mục `sites/<tên>/` với CSDL, ảnh, khóa bí mật, session và tài khoản quản trị riêng. Đăng nhập
ở cặp này không dùng được ở cặp khác; ảnh của cặp này không mở được qua subdomain của cặp khác.

**Thử trên máy (Docker)** — cổng 8081, chạy song song với bản một cặp ở 8080:

```bash
docker compose -f docker-compose.multi.yml up -d --build
docker compose -f docker-compose.multi.yml exec -u www-data php bash deploy/multi/cap-doi.sh them lan-hung
# mở http://lan-hung.localhost:8081/admin  (*.localhost tự trỏ về máy mình)
```

**Quản lý cặp đôi** (`deploy/multi/cap-doi.sh`):

| Lệnh | Việc |
|---|---|
| `them <tên> [--quota MB]` | Tạo cặp mới, hỏi tên cô dâu chú rể, ngày cưới, mật khẩu quản trị. `--quota` giới hạn dung lượng ảnh/nhạc của cặp đó. |
| `ds` | Liệt kê các cặp kèm tên và ngày cưới. |
| `xoa <tên>` | Gỡ trang (phải gõ lại tên để xác nhận). Dữ liệu được cất vào `sites/.da-xoa/`, khôi phục bằng `mv`. |

Tên cặp là subdomain: 2–40 ký tự, chữ thường không dấu, số, gạch ngang. Quên mật khẩu quản trị của một cặp:
`cd sites/<tên> && php index.php cli doi_mat_khau` (trong Docker: thêm `docker compose -f docker-compose.multi.yml exec -u www-data php` và `cd /sites/<tên>`).

**Trang quản trị nền tảng** (tạo cặp đôi bằng giao diện web thay cho dòng lệnh) ở tên miền gốc:
`http://localhost:8081` khi thử trên máy, `https://tenmien.com` khi chạy thật.

```bash
# Tạo tài khoản quản trị nền tảng (mật khẩu ≥ 12 ký tự; chạy lại lệnh này để đổi mật khẩu)
docker compose -f docker-compose.multi.yml exec -u www-data php php platform/index.php dat-mat-khau sieuadmin
```

Ở đó: tạo trang mới (subdomain, tên cô dâu chú rể, ngày cưới, dung lượng tối đa — mật khẩu quản trị của cặp
được tạo ngẫu nhiên, hiện **một lần**), xem danh sách (số ảnh, dung lượng), đặt lại mật khẩu quản trị của cặp,
tạm dừng / mở lại, gỡ trang. Mọi thao tác đi qua `deploy/multi/cap-doi.sh`, nên dòng lệnh và web cho cùng kết quả.
Trang mới chạy ngay vì DNS là wildcard `*.tenmien.com`.

Bảo vệ: đăng nhập riêng (giới hạn 10 lần sai/15 phút mỗi IP, 50 lần cho tất cả), CSRF + kiểm tra Origin,
cookie `SameSite=Strict`. Trang này tạo được tài khoản cho mọi cặp đôi, nên khi chạy thật **nên đặt thêm
Cloudflare Access** trước tên miền gốc: Zero Trust → *Access → Applications → Add → Self-hosted*, domain
`tenmien.com` (không có subdomain), policy *Allow* email của bạn. Khi đó phải qua mã đăng nhập gửi email trước
khi thấy form đăng nhập. Với Cloudflare Tunnel, thêm *Public Hostname* thứ hai: để trống subdomain (tên miền gốc)
→ `HTTP` · `caddy:80`.

Kiểm thử: `P_PASS='<mật khẩu nền tảng>' bash tests/platform_smoke.sh` (tạo một cặp thử rồi gỡ).

**Trên VPS:**

1. DNS: bản ghi `A` cho `tenmien.com` **và** `*.tenmien.com` trỏ về VPS (Cloudflare: để DNS only).
2. Cài gói, mã nguồn, PHP-FPM như mục VPS ở trên, rồi:
   ```bash
   sudo mkdir -p /srv/sites && sudo chown www-data:www-data /srv/sites
   sudo ln -s /srv/sites /sites        # Caddyfile và cap-doi.sh dùng /sites
   sudo sed -i 's~^php_admin_value\[open_basedir\].*~php_admin_value[open_basedir] = /srv/thiepcuoi/:/srv/sites/:/sites/:/tmp/~' \
        /etc/php/8.3/fpm/pool.d/thiepcuoi.conf && sudo systemctl restart php8.3-fpm
   echo 'import /srv/thiepcuoi/deploy/multi/Caddyfile' | sudo tee /etc/caddy/Caddyfile
   sudo cp /srv/thiepcuoi/deploy/multi/php-fpm-platform.conf /etc/php/8.3/fpm/pool.d/platform.conf
   sudo nano /etc/php/8.3/fpm/pool.d/platform.conf   # đổi env[BASE_DOMAIN] = tenmien.com
   sudo systemctl restart php8.3-fpm
   sudo systemctl edit caddy           # [Service]  Environment=BASE_DOMAIN=tenmien.com
   sudo systemctl restart caddy
   sudo -u www-data php /srv/thiepcuoi/platform/index.php dat-mat-khau sieuadmin   # trang quản trị nền tảng
   sudo -u www-data bash /srv/thiepcuoi/deploy/multi/cap-doi.sh them lan-hung
   ```
3. Mở `https://lan-hung.tenmien.com/admin`. Lần đầu mở mỗi subdomain mất vài giây để Caddy xin chứng chỉ HTTPS;
   Caddy chỉ xin cho subdomain có thư mục trong `/sites` (hỏi cổng nội bộ 5555), nên không bị lạm dụng.

Lưu ý: các cặp chạy chung một tiến trình PHP với cùng user, nên tách biệt ở mức ứng dụng (mỗi cặp một thư mục dữ liệu,
cookie theo subdomain) chứ không phải mức hệ điều hành. Phù hợp khi bạn tự quản lý các trang cho người quen; nếu
cho người lạ tự đăng ký thì nên tách mỗi cặp một container/user riêng.

## Chuyển dữ liệu từ máy nhà lên VPS

Dừng app ở máy nhà, rồi chép 3 thứ (giữ nguyên đường dẫn):

```bash
rsync -a database/ uploads/ vps:/srv/thiepcuoi/   # database/ gồm anhcuoi.db và .secret_key
ssh vps 'cd /srv/thiepcuoi && sudo chown -R www-data:www-data database uploads'
```

`.secret_key` cần chép theo để cookie thiệp mời đã gửi vẫn hợp lệ. Sau đó ghi `cloudflared/tunnel.json` ở chế độ
`off` như dòng cuối của `tao-trang.sh` (không chép `cloudflared/` từ máy nhà lên).

## Sao lưu

Toàn bộ dữ liệu nằm trong `database/` và `uploads/`. Quản trị → Cài đặt có nút tải bản sao lưu `.zip`.

## Sửa tính năng

- PHP: `application/` (controllers, models, views, libraries, helpers). Ngôn ngữ: `application/language/`.
- Giao diện: `assets/css/`, `assets/js/`. Sửa JS/CSS xong tải lại trang là thấy (URL có `?v=<mtime>`).
- Thay đổi cấu trúc CSDL: thêm file `application/sql/006_….sql`; `libraries/Schema.php` tự chạy file mới.
- Chạy thử trên máy: `bash run_mac.sh` rồi mở `http://localhost:8686`.
