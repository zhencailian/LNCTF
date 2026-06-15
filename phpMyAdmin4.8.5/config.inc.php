<?php
$cfg['blowfish_secret'] = 'lnctf_phpmyadmin_3306_secret_1234567890';

$i = 0;
$i++;

$cfg['Servers'][$i]['verbose'] = 'MySQL 3306';
$cfg['Servers'][$i]['auth_type'] = 'cookie';
$cfg['Servers'][$i]['host'] = '127.0.0.1';
$cfg['Servers'][$i]['port'] = '3306';
$cfg['Servers'][$i]['connect_type'] = 'tcp';
$cfg['Servers'][$i]['extension'] = 'mysqli';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = false;