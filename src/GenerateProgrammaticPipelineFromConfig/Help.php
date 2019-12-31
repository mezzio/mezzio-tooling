<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Tooling\GenerateProgrammaticPipelineFromConfig;

use Laminas\Stdlib\ConsoleHelper;

class Help
{
    const TEMPLATE = <<< 'EOT'
<info>Usage:</info>

  %s <command> [options]

<info>Commands:</info>

  <info>help</info>             Display this help/usage message
  <info>generate</info>         Generate a programmatic pipeline from configuration

<info>Options:</info>

  <info>--help|-h</info>        Display this help/usage message
  <info>--config-file</info>    Specify a path to the configuration file; defaults
                   to config/config.php. The file is expected to return
                   a PHP array value containing all configuration.

Reads existing configuration from the --config-file, and uses that
information to generate a programmatic middleware pipeline and routing
table for your application:

- The pipeline is written to config/pipeline.php.
- The routing rules are written to config/routes.php.
- A new configuration file, config/autoload/programmatic-pipeline.global.php,
  is created with configuration to enable the programmatic pipeline and
  new error handling mechanisms.
- The script updates public/index.php to require the pipeline and
  routing configuration files prior to running the application.

EOT;

    /**
     * @var string
     */
    private $command;

    /**
     * @var ConsoleHelper
     */
    private $helper;

    /**
     * @param string $command Name of script invoking the command.
     * @param ConsoleHelper $helper
     */
    public function __construct($command, ConsoleHelper $helper)
    {
        $this->command = $command;
        $this->helper = $helper;
    }

    /**
     * Emit the help message.
     *
     * @param resource $resource Stream to which to write; defaults to STDOUT.
     * @return void
     */
    public function __invoke($resource = STDOUT)
    {
        // Find relative command path
        $command = strtr(realpath($this->command) ?: $this->command, [
            getcwd() . DIRECTORY_SEPARATOR => '',
            'laminas' . DIRECTORY_SEPARATOR . 'mezzio-tooling' . DIRECTORY_SEPARATOR => '',
        ]);

        $this->helper->writeLine(sprintf(
            self::TEMPLATE,
            $command
        ), true, $resource);
    }
}
