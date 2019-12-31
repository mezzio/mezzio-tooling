<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Tooling\MigrateMezzio22;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateMezzio22Command extends Command
{
    const HELP = <<< 'EOT'
Migrate an Mezzio application to version 2.2.

This command does the following:

- Adds entries for the mezzio and mezzio-router
  ConfigProvider classes to config/config.php
- Updates entries to pipeRoutingMiddleware() to instead pipe the
  mezzio-router RouteMiddleware.
- Updates entries to pipeDispatchMiddleware() to instead pipe the
  mezzio-router DispatchMiddleware.
- Updates entries to pipe the various Implicit*Middleware to pipe the
  new mezzio-router versions.

These changes are made to prepare your application for version 3, and to remove
known deprecation messages.
EOT;

    /**
     * @var null|string Root path of the application being updated; defaults to $PWD
     */
    private $projectDir;

    /**
     * @var null|string Project root in which to make updates.
     */
    public function setProjectDir($path)
    {
        $this->projectDir = $path;
    }

    /**
     * Configure the console command.
     */
    protected function configure()
    {
        $this->setDescription('Migrate an Mezzio application to version 2.2.');
        $this->setHelp(self::HELP);
    }

    /**
     * Execute console command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Exit status
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectDir = $this->getProjectDir();

        $output->writeln('<info>Migrating application to Mezzio 2.2...</info>');

        $output->writeln('<info>- Updating config/config.php</info>');
        $updateConfig = new UpdateConfig();
        $updateConfig($output, $projectDir);

        $output->writeln('<info>- Updating config/pipeline.php</info>');
        $updatePipeline = new UpdatePipeline();
        $updatePipeline($output, $projectDir);

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    /**
     * Retrieve the project root directory.
     *
     * Uses result of getcwd() if not previously set.
     *
     * @return string
     */
    private function getProjectDir()
    {
        return $this->projectDir ?: realpath(getcwd());
    }
}
