import pymysql
import bcrypt
import random

conn = pymysql.connect(
    host="127.0.0.1",
    port=3306,
    user="root",
    password="123456",
    database="lnctf",
    charset="utf8mb4"
)

category_counts = {
    "Web": random.randint(55, 95),
    "Pwn": random.randint(35, 75),
    "Reverse": random.randint(45, 85),
    "Crypto": random.randint(50, 90),
    "Misc": random.randint(45, 85),
}

difficulties = [
    ("easy", 100, 0.35),
    ("medium", 200, 0.30),
    ("hard", 350, 0.25),
    ("expert", 500, 0.10),
]

def pick_difficulty():
    r = random.random()
    total = 0
    for diff, score, weight in difficulties:
        total += weight
        if r <= total:
            return diff, score
    return "easy", 100

with conn.cursor() as cur:
    cur.execute("SELECT id FROM users WHERE username='admin' LIMIT 1")
    admin_id = cur.fetchone()[0]

    # 删除之前批量生成的占位题
    cur.execute("DELETE FROM challenges WHERE title LIKE '%入门题%' OR title LIKE '%训练题%'")

    for cat, count in category_counts.items():
        cur.execute("SELECT id FROM categories WHERE name=%s LIMIT 1", (cat,))
        row = cur.fetchone()
        if not row:
            print(f"分类不存在：{cat}")
            continue

        category_id = row[0]

        for i in range(1, count + 1):
            difficulty, score = pick_difficulty()

            title = f"{cat} 随机训练题 {i:03d}"
            flag_plain = f"LNCTF{{{cat.lower()}_{difficulty}_{i:03d}}}"
            flag_hash = bcrypt.hashpw(flag_plain.encode(), bcrypt.gensalt()).decode()

            desc = f"""这是一道 {cat} 类型的 {difficulty} 难度训练题。

题目方向：{cat}
题目难度：{difficulty}
题目分值：{score}

Flag 格式：LNCTF{{...}}

测试 flag：{flag_plain}
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
                difficulty,
                score,
                flag_hash,
                "LNCTF{...}",
                admin_id
            ))

conn.commit()
conn.close()

print("随机导入完成：")
for k, v in category_counts.items():
    print(f"{k}: {v} 题")
print("刷新 LNCTF 页面查看结果")
