#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import json
import hashlib
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent
OUT_PATH = BASE_DIR / "challenges_seed.json"

CATEGORIES = {
    "Pwn": [
        "栈溢出入门", "ret2text 基础", "格式化字符串漏洞", "整数溢出分析", "堆漏洞初探",
        "ROP 链构造", "Canary 绕过", "NX 保护绕过", "ret2libc 入门", "Shellcode 编写"
    ],
    "Reverse": [
        "简单 CrackMe", "字符串逆向分析", "ELF 逆向入门", "算法还原", "控制流分析",
        "Python 字节码分析", "Go 程序逆向", "UPX 脱壳", "注册码校验", "反调试分析"
    ],
    "Crypto": [
        "凯撒密码", "维吉尼亚密码", "RSA 入门", "Base 编码综合", "异或加密",
        "哈希碰撞", "低指数 RSA", "模运算基础", "古典密码综合", "AES 模式识别"
    ],
    "Misc": [
        "图片隐写", "压缩包分析", "流量包分析", "音频隐写", "二维码修复",
        "文件头修复", "Exif 信息提取", "LSB 隐写", "日志分析", "内存取证"
    ]
}

DIFFICULTIES = ["easy", "medium", "hard"]

def make_flag(seed: str) -> str:
    h = hashlib.md5(seed.encode("utf-8")).hexdigest()[:10]
    return f"LNCTF{{{h}}}"

def make_desc(category: str, title: str, index: int) -> str:
    if category == "Pwn":
        return f"这是一道 Pwn 方向练习题：{title}。请分析程序漏洞点，构造合适的输入或利用脚本获取 Flag。"
    if category == "Reverse":
        return f"这是一道 Reverse 方向练习题：{title}。请逆向分析程序逻辑，还原关键算法并得到 Flag。"
    if category == "Crypto":
        return f"这是一道 Crypto 方向练习题：{title}。请分析加密方式、密文特征或数学关系，解出 Flag。"
    if category == "Misc":
        return f"这是一道 Misc 方向练习题：{title}。请从文件、图片、流量、压缩包或元数据中寻找隐藏信息。"
    return "CTF 练习题。"

def main():
    items = []
    per_category = 50

    for category, names in CATEGORIES.items():
        for i in range(1, per_category + 1):
            base = names[(i - 1) % len(names)]
            title = f"{category} - {base} - {i:03d}"
            difficulty = DIFFICULTIES[i % len(DIFFICULTIES)]
            points = 50 + (i % 6) * 50

            item = {
                "title": title,
                "category": category,
                "difficulty": difficulty,
                "points": points,
                "desc": make_desc(category, title, i),
                "file_url": None,
                "flag": make_flag(title)
            }

            items.append(item)

    with open(OUT_PATH, "w", encoding="utf-8") as f:
        json.dump(items, f, ensure_ascii=False, indent=2)

    print(f"已生成 {len(items)} 道题")
    print("分类统计：")
    for category in CATEGORIES:
        print(f"{category}: {per_category} 道")
    print(f"输出文件: {OUT_PATH}")

if __name__ == "__main__":
    main()
