<?php

declare(strict_types=1);

namespace Devanych\Tests\View\Extension;

use Devanych\View\Extension\AssetExtension;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function filemtime;
use function realpath;

class AssetExtensionTest extends TestCase
{
    /**
     * @var string
     */
    private string $baseUrl;

    /**
     * @var string
     */
    private string $basePath;

    public function setUp(): void
    {
        $this->baseUrl = 'https://example.com/assets';
        $this->basePath = realpath(__DIR__ . '/../TestAsset/assets');
    }

    public function testConstructorTrimsTrailingSlash(): void
    {
        $extension = new AssetExtension($this->basePath . '////', $this->baseUrl);
        $this->assertSame($this->baseUrl . '/style.css', $extension->assetFile('style.css'));

        $extension = new AssetExtension($this->basePath . '\\\\', $this->baseUrl);
        $this->assertSame($this->baseUrl . '/style.css', $extension->assetFile('style.css'));

        $extension = new AssetExtension($this->basePath . '\/\/', $this->baseUrl . '///');
        $this->assertSame($this->baseUrl . '/style.css', $extension->assetFile('style.css'));
    }

    public function testAssetFileWithTrueAppendTimestampFlag(): void
    {
        $extension = $extension = new AssetExtension($this->basePath, $this->baseUrl, true);
        $expected = $this->baseUrl . '/style.css?v=' . filemtime($this->basePath . '/style.css');
        $this->assertSame($expected, $extension->assetFile('style.css'));
    }

    public function testAssetFileTrimsLeadingSlash(): void
    {
        $extension = $extension = new AssetExtension($this->basePath, $this->baseUrl);
        $this->assertSame($this->baseUrl . '/style.css', $extension->assetFile('/style.css'));

        $extension = $extension = new AssetExtension($this->basePath, $this->baseUrl, true);
        $expected = $this->baseUrl . '/style.css?v=' . filemtime($this->basePath . '/style.css');
        $this->assertSame($expected, $extension->assetFile('///style.css'));
    }

    public function testAssetFileThrowRuntimeExceptionForNonExistAssetFile(): void
    {
        $extension = $extension = new AssetExtension($this->basePath, $this->baseUrl);
        $this->expectException(RuntimeException::class);
        $extension->assetFile('asset/file/not/exist');
    }
}
