print("spider.py 开始运行", flush=True)
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
LNCTF 全类型全栈爬虫 v2.0
=========================
从 JSON/GitHub/CTFd 爬取题目 → 下载附件 → 清洗入库

用法:
  python spider.py                  # 从本地 JSON 同步
  python spider.py --category Web   # 只同步 Web 类
  python spider.py --seed           # 生成种子模板后退出
"""

import os, re, json, hashlib, argparse, logging
from datetime import datetime
from urllib.parse import urlparse

import requests
import pymysql

try:
    import bcrypt
except ImportError:
    bcrypt = None

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s", datefmt="%H:%M:%S")
log = logging.getLogger("spider")

# ==================== 配置 ====================
BASE_DIR    = os.path.dirname(os.path.abspath(__file__))
DOWNLOAD_DIR = os.path.join(BASE_DIR, "public", "uploads")
CAT_DIRS = {
    "Web":     os.path.join(DOWNLOAD_DIR, "web"),
    "Pwn":     os.path.join(DOWNLOAD_DIR, "pwn"),
    "Reverse": os.path.join(DOWNLOAD_DIR, "reverse"),
    "Crypto":  os.path.join(DOWNLOAD_DIR, "crypto"),
    "Misc":    os.path.join(DOWNLOAD_DIR, "misc"),
}
DB = {"host": "127.0.0.1", "port": 3307, "user": "root", "password": "root", "database": "lnctf", "charset": "utf8mb4"}
SEED_PATH = os.path.join(BASE_DIR, "challenges_seed.json")


def ensure_bcrypt():
    if bcrypt is None:
        raise RuntimeError("缺少 bcrypt 依赖，无法生成与后端 password_verify() 兼容的 Flag 哈希。请先执行: pip install bcrypt --break-system-packages")


def hash_flag(flag: str) -> str:
    ensure_bcrypt()
    return bcrypt.hashpw(flag.encode(), bcrypt.gensalt()).decode()


def ensure_dirs():
    for p in CAT_DIRS.values():
        os.makedirs(p, exist_ok=True)
    log.info(f"附件目录: {DOWNLOAD_DIR}")


def safe_name(url, cat="misc"):
    parsed = urlparse(url)
    orig = os.path.basename(parsed.path) or hashlib.md5(url.encode()).hexdigest() + ".bin"
    safe = re.sub(r'[\\/:*?"<>|#%&{}~]', '_', orig)
    name, ext = os.path.splitext(safe)
    if len(name) > 60: name = name[:60]
    safe = name + ext
    d = CAT_DIRS.get(cat.capitalize(), DOWNLOAD_DIR)
    p = os.path.join(d, safe)
    if os.path.exists(p):
        p = os.path.join(d, f"{name}_{datetime.now():%Y%m%d_%H%M%S}{ext}")
    return p, f"/uploads/{cat.lower()}/{os.path.basename(p)}"


def download(url, cat="misc", retries=3):
    if not url: return None, None
    dest, web = safe_name(url, cat)
    for i in range(retries):
        try:
            r = requests.get(url, timeout=30, headers={"User-Agent": "Mozilla/5.0"}, stream=True)
            if r.status_code != 200: continue
            with open(dest, "wb") as f:
                for chunk in r.iter_content(8192):
                    if chunk: f.write(chunk)
            log.info(f"  ✓ {os.path.getsize(dest):,}B → {os.path.basename(dest)}")
            return dest, web
        except Exception as e:
            log.warning(f"  {e} 重试 {i+1}/{retries}")
    log.error(f"  ✗ 下载失败: {url}")
    return None, None


def infer_diff(title, desc, pts):
    t = (title + " " + desc).lower()
    if any(k in t for k in ["easy","签到","入门","base","simple","baby"]): return "easy"
    if any(k in t for k in ["hard","困难","obfuscated","rop"]): return "hard"
    if any(k in t for k in ["expert","master","终极"]): return "expert"
    pts = int(pts) if pts else 100
    if pts <= 50: return "easy"
    if pts <= 150: return "medium"
    if pts <= 300: return "hard"
    return "expert"


def parse_json(path):
    if not os.path.exists(path): log.warning(f"JSON 不存在: {path}"); return []
    with open(path, encoding="utf-8") as f: return json.load(f)


def clean_and_import(cursor, db, items):
    ok = skip = fail = 0
    for item in items:
        title = (item.get("title") or "").strip(); cat = (item.get("category") or "Misc").strip()
        pts = int(item.get("points", 100)); diff = item.get("difficulty") or infer_diff(title, item.get("desc",""), pts)
        desc = (item.get("desc") or "").strip(); flag = (item.get("flag") or "").strip()
        furl = item.get("file_url") or item.get("attachment_url")
        if not title or not flag: log.warning(f"  跳过: 标题或Flag为空"); skip += 1; continue
        cursor.execute("SELECT id FROM challenges WHERE title = %s", (title,))
        if cursor.fetchone(): log.info(f"  [!] 跳过（已存在）: {title}"); skip += 1; continue
        cursor.execute("SELECT id FROM categories WHERE name = %s", (cat,))
        r = cursor.fetchone()
        if r: cid = r[0]
        else: cursor.execute("INSERT INTO categories (name, sort_order) VALUES (%s, 99)", (cat,)); db.commit(); cid = cursor.lastrowid
        attach = None
        if furl: _, attach = download(furl, cat)
        hflag = hash_flag(flag)
        try:
            cursor.execute("""INSERT INTO challenges (title, category_id, difficulty, description, flag, score, attachment_url, is_active, created_by, created_at, updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,1,1,NOW(),NOW())""",
                           (title, cid, diff, desc, hflag, pts, attach))
            db.commit(); log.info(f"  [+] [{cat}] {title} ({diff}, {pts}pts)"); ok += 1
        except pymysql.Error as e: db.rollback(); log.error(f"  [-] 入库失败: {e}"); fail += 1
    return ok, skip, fail


def gen_seed():
    ensure_dirs()
    tmpl = [
        {"title": "HTTP 响应头分析", "category": "Web", "difficulty": "easy", "points": 50,
         "desc": "分析目标服务器 HTTP 响应头，找到 Flag。\n`curl -v http://challenge.lnctf.local/headers`", "file_url": None, "flag": "LNCTF{h4ck_th3_h34d3rs}"},
        {"title": "古典密码的艺术", "category": "Crypto", "difficulty": "easy", "points": 100,
         "desc": "密文：`oY_sh3_shp_wshco_b0_vun`\n凯撒密码位移 13 位。", "file_url": None, "flag": "LNCTF{ca3sar_c1ph3r_1s_c00l}"},
        {"title": "Exif 隐写", "category": "Misc", "difficulty": "medium", "points": 150,
         "desc": "Flag 藏在图片 Exif 元数据中。", "file_url": "https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba",
         "flag": "LNCTF{m1sc_st3g_cat_001}"},
        {"title": "Easy Reverse", "category": "Reverse", "difficulty": "medium", "points": 200,
         "desc": "逆向分析可执行程序的 main 函数。", "file_url": None, "flag": "LNCTF{re_v3rse_m3_plz}"},
        {"title": "栈溢出入门", "category": "Pwn", "difficulty": "hard", "points": 300,
         "desc": "`nc challenge.lnctf.local 31337`\n分析 ELF 文件构造 ROP 链。", "file_url": None, "flag": "LNCTF{r3t_2_t3xt_1s_n0t_h4rd}"},
    ]
    with open(SEED_PATH, "w", encoding="utf-8") as f: json.dump(tmpl, f, ensure_ascii=False, indent=2)
    log.info(f"种子模板已生成: {SEED_PATH}")


def start(args):
    log.info("="*50); log.info("LNCTF 爬虫 v2.0"); log.info(f"{datetime.now():%Y-%m-%d %H:%M:%S}"); log.info("="*50)
    ensure_bcrypt()
    ensure_dirs()
    try: db = pymysql.connect(**DB); cursor = db.cursor()
    except pymysql.Error as e: log.error(f"数据库连接失败: {e}"); return
    items = []
    if args.source in (None, "json"): items.extend(parse_json(SEED_PATH))
    from collections import Counter
    seen = Counter()
    items = [i for i in items if not seen[i.get("title")]]
    if args.category: items = [i for i in items if i.get("category","").lower() == args.category.lower()]
    if not items: log.warning("无数据，先用 --seed 生成模板"); cursor.close(); db.close(); return
    ok, skip, fail = clean_and_import(cursor, db, items)
    log.info(f"\n✅ {ok}  🚫 {skip}  ❌ {fail}  附件: {DOWNLOAD_DIR}")
    cursor.close(); db.close()

if __name__ == "__main__":
    p = argparse.ArgumentParser(description="LNCTF 爬虫")
    p.add_argument("--source", choices=["json"]); p.add_argument("--category"); p.add_argument("--seed", action="store_true")
    args = p.parse_args()
    if args.seed: gen_seed()
    else: start(args)
