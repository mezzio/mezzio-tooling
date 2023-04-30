<?php

declare(strict_types=1);

namespace Mezzio\Tooling;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

use function preg_replace;
use function str_contains;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;

trait TemplateResolutionTrait
{
    /**
     * Normalizes identifier to lowercase, dash-separated words.
     */
    private function normalizeTemplateIdentifier(string $identifier): string
    {
        $pattern     = ['#(?<=(?:\p{Lu}))(\p{Lu}\p{Ll})#', '#(?<=(?:\p{Ll}|\p{Nd}))(\p{Lu})#'];
        $replacement = ['-\1', '-\1'];
        $identifier  = preg_replace($pattern, $replacement, $identifier);
        return strtolower($identifier);
    }

    /**
     * Returns the top-level namespace for the given class.
     */
    private function getNamespace(string $class): string
    {
        $topLevelOffset = strpos($class, '\\');

        return $topLevelOffset !== false
            ? substr($class, 0, $topLevelOffset)
            : $class;
    }

    /**
     * Retrieves the namespace for the class using getNamespace, passes
     * the result to normalizeTemplateIdentifier(), and returns the result.
     */
    private function getTemplateNamespaceFromClass(string $class): string
    {
        return $this->normalizeTemplateIdentifier($this->getNamespace($class));
    }

    /**
     * Returns the unqualified class name (class minus namespace).
     */
    private function getClassName(string $class): string
    {
        return str_contains($class, '\\')
            ? substr($class, strrpos($class, '\\') + 1)
            : $class;
    }

    /**
     * Passes the $class to getClassName(), strips any "Action" or "Handler"
     * or "Middleware" suffixes, passes it to normalizeTemplateIdentifier(),
     * and returns the result.
     */
    private function getTemplateNameFromClass(string $class): string
    {
        return $this->normalizeTemplateIdentifier(
            preg_replace(
                '#(Action|Handler|Middleware)$#',
                '',
                $this->getClassName($class)
            )
        );
    }

    /**
     * Returns true if a renderer service is found in the container.
     */
    private function containerDefinesRendererService(ContainerInterface $container): bool
    {
        return $container->has(TemplateRendererInterface::class);
    }

    private function getRendererServiceTypeFromContainer(ContainerInterface $container): ?string
    {
        if (! $container->has(TemplateRendererInterface::class)) {
            return null;
        }

        $renderer = $container->get(TemplateRendererInterface::class);
        return $renderer::class;
    }
}
