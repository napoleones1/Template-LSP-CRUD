<?php
session_start();
session_unset();
session_destroy();
header("Location: /pelatihan/sipeka/login.php");
exit;
