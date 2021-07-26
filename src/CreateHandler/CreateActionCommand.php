<?php

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class CreateActionCommand extends CreateHandlerCommand
{
    public const HELP_DESCRIPTION = 'Create an action class file.';

    public const HELP = <<<'EOT'
        Creates an action class file named after the provided class. For a path, it
        matches the class namespace against PSR-4 autoloader namespaces in your
        composer.json.
        EOT;

    public const HELP_ARG_ACTION = <<<'EOT'
        Fully qualified class name of the action class to create. This value
        should be quoted to ensure namespace separators are not interpreted as
        escape sequences by your shell.
        EOT;

    public const HELP_OPT_NO_FACTORY = <<<'EOT'
        By default, this command generates a factory for the action class it creates,
        and registers it with the container. Passing this option disables that
        feature.
        EOT;

    public const HELP_OPT_NO_REGISTER = <<<'EOT'
        By default, when this command generates a factory for the action class it
        creates, it registers it with the container. Passing this option disables
        registration of the generated factory with the container.
        EOT;

    public const STATUS_TEMPLATE = '<info>Creating action %s...</info>';

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:action:create';

    protected function configure(): void
    {
        $this->handlerArgument = 'action';
        $this->setDescription(self::HELP_DESCRIPTION);
        $this->setHelp(self::HELP);
        $this->addArgument('action', InputArgument::REQUIRED, self::HELP_ARG_ACTION);
        $this->addOption('no-factory', null, InputOption::VALUE_NONE, self::HELP_OPT_NO_FACTORY);
        $this->addOption('no-register', null, InputOption::VALUE_NONE, self::HELP_OPT_NO_REGISTER);

        $this->configureTemplateOptions();
    }
}
