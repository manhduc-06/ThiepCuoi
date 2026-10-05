<?php

defined('BASEPATH') OR exit('No direct script access allowed');





if (!function_exists('lunar_jd')) {
function lunar_jd($_vacv8k0, $_v7s71iu, $_vy9ocg0)
{
$_vzza1q8 = intdiv(14 - $_v7s71iu, 12);
$_v5tg8ys = $_vy9ocg0 + 4800 - $_vzza1q8;
$_vfaooz0 = $_v7s71iu + 12 * $_vzza1q8 - 3;
$_vvdf31c = $_vacv8k0 + intdiv(153 * $_vfaooz0 + 2, 5) + 365 * $_v5tg8ys + intdiv($_v5tg8ys, 4) - intdiv($_v5tg8ys, 100) + intdiv($_v5tg8ys, 400) - 32045;
if ($_vvdf31c < 2299161) {
$_vvdf31c = $_vacv8k0 + intdiv(153 * $_vfaooz0 + 2, 5) + 365 * $_v5tg8ys + intdiv($_v5tg8ys, 4) - 32083;
}
return $_vvdf31c;
}
function lunar_new_moon($_v8nqdha)
{
$_vmhe61f = $_v8nqdha / 1236.85;
$_vt4pzds = $_vmhe61f * $_vmhe61f;
$_v63t0q9 = $_vt4pzds * $_vmhe61f;
$_v05t2j1 = M_PI / 180;
$_vlfx6xe = 2415020.75933 + 29.53058868 * $_v8nqdha + 0.0001178 * $_vt4pzds - 0.000000155 * $_v63t0q9;
$_vlfx6xe += 0.00033 * sin((166.56 + 132.87 * $_vmhe61f - 0.009173 * $_vt4pzds) * $_v05t2j1);
$_vbljxdq = 359.2242 + 29.10535608 * $_v8nqdha - 0.0000333 * $_vt4pzds - 0.00000347 * $_v63t0q9;
$_v5kpijt = 306.0253 + 385.81691806 * $_v8nqdha + 0.0107306 * $_vt4pzds + 0.00001236 * $_v63t0q9;
$_vpv07yc = 21.2964 + 390.67050646 * $_v8nqdha - 0.0016528 * $_vt4pzds - 0.00000239 * $_v63t0q9;
$_vlu3yfr = (0.1734 - 0.000393 * $_vmhe61f) * sin($_vbljxdq * $_v05t2j1) + 0.0021 * sin(2 * $_v05t2j1 * $_vbljxdq);
$_vlu3yfr = $_vlu3yfr - 0.4068 * sin($_v5kpijt * $_v05t2j1) + 0.0161 * sin($_v05t2j1 * 2 * $_v5kpijt);
$_vlu3yfr = $_vlu3yfr - 0.0004 * sin($_v05t2j1 * 3 * $_v5kpijt);
$_vlu3yfr = $_vlu3yfr + 0.0104 * sin($_v05t2j1 * 2 * $_vpv07yc) - 0.0051 * sin($_v05t2j1 * ($_vbljxdq + $_v5kpijt));
$_vlu3yfr = $_vlu3yfr - 0.0074 * sin($_v05t2j1 * ($_vbljxdq - $_v5kpijt)) + 0.0004 * sin($_v05t2j1 * (2 * $_vpv07yc + $_vbljxdq));
$_vlu3yfr = $_vlu3yfr - 0.0004 * sin($_v05t2j1 * (2 * $_vpv07yc - $_vbljxdq)) - 0.0006 * sin($_v05t2j1 * (2 * $_vpv07yc + $_v5kpijt));
$_vlu3yfr = $_vlu3yfr + 0.0010 * sin($_v05t2j1 * (2 * $_vpv07yc - $_v5kpijt)) + 0.0005 * sin($_v05t2j1 * (2 * $_v5kpijt + $_vbljxdq));
$_vxb1hfq = $_vmhe61f < -11 ? 0.001 + 0.000839 * $_vmhe61f + 0.0002261 * $_vt4pzds - 0.00000845 * $_v63t0q9 - 0.000000081 * $_vmhe61f * $_v63t0q9
: -0.000278 + 0.000265 * $_vmhe61f + 0.000262 * $_vt4pzds;
return $_vlfx6xe + $_vlu3yfr - $_vxb1hfq;
}
function lunar_new_moon_day($_v6pokao, $_vwl3jjp = 7)
{
return (int) floor(lunar_new_moon($_v6pokao) + 0.5 + $_vwl3jjp / 24);
}
function lunar_sun_longitude($_vof4011, $_vp7mow8 = 7)
{
$_vxvcyip = ($_vof4011 - 2451545.5 - $_vp7mow8 / 24) / 36525;
$_v3hghpx = $_vxvcyip * $_vxvcyip;
$_v5m21a1 = M_PI / 180;
$_va0enr2 = 357.52910 + 35999.05030 * $_vxvcyip - 0.0001559 * $_v3hghpx - 0.00000048 * $_vxvcyip * $_v3hghpx;
$_vdtae3y = 280.46645 + 36000.76983 * $_vxvcyip + 0.0003032 * $_v3hghpx;
$_vrjq48f = (1.914600 - 0.004817 * $_vxvcyip - 0.000014 * $_v3hghpx) * sin($_v5m21a1 * $_va0enr2);
$_vrjq48f += (0.019993 - 0.000101 * $_vxvcyip) * sin($_v5m21a1 * 2 * $_va0enr2) + 0.000290 * sin($_v5m21a1 * 3 * $_va0enr2);
$_vbfk3qj = ($_vdtae3y + $_vrjq48f) * $_v5m21a1;
$_vbfk3qj = $_vbfk3qj - M_PI * 2 * floor($_vbfk3qj / (M_PI * 2));
return (int) floor($_vbfk3qj / M_PI * 6);
}
function lunar_month11($_v5z7cyx, $_v3qrzl0 = 7)
{
$_vb2hshj = lunar_jd(31, 12, $_v5z7cyx) - 2415021;
$_v2xf5jk = (int) floor($_vb2hshj / 29.530588853);
$_vol81tm = lunar_new_moon_day($_v2xf5jk, $_v3qrzl0);
if (lunar_sun_longitude($_vol81tm, $_v3qrzl0) >= 9) {
$_vol81tm = lunar_new_moon_day($_v2xf5jk - 1, $_v3qrzl0);
}
return $_vol81tm;
}
function lunar_leap_offset($_v0h8w4e, $_vmn3vbl = 7)
{
$_vn0vx5x = (int) floor(($_v0h8w4e - 2415021.076998695) / 29.530588853 + 0.5);
$_vrwk5zm = 1;
$_vq3v6vu = lunar_sun_longitude(lunar_new_moon_day($_vn0vx5x + $_vrwk5zm, $_vmn3vbl), $_vmn3vbl);
do {
$_vbs56wm = $_vq3v6vu;
$_vrwk5zm++;
$_vq3v6vu = lunar_sun_longitude(lunar_new_moon_day($_vn0vx5x + $_vrwk5zm, $_vmn3vbl), $_vmn3vbl);
} while ($_vq3v6vu != $_vbs56wm && $_vrwk5zm < 14);
return $_vrwk5zm - 1;
}

function lunar_from_solar($_vd9c68j, $_v2lzfu9, $_vhfj2if, $_vpnsvvn = 7)
{
$_v5ol8k1 = lunar_jd($_vd9c68j, $_v2lzfu9, $_vhfj2if);
$_vyqns95 = (int) floor(($_v5ol8k1 - 2415021.076998695) / 29.530588853);
$_vz0ndii = lunar_new_moon_day($_vyqns95 + 1, $_vpnsvvn);
if ($_vz0ndii > $_v5ol8k1) {
$_vz0ndii = lunar_new_moon_day($_vyqns95, $_vpnsvvn);
}
$_vuzzti4 = lunar_month11($_vhfj2if, $_vpnsvvn);
$_vafyfyi = $_vuzzti4;
if ($_vuzzti4 >= $_vz0ndii) {
$_vzgsy9x = $_vhfj2if;
$_vuzzti4 = lunar_month11($_vhfj2if - 1, $_vpnsvvn);
} else {
$_vzgsy9x = $_vhfj2if + 1;
$_vafyfyi = lunar_month11($_vhfj2if + 1, $_vpnsvvn);
}
$_vs6b14h = $_v5ol8k1 - $_vz0ndii + 1;
$_vjmk4sf = (int) floor(($_vz0ndii - $_vuzzti4) / 29);
$_vu9gzu3 = FALSE;
$_vw2szmw = $_vjmk4sf + 11;
if ($_vafyfyi - $_vuzzti4 > 365) {
$_v4xflhh = lunar_leap_offset($_vuzzti4, $_vpnsvvn);
if ($_vjmk4sf >= $_v4xflhh) {
$_vw2szmw = $_vjmk4sf + 10;
if ($_vjmk4sf == $_v4xflhh) {
$_vu9gzu3 = TRUE;
}
}
}
if ($_vw2szmw > 12) {
$_vw2szmw -= 12;
}
if ($_vw2szmw >= 11 && $_vjmk4sf < 4) {
$_vzgsy9x -= 1;
}
return array((int) $_vs6b14h, (int) $_vw2szmw, (int) $_vzgsy9x, $_vu9gzu3);
}

function vn_lunar_text($_vijbxf9)
{
if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $_vijbxf9, $_v8wle3i)) {
return '';
}
list($_vsyii5f, $_v9i511t, $_vtcmzoz, $_vyg2c28) = lunar_from_solar((int) $_v8wle3i[3], (int) $_v8wle3i[2], (int) $_v8wle3i[1]);
if (lang_cur() === 'en') {

$_vpubiu4 = function ($_vdv8hk9) {
$_vjptbgh = ($_vdv8hk9 % 100 >= 11 && $_vdv8hk9 % 100 <= 13) ? 'th' : (array(1 => 'st', 2 => 'nd', 3 => 'rd')[$_vdv8hk9 % 10] ?? 'th');
return $_vdv8hk9 . $_vjptbgh;
};
return 'Lunar date: ' . $_vpubiu4($_vsyii5f) . ' day of the ' . ($_vyg2c28 ? 'leap ' : '') . $_vpubiu4($_v9i511t) . ' month';
}
if (lang_cur() !== 'vi') {

return __($_vyg2c28 ? 'Âm lịch: ngày {d} tháng {m} nhuận' : 'Âm lịch: ngày {d} tháng {m}', array('d' => $_vsyii5f, 'm' => $_v9i511t));
}
$_vv2s86z = array('Canh', 'Tân', 'Nhâm', 'Quý', 'Giáp', 'Ất', 'Bính', 'Đinh', 'Mậu', 'Kỷ');
$_v7bm7iw = array('Thân', 'Dậu', 'Tuất', 'Hợi', 'Tý', 'Sửu', 'Dần', 'Mão', 'Thìn', 'Tỵ', 'Ngọ', 'Mùi');
return 'Tức ngày ' . $_vsyii5f . ' tháng ' . $_v9i511t . ($_vyg2c28 ? ' nhuận' : '') . ' năm ' . $_vv2s86z[$_vtcmzoz % 10] . ' ' . $_v7bm7iw[$_vtcmzoz % 12] . ' (Âm lịch)';
}
}