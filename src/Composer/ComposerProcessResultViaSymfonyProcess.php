<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

use Symfony\Component\Process\Process;

final class ComposerProcessResultViaSymfonyProcess implements ComposerProcessResultInterface
{
    public function __construct(private Process $process)
    {
    }

    public function isSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    public function getOutput(): string
    {
        return $this->process->getOutput();
    }

    public function getErrorOutput(): string
    {
        return $this->process->getErrorOutput();
    }
}
