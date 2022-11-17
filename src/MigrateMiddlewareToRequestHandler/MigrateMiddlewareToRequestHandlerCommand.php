<?php

declare(strict_types=1);

namespace Mezzio\Tooling\MigrateMiddlewareToRequestHandler;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function is_dir;
use function sprintf;

final class MigrateMiddlewareToRequestHandlerCommand extends Command
{
    /**
     * @var string
     */
    private const DEFAULT_SRC = '/src';

    /**
     * @var string
     */
    private const HELP = <<<'EOT'
        Migrate PSR-15 middleware to request handlers.
        
        Scans all PHP files under the --src directory for PSR-15 middleware. When it
        encounters middleware class files where the "middleware" does not call on the
        second argument (the handler or "delegate"), it converts them to request
        handlers.
        EOT;

    /**
     * @var string
     */
    private const HELP_OPT_SRC = <<<'EOT'
        Specify a path to PHP files under which to migrate PSR-15 middleware to request
        handlers. If not specified, assumes src/ under the current working path.
        EOT;

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:middleware:to-request-handler';

    /**
     * @var null|string Project root against which to scan.
     */
    public function __construct(private string $projectRoot)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Migrate PSR-15 middleware to request handlers');
        $this->setHelp(self::HELP);
        $this->addOption('src', 's', InputOption::VALUE_REQUIRED, self::HELP_OPT_SRC);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $src = $this->getSrcDir($input);

        $output->writeln(sprintf(
            '<info>Scanning "%s" for PSR-15 middleware to convert...</info>',
            $src
        ));

        $converter = new ConvertMiddleware($output);
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
