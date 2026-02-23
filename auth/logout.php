<?php
session_start();
session_destroy();
header('Location: login.php'); // atau 'login/' jika login di folder
exit();