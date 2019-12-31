<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Tooling\Module\Command;

use Laminas\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Laminas\ComposerAutoloading\Command\Disable;
use Laminas\ComposerAutoloading\Exception\RuntimeException;
use Mezzio\Tooling\Module\Exception;

class Deregister extends AbstractCommand
{
    /**
     * Deregister module from application configuration, and disable autoloading of module via composer.
     *
     * {@inheritdoc}
     */
    public function process($moduleName)
    {
        $injector = new ConfigAggregatorInjector($this->projectDir);
        $configProvider = sprintf('%s\ConfigProvider', $moduleName);
        if ($injector->isRegistered($configProvider)) {
            $injector->remove($configProvider);
        }

        try {
            $disable = new Disable($this->projectDir, $this->modulesPath, $this->composer);
            $disable->process($moduleName);
        } catch (RuntimeException $ex) {
            throw new Exception\RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
        }

        return sprintf('Removed autoloading rules and configuration entries for module %s', $moduleName);
    }
}
