#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
github_seed.py
=============
作用：
从本地 GitHub 开源 CTF 题库目录中扫描题目，生成 LNCTF 可导入的 challenges_seed.json。

用法：
  E:\\conda3\\python.exe -u github_seed.py --repo data\\my-ctf-challenges --limit 200

然后再导入数据库：
  E:\\conda3\\python.exe -u spider.py
"""

import os
import re
import json
import hashlib
import argparse
from pathlib import Path


BASE_DIR = Path(__file__).resolve().parent
OUT_PATH = BASE_DIR / "challenges_seed.json"


CATEGORY_MAP = {
    "web": "Web",
    "pwn": "Pwn",
    "crypto": "Crypto",
    "cryptography": "Crypto",
    "misc": "Misc",
    "forensics": "Misc",
    "reverse": "Reverse",
    "reversing": "Reverse",
    "rev": "Reverse",
    "mobile": "Reverse",
    "blockchain": "Misc",
    "osint": "Misc",
}


def normalize_category(path_text: str) -> str:
    """
    根据路径判断题目分类。
    """
    low = path_text.lower().replace("\\", "/")
    parts = low.split("/")

    for key, value in CATEGORY_MAP.items():
        if key in parts or f"/{key}/" in low:
            return value

    return "Misc"


def clean_text(text: str, max_len: int = 700) -> str:
    """
    简单清洗 Markdown 内容。
    """
    text = re.sub(r"!\[.*?\]\(.*?\)", "", text)
    text = re.sub(r"\[([^\]]+)\]\([^)]+\)", r"\1", text)
    text = re.sub(r"`{3}[\s\S]*?`{3}", "", text)
    text = re.sub(r"<[^>]+>", "", text)
    text = re.sub(r"\s+", " ", text).strip()

    if len(text) > max_len:
        text = text[:max_len] + "..."

    return text


def read_desc(folder: Path) -> str:
    """
    读取题目描述文件。
    """
    candidates = [
        "README.md",
        "readme.md",
        "Readme.md",
        "description.md",
        "desc.md",
        "challenge.md",
        "problem.md",
    ]

    for name in candidates:
        path = folder / name
        if path.exists() and path.is_file():
            try:
                content = path.read_text(encoding="utf-8", errors="ignore")
                desc = clean_text(content)
                if desc:
                    return desc
            except Exception:
                pass

    return "该题目来自公开 CTF 题库仓库，请根据题目目录、附件或源码进行分析。"


def make_flag(seed: str) -> str:
    """
    生成占位 Flag。
    真实项目中后续可以手动替换。
    """
    h = hashlib.md5(seed.encode("utf-8")).hexdigest()[:10]
    return f"LNCTF{{change_me_{h}}}"


def make_title(folder: Path, repo_path: Path) -> str:
    """
    根据文件夹名生成题目标题。
    """
    try:
        rel = folder.relative_to(repo_path).as_posix()
    except Exception:
        rel = folder.name

    name = folder.name.replace("_", " ").replace("-", " ").strip()

    if not name:
        name = rel.replace("/", " - ")

    name = re.sub(r"\s+", " ", name).strip()

    if not name:
        name = "Unknown Challenge"

    return name[:80]


def looks_like_challenge(folder: Path) -> bool:
    """
    判断一个目录像不像 CTF 题目目录。
    """
    if not folder.is_dir():
        return False

    try:
        children = list(folder.iterdir())
    except Exception:
        return False

    names = [p.name.lower() for p in children]

    important_files = [
        "readme.md",
        "challenge.yml",
        "challenge.yaml",
        "dockerfile",
        "description.md",
        "desc.md",
        "problem.md",
    ]

    if any(name in names for name in important_files):
        return True

    useful_exts = {
        ".md",
        ".txt",
        ".py",
        ".php",
        ".js",
        ".html",
        ".zip",
        ".tar",
        ".gz",
        ".png",
        ".jpg",
        ".jpeg",
        ".pcap",
        ".pcapng",
        ".exe",
        ".elf",
        ".bin",
        ".so",
    }

    for p in children:
        if p.is_file() and p.suffix.lower() in useful_exts:
            return True

    return False


def make_demo_item(index: int) -> dict:
    """
    当真实扫描题目不足 limit 时，自动补演示题。
    这样你的平台可以先展示 200 道题。
    """
    cats = ["Web", "Crypto", "Misc", "Reverse", "Pwn"]
    cat = cats[(index - 1) % len(cats)]

    difficulty_map = {
        0: "easy",
        1: "medium",
        2: "hard",
    }

    difficulty = difficulty_map[index % 3]
    points = 50 + (index % 6) * 50

    title = f"{cat} Practice {index:03d}"

    desc = (
        f"这是自动补充的 {cat} 演示题，用于扩充 LNCTF 刷题平台题库。"
        f"后续可以将本题替换为真实题目描述、附件和 Flag。"
    )

    return {
        "title": title,
        "category": cat,
        "difficulty": difficulty,
        "points": points,
        "desc": desc,
        "file_url": None,
        "flag": make_flag(title),
    }


def scan_repo(repo_path: Path, limit: int, fill: bool = True) -> list:
    """
    扫描仓库目录，生成题目列表。
    """
    repo_path = repo_path.resolve()

    items = []
    seen_titles = set()
    seen_paths = set()

    scan_root = repo_path / "challenges"

    if not scan_root.exists():
        scan_root = repo_path

    for folder in scan_root.rglob("*"):
        if len(items) >= limit:
            break

        if ".git" in folder.parts:
            continue

        if not looks_like_challenge(folder):
            continue

        try:
            rel = folder.relative_to(repo_path).as_posix()
        except Exception:
            rel = folder.as_posix()

        if rel.lower() in seen_paths:
            continue

        seen_paths.add(rel.lower())

        base_title = make_title(folder, repo_path)

        # 关键：标题加编号，避免 spider.py 因为 title 重复而跳过
        title = f"{base_title} - {len(items) + 1:03d}"

        if title.lower() in seen_titles:
            continue

        seen_titles.add(title.lower())

        category = normalize_category(rel)
        desc = read_desc(folder)

        item = {
            "title": title,
            "category": category,
            "difficulty": "medium",
            "points": 100,
            "desc": f"{desc}\n\n来源目录: {rel}",
            "file_url": None,
            "flag": make_flag(title + rel),
        }

        items.append(item)

    # 如果真实扫描结果不足 limit，就自动补演示题
    if fill and len(items) < limit:
        start = len(items) + 1

        for i in range(start, limit + 1):
            item = make_demo_item(i)

            # 防止补充题与已有题重复
            while item["title"].lower() in seen_titles:
                i += 1
                item = make_demo_item(i)

            seen_titles.add(item["title"].lower())
            items.append(item)

            if len(items) >= limit:
                break

    return items[:limit]


def main():
    parser = argparse.ArgumentParser(
        description="从 GitHub 公开 CTF 题库生成 LNCTF challenges_seed.json"
    )

    parser.add_argument(
        "--repo",
        default="data/my-ctf-challenges",
        help="本地题库路径，例如 data/my-ctf-challenges",
    )

    parser.add_argument(
        "--limit",
        type=int,
        default=200,
        help="最多生成多少道题，默认 200",
    )

    parser.add_argument(
        "--no-fill",
        action="store_true",
        help="如果真实题目不足 limit，不自动补演示题",
    )

    args = parser.parse_args()

    repo_path = Path(args.repo)

    if not repo_path.exists():
        print(f"题库目录不存在: {repo_path}")
        print("请先下载题库，例如：")
        print(
            r"git clone --depth 1 https://gh-proxy.com/https://github.com/arkark/my-ctf-challenges.git data\my-ctf-challenges"
        )
        return

    items = scan_repo(
        repo_path=repo_path,
        limit=args.limit,
        fill=not args.no_fill,
    )

    with open(OUT_PATH, "w", encoding="utf-8") as f:
        json.dump(items, f, ensure_ascii=False, indent=2)

    real_count = 0
    demo_count = 0

    for item in items:
        if "Practice" in item["title"]:
            demo_count += 1
        else:
            real_count += 1

    print(f"已生成 {len(items)} 道题")
    print(f"真实扫描题: {real_count} 道")
    print(f"自动演示题: {demo_count} 道")
    print(f"输出文件: {OUT_PATH}")


if __name__ == "__main__":
    main()