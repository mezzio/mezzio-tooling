<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Tooling\Module\Command;

use Mezzio\Tooling\Module\Exception;

abstract class AbstractCommand
{
    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var string
     */
    protected $composer;

    /**
     * @var string
     */
    protected $modulesPath;

    /**
     * @param string $projectDir
     * @param string $modulesPath
     * @param string $composer
     */
    public function __construct($projectDir, $modulesPath, $composer)
    {
        $this->projectDir = $projectDir;
        $this->composer = $composer;
        $this->modulesPath = $modulesPath;
    }

    /**
     * Processes the command.
     *
     * @param string $moduleName
     * @return string Success message.
     * @throws Exception\RuntimeException
     */
    abstract public function process($moduleName);
}
