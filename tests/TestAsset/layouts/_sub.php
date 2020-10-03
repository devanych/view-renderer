<?php

declare(strict_types=1);

/** @var Devanych\View\Renderer $this */

$this->layout('layouts/main');
?>
<main>Sub<?=$this->renderBlock('content');?></main>
