<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Factory;

use Mezzio\Tooling\Factory\Create;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

final class CreateFactoryCommand extends Command
{
    /**
     * @var string
     */
    public const DEFAULT_SRC = '/src';

    /**
     * @var string
     */
    public const HELP = <<<'EOT'
        Creates a factory class file for generating the provided class, in the
        same directory as the provided class.
        EOT;

    /**
     * @var string
     */
    public const HELP_ARG_CLASS = <<<'EOT'
        Fully qualified class name of the class for which to create a factory.
        This value should be quoted to ensure namespace separators are not
        interpreted as escape sequences by your shell. The class should be
        autoloadable.
        EOT;

    /**
     * @var string
     */
    public const HELP_OPT_NO_REGISTER = <<<'EOT'
        When this flag is present, the command WILL NOT register the factory
        with the application container.
        EOT;

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:factory:create';

    public function __construct(private Create $generator, private string $projectRoot)
    {
        parent::__construct();
    }

    /**
     * Configure the console command.
     */
    protected function configure(): void
    {
        $this->setDescription('Create a factory class file for the named class.');
        $this->setHelp(self::HELP);
        $this->addArgument('class', InputArgument::REQUIRED, self::HELP_ARG_CLASS);
        $this->addOption('no-register', null, InputOption::VALUE_NONE, self::HELP_OPT_NO_REGISTER);
    }

    /**
     * Execute console command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $className       = (string) $input->getArgument('class');
        $factoryName     = $className . 'Factory';
        $registerFactory = ! $input->getOption('no-register');
        $configFile      = null;

        $output->writeln(sprintf('<info>Creating factory for class %s...</info>', $className));

        $path = $this->generator->createForClass($className);

        if ($registerFactory) {
            $output->writeln('<info>Registering factory with container</info>');
            $injector   = new ConfigInjector($this->projectRoot);
            $configFile = $injector->injectFactoryForClass($factoryName, $className);
        }

        $output->writeln('<info>Success!</info>');
        $output->writeln(sprintf(
            '<info>- Created factory class %s, in file %s</info>',
            $factoryName,
            $path
        ));

        if ($registerFactory) {
            $output->writeln(sprintf(
                '<info>- Registered factory to container in file %s</info>',
                $configFile
            ));
        }

        return 0;
    }
}
