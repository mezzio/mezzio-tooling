<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

interface ComposerPackageFactoryInterface
{
    public function loadPackage(string $projectRoot): ComposerPackageInterface;
}
