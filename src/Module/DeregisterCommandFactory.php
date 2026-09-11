<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Module;

use Mezzio\Tooling\Composer\ComposerProcessViaSymfonyProcessFactory;
use Mezzio\Tooling\Composer\FileSystemBasedComposerPackageFactory;

use function getcwd;
use function realpath;

final class DeregisterCommandFactory
{
    public function __invoke(): DeregisterCommand
    {
        $path = realpath(getcwd() ?: '.');
        $path = $path === false ? '/tmp' : $path;

        return new DeregisterCommand(
            $path,
            new FileSystemBasedComposerPackageFactory(),
            new ComposerProcessViaSymfonyProcessFactory()
        );
    }
}
