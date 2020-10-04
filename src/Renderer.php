<?php

declare(strict_types=1);

namespace Devanych\View;

use Devanych\View\Extension\ExtensionInterface;
use Closure;
use RuntimeException;
use Throwable;

use function array_key_exists;
use function extract;
use function func_get_arg;
use function get_class;
use function htmlspecialchars;
use function is_dir;
use function ltrim;
use function ob_end_clean;
use function ob_get_level;
use function ob_start;
use function pathinfo;
use function rtrim;
use function sprintf;
use function trim;

final class Renderer
{
    /**
     * @var string path to the root directory of views.
     */
    private string $viewDirectory;

    /**
     * @var string file extension of the default views.
     */
    private string $fileExtension;

    /**
     * @var string|null name of the view layout.
     */
    private ?string $layout = null;

    /**
     * @var string|null name of the block currently being rendered.
     */
    private ?string $blockName = null;

    /**
     * @var array<string, string> of blocks content.
     */
    private array $blocks = [];

    /**
     * @var array<string, mixed> global variables that will be available in all views.
     */
    private array $globalVars = [];

    /**
     * @var array<string, ExtensionInterface>
     */
    private array $extensions = [];

    /**
     * @var Closure
     */
    private Closure $renderer;

    /**
     * @param string $viewDirectory path to the root directory of views.
     * @param string $fileExtension file extension of the default views.
     * @throws RuntimeException if the specified path does not exist.
     * @psalm-suppress MixedArgument
     * @psalm-suppress UnresolvableInclude
     */
    public function __construct(string $viewDirectory, string $fileExtension = 'php')
    {
        if (!is_dir($viewDirectory = rtrim($viewDirectory, '\/'))) {
            throw new RuntimeException(sprintf(
                'The specified view directory "%s" does not exist.',
                $viewDirectory
            ));
        }

        if ($fileExtension && $fileExtension[0] === '.') {
            $fileExtension = ltrim($fileExtension, '.');
        }

        $this->viewDirectory = $viewDirectory;
        $this->fileExtension = $fileExtension;
        $this->renderer = function (): void {
            extract(func_get_arg(1), EXTR_OVERWRITE);
            require func_get_arg(0);
        };
    }

    /**
     * Adds an extension.
     *
     * @param ExtensionInterface $extension
     */
    public function addExtension(ExtensionInterface $extension): void
    {
        $this->extensions[get_class($extension)] = $extension;
    }

    /**
     * Adds a global variable.
     *
     * @param string $name variable name.
     * @param mixed $value variable value.
     * @throws RuntimeException if this global variable has already been added.
     */
    public function addGlobal(string $name, $value): void
    {
        if (array_key_exists($name, $this->globalVars)) {
            throw new RuntimeException(sprintf(
                'Unable to add "%s" as this global variable has already been added.',
                $name
            ));
        }

        $this->globalVars[$name] = $value;
    }

    /**
     * @param string $layout name of the view layout.
     */
    public function layout(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Records a block.
     *
     * @param string $name block name.
     * @param string $content block content.
     * @throws RuntimeException if the specified block name is "content".
     */
    public function block(string $name, string $content): void
    {
        if ($name === 'content') {
            throw new RuntimeException('The block name "content" is reserved.');
        }

        if (!$name || array_key_exists($name, $this->blocks)) {
            return;
        }

        $this->blocks[$name] = $content;
    }

    /**
     * Begins recording a block.
     *
     * @param string $name block name.
     * @throws RuntimeException if you try to nest a block in other block.
     * @see block()
     */
    public function beginBlock(string $name): void
    {
        if ($this->blockName) {
            throw new RuntimeException('You cannot nest blocks within other blocks.');
        }

        $this->blockName = $name;
        ob_start();
    }

    /**
     * Ends recording a block.
     *
     * @throws RuntimeException If you try to end a block without beginning it.
     * @see block()
     */
    public function endBlock(): void
    {
        if ($this->blockName === null) {
            throw new RuntimeException('You must begin a block before can end it.');
        }

        $this->block($this->blockName, ob_get_clean());
        $this->blockName = null;
    }

    /**
     * Renders a block.
     *
     * @param string $name block name.
     * @param string $default default content.
     * @return string block content.
     */
    public function renderBlock(string $name, string $default = ''): string
    {
        return $this->blocks[$name] ?? $default;
    }

    /**
     * Renders a view.
     *
     * @param string $view view name.
     * @param array $params view variables (`name => value`).
     * @return string rendered view content.
     * @throws RuntimeException if the view file does not exist or is not a file.
     * @throws Throwable If an error occurred during rendering.
     * @psalm-suppress RedundantCondition
     */
    public function render(string $view, array $params = []): string
    {
        $view = $this->viewDirectory . '/' . trim($view, '\/');

        if (pathinfo($view, PATHINFO_EXTENSION) === '') {
            $view .= ($this->fileExtension ? '.' . $this->fileExtension : '');
        }

        if (!file_exists($view) || !is_file($view)) {
            throw new RuntimeException(sprintf(
                'View file "%s" does not exist or is not a file.',
                $view
            ));
        }

        $level = ob_get_level();
        $this->layout = null;
        ob_start();

        try {
            ($this->renderer)($view, $params + $this->globalVars);
            $content = ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }

        if (!$this->layout) {
            return $content;
        }

        $this->blocks['content'] = $content;
        return $this->render($this->layout);
    }

    /**
     * Escapes special characters, converts them to corresponding HTML entities.
     *
     * @param string $content content to be escaped.
     * @return string escaped content.
     */
    public function esc(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
    }

    /**
     * Magic method used to call extension functions.
     *
     * @param string $name function name.
     * @param array $arguments function arguments.
     * @return mixed result of the function.
     * @throws RuntimeException if the extension or function was not added.
     */
    public function __call(string $name, array $arguments)
    {
        foreach ($this->extensions as $extension) {
            foreach ($extension->getFunctions() as $function => $callback) {
                if ($function === $name) {
                    return ($callback)(...$arguments);
                }
            }
        }

        throw new RuntimeException(sprintf('Calling an undefined function "%s".', $name));
    }
}
