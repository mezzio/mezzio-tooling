<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

use Symfony\Component\Process\Process;

/** @internal */
class ComposerProcessViaSymfonyProcess implements ComposerProcessInterface
{
    public function __construct(private Process $process)
    {
    }

    public function run(): ComposerProcessResultInterface
    {
        $this->process->run();
        return new ComposerProcessResultViaSymfonyProcess($this->process);
    }
}
