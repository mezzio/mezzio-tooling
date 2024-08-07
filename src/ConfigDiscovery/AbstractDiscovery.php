<?php

declare(strict_types=1);

namespace Mezzio\Tooling\ConfigDiscovery;

use function file_get_contents;
use function is_dir;
use function is_file;
use function preg_match;
use function sprintf;

/**
 * @internal
 */
abstract class AbstractDiscovery implements DiscoveryInterface
{
    /**
     * Configuration file to look for.
     *
     * Implementations MUST overwite this.
     */
    protected string $configFile = '';

    /**
     * Expected pattern to match if the configuration file exists.
     *
     * Implementations MUST overwrite this.
     */
    protected string $expected = '';

    /**
     * Optionally specify project directory; $configFile will be relative to
     * this value.
     */
    public function __construct(string $projectDirectory = '')
    {
        if ('' === $projectDirectory) {
            return;
        }

        if (! is_dir($projectDirectory)) {
            return;
        }

        $this->configFile = sprintf(
            '%s/%s',
            $projectDirectory,
            $this->configFile
        );
    }

    /**
     * Determine if the configuration file exists and contains modules.
     */
    public function locate(): bool
    {
        if (! is_file($this->configFile)) {
            return false;
        }

        $config = file_get_contents($this->configFile);
        return 1 === preg_match($this->expected, $config);
    }
}
