<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Module;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use function preg_replace;
use function str_replace;

/**
 * @internal
 */
final class CommandCommonOptions
{
    /**
     * Add default arguments and options used by all commands.
     */
    public static function addDefaultOptionsAndArguments(Command $command): void
    {
        $command->addArgument(
            'module',
            InputArgument::REQUIRED,
            $command::HELP_ARG_MODULE
        );

        $command->addOption(
            'composer',
            'c',
            InputOption::VALUE_REQUIRED,
            'Specify the path to the composer binary; defaults to "composer"'
        );

        $command->addOption(
            'modules-path',
            'p',
            InputOption::VALUE_REQUIRED,
            'Specify the path to the modules directory; defaults to "src"'
        );
    }

    /**
     * Retrieve the modules path from  1: $input, 2: project config or 3: default 'src'
     *
     * @param array|ArrayAccess $config
     */
    public static function getModulesPath(InputInterface $input, $config = []): string
    {
        $configuredModulesPath = $config[self::class]['--modules-path'] ?? 'src';
        $modulesPath           = $input->getOption('modules-path') ?? $configuredModulesPath;
        $modulesPath           = preg_replace('/^\.\//', '', str_replace('\\', '/', $modulesPath));

        return $modulesPath;
    }
}
