<?php

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

use Mezzio\Template\TemplateRendererInterface;
use RuntimeException;

use function sprintf;

final class UnresolvableRendererException extends RuntimeException
{
    public static function dueToMissingAlias(): self
    {
        return new self(sprintf(
            'Unable to determine what type of template renderer is in use due'
            . ' to an inability to detect a service alias for the service %s;'
            . ' cannot create template.',
            TemplateRendererInterface::class
        ));
    }

    public static function dueToUnknownType(string $type): self
    {
        return new self(sprintf(
            'Detected an unknown template renderer type "%s", and thus cannot'
            . ' create a template as we do not know what extension to use.',
            $type
        ));
    }
}
