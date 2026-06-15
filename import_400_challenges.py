import pymysql
import bcrypt

conn = pymysql.connect(
    host="127.0.0.1",
    port=3306,
    user="root",
    password="123456",
    database="lnctf",
    charset="utf8mb4"
)

categories = ["Web", "Pwn", "Reverse", "Crypto", "Misc"]

with conn.cursor() as cur:
    cur.execute("SELECT id FROM users WHERE username='admin' LIMIT 1")
    admin_id = cur.fetchone()[0]

    for cat in categories:
        cur.execute("SELECT id FROM categories WHERE name=%s", (cat,))
        category_id = cur.fetchone()[0]

        for i in range(1, 81):
            title = f"{cat} 入门题 {i:02d}"
            flag_plain = f"LNCTF{{{cat.lower()}_{i:02d}_flag}}"
            flag_hash = bcrypt.hashpw(flag_plain.encode(), bcrypt.gensalt()).decode()

            desc = f"""这是从公开 CTF 题库思路整理导入的 {cat} 类型训练题第 {i:02d} 题。

题目目标：学习 {cat} 方向的基础解题思路。
Flag 格式：LNCTF{{...}}

测试用 flag：{flag_plain}
"""

            cur.execute("""
                INSERT INTO challenges
                (title, description, category_id, difficulty, score, flag, flag_hint,
                 attachment_url, source_code_url, is_active, is_dockerized, created_by)
                VALUES
                (%s, %s, %s, %s, %s, %s, %s, NULL, NULL, 1, 0, %s)
            """, (
                title,
                desc,
                category_id,
                "easy",
                100,
                flag_hash,
                "LNCTF{...}",
                admin_id
            ))

conn.commit()
conn.close()

print("导入完成：Web/Pwn/Reverse/Crypto/Misc 每类 80 题，共 400 题")
