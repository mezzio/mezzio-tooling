<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

final class FileSystemBasedComposerPackageFactory implements ComposerPackageFactoryInterface
{
    public function loadPackage(string $projectRoot): ComposerPackageInterface
    {
        return new FileSystemBasedComposerPackage($projectRoot);
    }
}
