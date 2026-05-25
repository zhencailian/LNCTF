-- ============================================================
-- LNCTF 公告系统 - 数据库迁移
-- 创建 announcements 表并插入示例数据
-- ============================================================

CREATE TABLE IF NOT EXISTS `announcements` (
    `id`           INT(11)       NOT NULL AUTO_INCREMENT,
    `title`        VARCHAR(255)  NOT NULL COMMENT '公告标题',
    `content`      TEXT          NOT NULL COMMENT '公告内容（完整 Markdown/HTML）',
    `is_important` TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '是否重要公告（1=置顶）',
    `created_by`   INT(11)       DEFAULT NULL COMMENT '发布人用户 ID',
    `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_important_created` (`is_important`, `created_at` DESC),
    KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告表';

-- 示例数据
INSERT INTO `announcements` (`title`, `content`, `is_important`, `created_at`) VALUES
('🚀 LNCTF 平台正式上线',
 '欢迎来到 LNCTF 训练平台！\n\n这是一个面向网络安全爱好者的 CTF 在线训练平台，提供 Web 渗透、逆向工程、密码学、PWN 二进制漏洞利用、MISC 杂项等多种类型的挑战题目。\n\n**平台特色：**\n- 分类清晰的挑战题库，涵盖主流 CTF 方向\n- 实时积分排行榜，支持个人与队伍排名\n- WriteUp 提交与分享功能\n- 完善的用户个人资料与解题记录\n\n祝大家玩得开心，不断提升技术水平！',
 1, '2026-05-25 10:00:00'),

('📢 每周五晚 8 点直播讲题',
 '从本周起，我们将在每周五晚 8 点通过 B 站直播间进行题目讲解与互动。\n\n每期会选取上周最热门或难度最高的 2-3 道题进行详细分析，包括解题思路、工具使用、常见坑点等。\n\n直播间地址将在公告区更新，敬请关注！',
 1, '2026-05-24 18:30:00'),

('🛠️ 平台维护通知（已完成）',
 '平台已于 2026 年 5 月 23 日凌晨完成例行维护，主要内容包括：\n- 后端 API 性能优化\n- 修复部分题目提交状态显示异常的问题\n- 新增题目搜索与分类筛选功能\n- 优化排行榜加载速度\n\n如您在使用过程中遇到任何问题，请通过反馈渠道联系我们。',
 0, '2026-05-23 02:00:00'),

('🏆 五月月赛即将开始',
 'LNCTF 五月内部月赛定于 2026 年 5 月 30 日（周六）10:00 - 18:00 举行。\n\n**赛事信息：**\n- 比赛形式：个人赛 / 队伍赛（4 人以内）\n- 题目数量：12 道（Web x4, Crypto x2, Reverse x2, PWN x2, MISC x2）\n- 计分规则：动态计分（根据解题人数衰减）\n- 奖品：前三名可获得 LNCTF 定制周边\n\n报名截止时间：5 月 28 日 23:59，请提前完成组队！',
 0, '2026-05-22 14:00:00'),

('📖 WriteUp 提交功能已上线',
 '现在你可以在解出题目后提交 WriteUp 了！\n\n提交位置：进入题目详情页 → "WriteUp" 标签 → 点击 "提交 WriteUp"\n\nWriteUp 支持 Markdown 格式，可以插入代码块、图片等。优秀的 WriteUp 将会被精选展示在题解区。\n\n注意：请勿抄袭他人题解，一经发现将取消 WriteUp 展示资格。',
 0, '2026-05-20 09:00:00'),

('🔧 关于题目提交的一些注意事项',
 '近期收到部分用户反馈题目提交失败的问题，经排查主要有以下几个原因：\n\n1. Flag 格式区分大小写，请确认复制时没有多余空格\n2. 平台 Flag 统一格式为 `flag{...}`，部分题目可能使用 `LNCTF{...}`\n3. 同一 IP 短时间内频繁提交会被限流（30 秒内最多 10 次）\n4. 如果确认 Flag 正确但仍提示错误，请联系管理员\n\n我们将持续优化提交体验。',
 0, '2026-05-18 16:00:00');
