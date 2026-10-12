<?php

$name = "<script>alert('Hacked!')</script>";

echo bin2hex(random_bytes(32));
echo '<br />'.random_bytes(32);
?>