<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

use Symfony\Component\Process\Process;

final class ComposerProcessResultViaSymfonyProcess implements ComposerProcessResultInterface
{
    /** @var Process */
    private $process;

    public function __construct(Process $process)
    {
        $this->process = $process;
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
