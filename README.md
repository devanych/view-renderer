# Simple View Renderer

[![License](https://poser.pugx.org/devanych/view-renderer/license)](https://packagist.org/packages/devanych/view-renderer)
[![Latest Stable Version](https://poser.pugx.org/devanych/view-renderer/v)](https://packagist.org/packages/devanych/view-renderer)
[![Total Downloads](https://poser.pugx.org/devanych/view-renderer/downloads)](https://packagist.org/packages/devanych/view-renderer)
[![GitHub Build Status](https://github.com/devanych/view-renderer/workflows/build/badge.svg)](https://github.com/devanych/view-renderer/actions)
[![GitHub Static Analysis Status](https://github.com/devanych/view-renderer/workflows/static/badge.svg)](https://github.com/devanych/view-renderer/actions)
[![Scrutinizer Code Coverage](https://scrutinizer-ci.com/g/devanych/view-renderer/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/devanych/view-renderer/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/devanych/view-renderer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/devanych/view-renderer/?branch=master)

A small and easy-to-use PHP library designed for rendering native PHP views.

Highlights:

* Convenient flat block system.
* Setting global parameters for all views.
* Support for using existing PHP functions.
* Easy extensibility by adding custom functions.
* Flexible layout functionality with unlimited nesting.

A guide with a detailed description in Russian language is [available here](https://devanych.ru/development/rendering-nativnyh-php-predstavlenij).

## Installation

This package requires PHP version 7.4 or later.

```
composer require devanych/view-renderer
```

## Usage

The simplest example of rendering:

```php
use Devanych\View\Renderer;

$renderer = new Renderer('/path/to/root/directory/of/views');

$content = $renderer->render('path/to/view/file', [
    'variableName' => 'the value of a variable of any type',
]);

echo $content;
```

The `render()` method takes as its first argument the path to the view file, which must be relative to the directory passed to the constructor when the object was created. The second argument passes an array of parameters (`name => value`) that will be converted to variables inside the view.

```php
$renderer->render('post/show', [
    'post' => $posts->get($id),
]);
```

in the view:

```php
<h1><?=$post->getName();?></h1>
```

The view file can be with or without an extension, if the file extension is not specified, `.php` will be substituted by default. The default extension can be changed by passing it to the constructor as the second argument.

```php
$renderer = new Renderer('/path/to/root/directory/of/views', 'tpl');
```

Within layouts and views, a renderer instance is available at `$this`.

```php
<title><?=$this->renderBlock('title');?></title>
```

### Blocks

The `block()`, `beginBlock()`, and `endBlock()` methods allow you to create content blocks in your view. The content of blocks is saved for use anywhere in the view, as well as in any parent layout. The method `renderBlock()` renders the stored content of the block.

Layout code:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?=$this->renderBlock('title');?></title>
    <?=$this->renderBlock('meta');?>
</head>
<body class="app">
    <?=$this->renderBlock('content');?>
</body>
</html>
```

View code:

```php
<?php

declare(strict_types=1);

/** @var Devanych\View\Renderer $this */

$this->layout('layouts/main');
$this->block('title', 'Page Title');
?>

<p>Page Content</p>

<?php $this->beginBlock('meta');?>
    <meta name="description" content="Page Description">
<?php $this->endBlock();?>
```

Rendering result:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page Title</title>
    <meta name="description" content="Page Description">
</head>
<body class="app">
    <p>Page Content</p>
</body>
</html>
```

Blocks can't be nested, but you can render one block inside another.

```php
<!-- Allowed -->
<?php $this->beginBlock('parent');?>
    <!--  ...  -->
    <?=$this->renderBlock('children');?>
    <!--  ...  -->
<?php $this->endBlock();?>

<!-- Disallowed -->
<?php $this->beginBlock('parent');?>
    <!--  ...  -->
    <?php $this->beginBlock('children');?>
        <!--  ...  -->
    <?php $this->endBlock();?>
    <!--  ...  -->
<?php $this->endBlock();?>
```

Note that `content` is a reserved block name. it renders the content of the view and child layouts. If you try to create a block named `content`, the `\RuntimeException`exception is thrown.

When calling the `renderBlock()` method with the name of a nonexistent block, the `renderBlock()` method returns an empty string, so no additional methods are needed to check the existence of the block. The second argument in `renderBlock()` can pass the default value, which will be substituted if the block with the specified name does not exist.

```php
<!-- Output the block content, if it exists  -->
<?php if ($name = $this->renderBlock('name')): ?>
    <h1><?=$name;?></h1>
<?php endif;?>

<!-- Output the default content if the block doesn't exist  -->
<?=$this->renderBlock('title', 'Default Title');?>
<!-- or using a different block  -->
<?=$this->renderBlock('title', $this->renderBlock('default-title'));?>
```

### Layouts

Layouts are a special type of representations that are very convenient to use for rendering repetitive parts (header, footer, sidebar, etc.). This package allows you to build layout chains with unlimited nesting. Layout inheritance is set in the file of the view or child layout by the `layout()` method.

Parent layout code (`layouts/main`):

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?=$this->renderBlock('title');?></title>
</head>
<body class="app">
    <?=$this->renderBlock('content');?>
</body>
</html>
```

Child layout code (`layouts/columns`):

```php
<?php

declare(strict_types=1);

/** @var Devanych\View\Renderer $this */

$this->layout('layouts/main');
?>
<main class="main">
    <?=$this->renderBlock('content');?>
</main>
<aside class="sidebar">
    <!-- Sidebar Code  -->
</aside>
```

View code (`site/page`):

```php
<?php

declare(strict_types=1);

/** @var Devanych\View\Renderer $this */

$this->layout('layouts/columns');
$this->block('title', 'Page Title');
?>

<p>Page Content</p>
```

Rendering the view:

```php
$renderer->render('site/page', [/* params */]);
```

Rendering result:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page Title</title>
</head>
<body class="app">
    <main class="main">
        <p>Page Content</p>
    </main>
    <aside class="sidebar">
        <!-- Sidebar Code  -->
    </aside>
</body>
</html>
```

If you just need to render the child layout file from the parent layout, you can use the `render()` method.

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?=$this->renderBlock('title');?></title>
</head>
<body class="app">
    <?=$this->render('layouts/_header.php')?>
		
    <?=$this->renderBlock('content');?>
		
    <?=$this->render('layouts/_footer.php', [
        'parameter' => 'value'
    ])?>
</body>
</html>
```

Rendering result:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page Title</title>
</head>
<body class="app">
    <header>Header</header>
    <p>Page Content</p>
    <footer>Footer</footer>
</body>
</html>
```

### Extensions

The extension functionality allows you to add your own functions and use them inside layouts and views. To add a function, you need to create your own class that implements the [Devanych\View\Extension\ExtensionInterface](https://github.com/devanych/view-renderer/blob/master/src/Extension/ExtensionInterface.php) interface. The interface contains only one method that returns an array whose keys are the names of added functions, and the values can be any possible variants of the `callable` type.

```php
interface ExtensionInterface
{
    /**
     * Returns an array of functions as `function name` => `function callback`.
     *
     * @return array<string, callable>
     */
    public function getFunctions(): array;
}
```

This package contains a single extension [Devanych\View\Extension\AssetExtension](https://github.com/devanych/view-renderer/blob/master/src/Extension/AssetExtension.php), which simplifies the connection of assets (scripts, styles, fonts, etc.) and adds a timestamp to the file. To add an extension, use the `addExtension()` method.

```php
$extension = new AssetExtension('/path/to/assets', 'https://examle.com/assets', true);
$renderer->addExtension($extension);

// Result: 'https://examle.com/assets/css/style.css?v=1601742615'
$renderer->asset('css/style.css');
```

### Global parameters

Using the `addGlobal()` method, you can add global parameters that will be available in all views and layouts.

```php
// The `$variableName` variable will now be available inside files.
$renderer->addGlobal( 'variableName', 'the value of a variable of any type');
```

Adding a variable with the same name again will cause a `\RuntimeException`, but you can change the value of the global variable for a specific view by passing a parameter with the same name when rendering the view, or simply assigning it in the view itself.

```php
// For all views and layouts, the value is `$author` will equal `Boby`
$renderer->addGlobal( 'author', 'Boby');

// Only for the `blog/page` view, the value is `$author` will be equal to `John`
$renderer->render('blog/page', [
    'author' => 'John',
]);
// or assign a value in the view
$author = 'John';
```

### Escaping

To prevent the output of potentially dangerous HTML code, the renderer contains the `esc()` method, which escapes special characters, converts them to the corresponding HTML entities.

```php
// Result: '&lt;script&gt;alert(123);&lt;/script&gt;'
$renderer->esc('<script>alert(123);</script>');
// Equivalently to: `htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true)`
```

This method is for convenience, but you can also use the original `htmlspecialchars()` function, as all existing PHP functions are available in views and layouts.
