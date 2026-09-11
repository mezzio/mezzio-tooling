<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

use Override;
use Symfony\Component\Process\Process;

final class ComposerProcessResultViaSymfonyProcess implements ComposerProcessResultInterface
{
    public function __construct(private readonly Process $process)
    {
    }

    #[Override]
    public function isSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    #[Override]
    public function getOutput(): string
    {
        return $this->process->getOutput();
    }

    #[Override]
    public function getErrorOutput(): string
    {
        return $this->process->getErrorOutput();
    }
}
