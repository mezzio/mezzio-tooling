<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Renderer;

interface RendererInterface
{
    public function render(array $routes): string;
}
