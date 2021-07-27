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
        return new RegisterCommand(
            realpath(getcwd()),
            new FileSystemBasedComposerPackageFactory(),
            new ComposerProcessViaSymfonyProcessFactory()
        );
    }
}
