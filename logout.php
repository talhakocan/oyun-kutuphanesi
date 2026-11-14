<?php
session_start();
session_destroy();
session_start();
session_regenerate_id(true);
$_SESSION['no_auto'] = 1; // çıkış yapınca otomatik demo girişi kapansın
header('Location: login.php');
