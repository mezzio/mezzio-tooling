<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Tooling\Module;

use Laminas\Stdlib\ConsoleHelper;

class Help
{
    const TEMPLATE = <<< 'EOT'
<info>Usage:</info>

  %s <command> [options] modulename

<info>Commands:</info>

  <info>help</info>          Display this help/usage message
  <info>create</info>        Create source tree for the mezzio module and register it
  <info>register</info>      Register module in application configuration,
                       and enable autoloading of module via composer
  <info>deregister</info>    Deregister module from application configuration,
                       and disable autoloading of module via composer

<info>Options:</info>

  <info>--help|-h</info>            Display this help/usage message
  <info>--composer|-c</info>        Specify the path to composer binary;
                       defaults to "composer"
  <info>--modules-path|-p</info>    Specify the path to modules directory;
                       defaults to "src"

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
