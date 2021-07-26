<?php

declare(strict_types=1);

namespace Mezzio\Tooling\MigrateInteropMiddleware;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function is_dir;
use function sprintf;

final class MigrateInteropMiddlewareCommand extends Command
{
    private const DEFAULT_SRC = '/src';

    private const HELP = <<<'EOT'
        Migrate an Mezzio application to PSR-15 middleware.
        
        Scans all PHP files under the --src directory for interop middleware
        and delegators. Changes imported interop classes to PSR-15 interfaces,
        keeps aliases and adds return type if it is not present.
        
        This command is DEPRECATED and only for use with migrating applications from
        Mezzio v2 to v3. The command will be removed in version 2 of
        mezzio-tooling.
        EOT;

    private const HELP_OPT_SRC = <<<'EOT'
        Specify a path to PHP files to migrate interop middleware.
        If not specified, assumes src/ under the current working path.
        EOT;

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:middleware:migrate-from-interop';

    /** @var null|string Path from which to resolve default src directory */
    private $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Migrate http-interop middleware and delegators');
        $this->setHelp(self::HELP);
        $this->addOption('src', 's', InputOption::VALUE_REQUIRED, self::HELP_OPT_SRC);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $src = $this->getSrcDir($input);

        $output->writeln('<info>Scanning for usage of http-interop middleware...</info>');

        $converter = new ConvertInteropMiddleware($output);
        $converter->process($src);

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    /**
     * @throws ArgvException
     */
    private function getSrcDir(InputInterface $input): string
    {
        $path = $input->getOption('src') ?: self::DEFAULT_SRC;
        $path = $this->projectRoot . '/' . $path;

        if (! is_dir($path)) {
            throw new ArgvException(sprintf(
                'Invalid --src argument "%s"; directory does not exist',
                $path
            ));
        }

        return $path;
    }
}
