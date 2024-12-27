<?php
    $hashed_password = password_hash('123456', PASSWORD_BCRYPT);
    echo "$hashed_password";
?>