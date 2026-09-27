"""Ambil tangkapan skrin kalkulator sebenar bayarfidyahonline.com (paparan telefon), satu gambar bagi setiap ketikan.
Hasil: assets/fid/ui/s00.jpg ... s09.jpg (7 hari x RM4.00 x 3 = RM84.00).
Perlu: pip install playwright, Chromium, dan domain bayarfidyahonline.com dibenarkan dalam tetapan rangkaian."""
import os, glob, subprocess, base64, hashlib
from playwright.sync_api import sync_playwright
from PIL import Image

LIB = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
UI = os.path.join(LIB, 'assets', 'fid', 'ui'); os.makedirs(UI, exist_ok=True)
EXE = next(iter(glob.glob('/opt/pw-browsers/chromium*/chrome-linux/chrome') + ['/opt/pw-browsers/chromium']))

args = []
CA = '/root/.ccr/agent-proxy-ca.crt'   # dalam Claude Code (cloud): percayai CA proksi sahaja, bukan matikan semakan TLS
if os.path.exists(CA):
    der = subprocess.run(f"openssl x509 -in {CA} -pubkey -noout | openssl pkey -pubin -outform der", shell=True, capture_output=True).stdout
    args.append('--ignore-certificate-errors-spki-list=' + base64.b64encode(hashlib.sha256(der).digest()).decode())

with sync_playwright() as p:
    b = p.chromium.launch(executable_path=EXE if os.path.exists(EXE) else None, args=args)
    pg = b.new_page(viewport={'width': 390, 'height': 844}, device_scale_factor=3, is_mobile=True, has_touch=True)
    pg.goto('https://www.bayarfidyahonline.com/', wait_until='networkidle', timeout=60000)
    # sembunyikan butang WhatsApp dan notifikasi terapung
    pg.add_style_tag(content='a[href*="wa.me"], a[href*="whatsapp"], [class*="fixed"]:not(header):not(header *){display:none !important}')
    pg.get_by_text('Kalkulator Fidyah', exact=True).first.scroll_into_view_if_needed()
    pg.evaluate("window.scrollBy(0, -40)")
    pg.wait_for_timeout(600)
    n = 0
    def snap():
        global n
        pg.wait_for_timeout(250)
        path = os.path.join(UI, f's{n:02d}.png'); pg.screenshot(path=path)
        Image.open(path).convert('RGB').save(path[:-4] + '.jpg', quality=90); os.remove(path)
        n += 1
    snap()
    pg.locator('select').first.select_option('lewat_qada'); snap()
    for _ in range(6):
        pg.get_by_label('Tambah Bilangan hari ditinggalkan').click(); snap()
    for _ in range(2):
        pg.get_by_label('Tambah Gandaan tahun qada').click(); snap()
    b.close()
print('siap:', n, 'gambar di', UI)
