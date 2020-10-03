<?php

declare(strict_types=1);

/** @var Devanych\View\Renderer $this */

$this->layout('layouts/_sub');
$this->block('title', 'Page Title');
?>
<?php $this->beginBlock('menu');?>
<nav>Menu</nav>
<?php $this->endBlock();?>
<p>Content</p>
