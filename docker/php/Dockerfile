# PHP-FPM cho thiệp cưới. Ảnh chính thức đã có sẵn sqlite3, pdo_sqlite, mbstring, curl, fileinfo, posix;
# cài thêm gd (jpeg/webp/png), exif (xoay ảnh theo EXIF), zip (sao lưu, tải album), intl (Normalizer).
FROM php:8.3-fpm-bookworm

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions gd exif zip intl

# Pool riêng: cùng giới hạn với bản VPS, nhưng nghe TCP 9000 cho container Caddy.
RUN rm -f /usr/local/etc/php-fpm.d/*.conf
COPY docker/php/thiepcuoi-pool.conf /usr/local/etc/php-fpm.d/thiepcuoi.conf
COPY docker/php/platform-pool.conf /usr/local/etc/php-fpm.d/platform.conf
COPY docker/php/entrypoint.sh /usr/local/bin/thiepcuoi-entrypoint
RUN chmod +x /usr/local/bin/thiepcuoi-entrypoint

# Mã nguồn được mount từ máy (docker-compose.yml) để sửa code là thấy ngay, không cần build lại.
WORKDIR /srv/thiepcuoi
ENTRYPOINT ["thiepcuoi-entrypoint"]
CMD ["php-fpm", "--nodaemonize"]
