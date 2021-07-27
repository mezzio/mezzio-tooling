<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

interface ComposerProcessFactoryInterface
{
    /**
     * @param string[] $args List of CLI arguments to run
     */
    public function createProcess(array $args): ComposerProcessInterface;
}
