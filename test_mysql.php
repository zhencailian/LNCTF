<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', '', 3307);

if ($mysqli->connect_errno) {
    die('连接失败：' . $mysqli->connect_errno . ' - ' . $mysqli->connect_error);
}

echo 'MySQL 3307 连接成功';