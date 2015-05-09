<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <title>Modified Core Files Report by Amasty</title>
    <link rel="stylesheet" href="styles.css" type="text/css" charset="utf-8"/>
    <script>
        function hideNextDiv(el) {
            var yourUl = el.parentNode.childNodes[4];
            yourUl.style.display = yourUl.style.display === 'none' ? '' : 'none';
        }
    </script>
</head>
<body>

<h1 style="text-align: center">Modified Core Files Report </h1>


<form action="index.php" enctype="multipart/form-data" method="post">
    <input type="file" name="sources"/>
    <br/>
    <input type="submit" name="submit"/>
</form>

<?php

require_once dirname(__FILE__) . '/lib/Diff.php';
require_once dirname(__FILE__) . '/lib/Diff/Renderer/Html/SideBySide.php';
require_once dirname(__FILE__) . '/FileCompare.php';



if (isset($_POST['submit'])) {
    $fc = new File_Compare(array('php'));
    echo $fc->process($_FILES['sources']['tmp_name']);
}
?>

</body>
</html>