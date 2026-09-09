<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

use Override;

final class FileSystemBasedComposerPackageFactory implements ComposerPackageFactoryInterface
{
    #[Override]
    public function loadPackage(string $projectRoot): ComposerPackageInterface
    {
        return new FileSystemBasedComposerPackage($projectRoot);
    }
}
