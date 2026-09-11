<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Module;

use Mezzio\Tooling\Composer\ComposerProcessViaSymfonyProcessFactory;
use Mezzio\Tooling\Composer\FileSystemBasedComposerPackageFactory;

use function getcwd;
use function realpath;

final class RegisterCommandFactory
{
    public function __invoke(): RegisterCommand
    {
        $path = realpath(getcwd() ?: '.');
        $path = $path === false ? '/tmp' : $path;

        return new RegisterCommand(
            $path,
            new FileSystemBasedComposerPackageFactory(),
            new ComposerProcessViaSymfonyProcessFactory()
        );
    }
}
