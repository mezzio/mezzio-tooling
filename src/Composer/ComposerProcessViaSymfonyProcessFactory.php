<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

use Symfony\Component\Process\Process;

final class ComposerProcessViaSymfonyProcessFactory implements ComposerProcessFactoryInterface
{
    public function createProcess(array $args): ComposerProcessInterface
    {
        return new ComposerProcessViaSymfonyProcess(
            new Process($args)
        );
    }
}
