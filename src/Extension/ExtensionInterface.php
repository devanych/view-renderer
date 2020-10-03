<?php

declare(strict_types=1);

namespace Devanych\View\Extension;

interface ExtensionInterface
{
    /**
     * Returns an array of functions as `function name` => `function callback`.
     *
     * @return array<string, callable>
     */
    public function getFunctions(): array;
}
