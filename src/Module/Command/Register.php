<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Tooling\Module\Command;

use Laminas\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Laminas\ComponentInstaller\Injector\InjectorInterface;
use Laminas\ComposerAutoloading\Command\Enable;
use Laminas\ComposerAutoloading\Exception\RuntimeException;
use Mezzio\Tooling\Module\Exception;

class Register extends AbstractCommand
{
    /**
     * Register module in application configuration, and enable autoloading of module via composer.
     *
     * {@inheritdoc}
     */
    public function process($moduleName)
    {
        $injector = new ConfigAggregatorInjector($this->projectDir);
        $configProvider = sprintf('%s\ConfigProvider', $moduleName);
        if (! $injector->isRegistered($configProvider)) {
            $injector->inject(
                $configProvider,
                InjectorInterface::TYPE_CONFIG_PROVIDER
            );
        }

        try {
            $enable = new Enable($this->projectDir, $this->modulesPath, $this->composer);
            $enable->setMoveModuleClass(false);
            $enable->process($moduleName);
        } catch (RuntimeException $ex) {
            throw new Exception\RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
        }

        return sprintf('Registered autoloading rules and added configuration entry for module %s', $moduleName);
    }
}
