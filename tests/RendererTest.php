<?php

declare(strict_types=1);

namespace Devanych\Tests\View;

use Devanych\View\Extension\ExtensionInterface;
use Devanych\View\Renderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

use function preg_replace;
use function realpath;

class RendererTest extends TestCase
{
    /**
     * @var Renderer
     */
    private Renderer $renderer;

    public function setUp(): void
    {
        $this->renderer = new Renderer(realpath(__DIR__ . '/TestAsset'));
    }

    public function testConstructTrimsTrailingSlash(): void
    {
        $viewDirectory = realpath(__DIR__ . '/TestAsset');

        $renderer = new Renderer($viewDirectory . '////');
        $this->assertSame('<p>Content</p>', preg_replace('/\s/', '', $renderer->render('views/single')));

        $renderer = new Renderer($viewDirectory . '\\\\');
        $this->assertSame('<p>Content</p>', preg_replace('/\s/', '', $renderer->render('views/single')));

        $renderer = new Renderer($viewDirectory . '\/\/');
        $this->assertSame('<p>Content</p>', preg_replace('/\s/', '', $renderer->render('views/single')));
    }

    public function testConstructThrowRuntimeExceptionForNonExistViewDirectory(): void
    {
        $this->expectException(RuntimeException::class);
        new Renderer('/view/directory/not/exist');
    }

    public function testBlockAndRenderBlock(): void
    {
        $this->assertSame('', $this->renderer->renderBlock('name'));
        $this->assertSame('default', $this->renderer->renderBlock('name', 'default'));

        $this->renderer->block('name', 'value');
        $this->assertSame('value', $this->renderer->renderBlock('name'));

        $this->renderer->block('name', 'other-value');
        $this->assertSame('value', $this->renderer->renderBlock('name'));
    }

    public function testBlockThrowRuntimeExceptionWhenBlockNameIsContent(): void
    {
        $this->expectException(RuntimeException::class);
        $this->renderer->block('content', 'value');
    }

    public function testBeginBlockAndEndBlock(): void
    {
        $this->renderer->beginBlock('name');
        echo $value = '<p>Block Value</p>';
        $this->renderer->endBlock();
        $this->expectOutputString('');
        $this->assertSame($value, $this->renderer->renderBlock('name'));

        $this->renderer->beginBlock('name');
        echo '<p>Other Block Value</p>';
        $this->renderer->endBlock();
        $this->expectOutputString('');
        $this->assertSame($value, $this->renderer->renderBlock('name'));

        $this->renderer->beginBlock('empty');
        $this->renderer->endBlock();
        $this->expectOutputString('');
        $this->assertSame('', $this->renderer->renderBlock('empty'));
    }

    public function testBeginBlockAndEndBlockThrowRuntimeExceptionWhenBlockNameIsContent(): void
    {
        $this->renderer->beginBlock('content');
        $this->expectException(RuntimeException::class);
        $this->renderer->endBlock();
    }

    public function testEndBlockThrowRuntimeExceptionWhenTryingEndBlockNotBeginBlock(): void
    {
        $this->expectException(RuntimeException::class);
        $this->renderer->endBlock();
    }

    public function testRenderSingleView(): void
    {
        $this->assertSame('<p>Content</p>', $this->render('views/single'));
    }

    public function testRenderNestedViews(): void
    {
        $output = '<html><head><title>PageTitle</title></head><body><header>Header</header>'
        . '<nav>Menu</nav><main>Sub<p>Content</p></main><footer>Footer</footer></body></html>';
        $this->assertSame($output, $this->render('views/nested'));
    }

    public function testRenderTrimsLeadingAndTrailingSlash(): void
    {
        $this->assertSame('<p>Content</p>', $this->render('/views/single/'));
        $this->assertSame('<p>Content</p>', $this->render('\\views/single\\'));
        $this->assertSame('<p>Content</p>', $this->render('\\/\/views/single/\///'));
    }

    public function testLayoutIsNotSetOutsideView(): void
    {
        $this->assertSame('<p>Content</p>', $this->render('views/single'));
        $this->renderer->layout('layouts/_sub');
        $this->assertSame('<p>Content</p>', $this->render('views/single'));
    }

    public function testAddGlobal(): void
    {
        $this->assertSame('<p>Content</p>', $this->render('views/single'));
        $this->renderer->addGlobal('global', '<p>GlobalVariable</p>');
        $this->assertSame('<p>Content</p><p>GlobalVariable</p>', $this->render('views/single'));
    }

    public function testAddGlobalThrowRuntimeExceptionWhenTryingToReAdd(): void
    {
        $this->renderer->addGlobal('global', '<p>GlobalVariable</p>');
        $this->assertSame('<p>Content</p><p>GlobalVariable</p>', $this->render('views/single'));
        $this->expectException(RuntimeException::class);
        $this->renderer->addGlobal('global', '<p>GlobalVariable</p>');
    }

    public function testAddGlobalOverwriteFromParams(): void
    {
        $this->renderer->addGlobal('global', '<p>GlobalVariable</p>');
        $this->assertSame('<p>Content</p><p>GlobalVariable</p>', $this->render('views/single'));
        $this->assertSame('<p>Content</p><p>OverwriteVariable</p>', $this->render('views/single', [
            'global' => '<p>OverwriteVariable</p>'
        ]));
    }

    public function testEncodeAndDecode(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(123);&lt;/script&gt;',
            $this->renderer->esc('<script>alert(123);</script>')
        );
    }

    public function testAddExtensionAndCallMagic(): void
    {
        $extension = new class implements ExtensionInterface {
            public function getFunctions(): array
            {
                return ['testUppercase' => fn(string $string): string => strtoupper($string)];
            }
        };

        $this->renderer->addExtension($extension);
        $this->assertSame('TEST', $this->renderer->testUppercase('test'));
        $this->assertSame('TEST', $this->renderer->__call('testUppercase', ['test']));
    }

    /**
     * @param string $view
     * @param array $params
     * @return string
     * @throws Throwable
     */
    private function render(string $view, array $params = []): string
    {
        return preg_replace('/\s/', '', $this->renderer->render($view, $params));
    }
}
