<?php

declare(strict_types=1);

namespace Devanych\View\Extension;

use RuntimeException;

use function file_exists;
use function filemtime;
use function ltrim;
use function rtrim;
use function sprintf;

final class AssetExtension implements ExtensionInterface
{
    /**
     * @var string root directory storing the published asset files.
     */
    private string $basePath;

    /**
     * @var string base URL through which the published asset files can be accessed.
     */
    private string $baseUrl;

    /**
     * @var bool whether to append a timestamp to the URL of every published asset.
     */
    private bool $appendTimestamp;

    /**
     * @param string $basePath root directory storing the published asset files.
     * @param string $baseUrl base URL through which the published asset files can be accessed.
     * @param bool $appendTimestamp whether to append a timestamp to the URL of every published asset.
     */
    public function __construct(string $basePath, string $baseUrl = '', bool $appendTimestamp = false)
    {
        $this->basePath = rtrim($basePath, '\/');
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->appendTimestamp = $appendTimestamp;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions(): array
    {
        return [
            'asset' => [$this, 'assetFile'],
        ];
    }

    /**
     * Includes the asset file and appends a timestamp with the last modification of that file.
     *
     * @param string $file
     * @return string
     */
    public function assetFile(string $file): string
    {
        $url = $this->baseUrl . '/' . ltrim($file, '/');
        $path = $this->basePath . '/' . ltrim($file, '/');

        if (!file_exists($path)) {
            throw new RuntimeException(sprintf(
                'Asset file "%s" does not exist.',
                $path
            ));
        }

        if ($this->appendTimestamp) {
            return $url . '?v=' . filemtime($path);
        }

        return $url;
    }
}
