<?php

declare(strict_types=1);

namespace Mezzio\Tooling\CreateMiddleware;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

final class CreateMiddlewareCommand extends Command
{
    public const DEFAULT_SRC = '/src';

    public const HELP = <<<'EOT'
        Creates a PSR-15 middleware class file named after the provided class. For a
        path, it matches the class namespace against PSR-4 autoloader namespaces in
        your composer.json.
        EOT;

    public const HELP_ARG_MIDDLEWARE = <<<'EOT'
        Fully qualified class name of the middleware to create. This value
        should be quoted to ensure namespace separators are not interpreted as
        escape sequences by your shell.
        EOT;

    public const HELP_OPT_NO_FACTORY = <<<'EOT'
        By default, this command generates a factory for the middleware it creates, and
        registers it with the container. Passing this option disables that feature.
        EOT;

    public const HELP_OPT_NO_REGISTER = <<<'EOT'
        By default, when this command generates a factory for the middleware it
        creates, it registers it with the container. Passing this option disables
        registration of the generated factory with the container.
        EOT;

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:middleware:create';

    /** @var string */
    private $projectRoot;

    /**
     * Flag indicating whether or not to require the generated middleware before
     * generating its factory. By default, this is true, as it is necessary
     * in order for the middleware to be reflected. However, during testing, we do
     * not actually generate a middleware, so we need a way to disable it.
     *
     * @var bool
     */
    private $requireMiddlewareBeforeGeneratingFactory = true;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;

        parent::__construct();
    }

    /**
     * Configure the console command.
     */
    protected function configure(): void
    {
        $this->setDescription('Create a PSR-15 middleware class file.');
        $this->setHelp(self::HELP);
        $this->addArgument('middleware', InputArgument::REQUIRED, self::HELP_ARG_MIDDLEWARE);
        $this->addOption('no-factory', null, InputOption::VALUE_NONE, self::HELP_OPT_NO_FACTORY);
        $this->addOption('no-register', null, InputOption::VALUE_NONE, self::HELP_OPT_NO_REGISTER);
    }

    /**
     * Execute console command.
     *
     * @return int Exit status
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $middleware = $input->getArgument('middleware');

        $output->writeln(sprintf('<info>Creating middleware %s...</info>', $middleware));

        $generator = new CreateMiddleware();
        $path      = $generator->process($middleware, $this->projectRoot);

        $output->writeln('<info>Success!</info>');
        $output->writeln(sprintf(
            '<info>- Created class %s, in file %s</info>',
            $middleware,
            $path
        ));

        if (! $input->getOption('no-factory')) {
            if ($this->requireMiddlewareBeforeGeneratingFactory) {
                require $path;
            }
            return $this->generateFactory($middleware, $input, $output);
        }

        return 0;
    }

    private function generateFactory(string $middlewareClass, InputInterface $input, OutputInterface $output): int
    {
        $factoryInput = new ArrayInput([
            'command'       => 'mezzio:factory:create',
            'class'         => $middlewareClass,
            '--no-register' => $input->getOption('no-register'),
        ]);
        $command      = $this->getApplication()->find('mezzio:factory:create');
        return $command->run($factoryInput, $output);
    }
}
