<?php

$name = "<script>alert('Hacked!')</script>";

echo htmlspecialchars($name,ENT_QUOTES);

// echo bin2hex(random_bytes(32));
// echo '<br />'.random_bytes(32);
// echo '<br />'.time();
// echo '<br />'.date('Y-m-d H:i:s');

$text = "It's PHP";

$escaped = addslashes($text);
echo '<br />'.$escaped; 
// It\'s PHP

echo '<br />'.stripslashes($escaped);
// It's PHP
?>