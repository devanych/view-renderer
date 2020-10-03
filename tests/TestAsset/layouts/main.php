<?php

declare(strict_types=1);

/** @var Devanych\View\Renderer $this */

?>
<html>
<head>
    <title><?=$this->renderBlock('title');?></title>
</head>
<body>
    <?=$this->render('layouts/_header.php')?>
    <?=$this->renderBlock('menu');?>
    <?=$this->renderBlock('content');?>
    <?=$this->render('layouts/_footer.php')?>
</body>
</html>
