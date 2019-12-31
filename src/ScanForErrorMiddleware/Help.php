<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Tooling\ScanForErrorMiddleware;

use Laminas\Stdlib\ConsoleHelper;

class Help
{
    const TEMPLATE = <<< 'EOT'
<info>Usage:</info>

  %s <command> [options]

<info>Commands:</info>

  <info>help</info>             Display this help/usage message
  <info>scan</info>             Scan for error middleware in the given directory

<info>Options:</info>

  <info>--help|-h</info>        Display this help/usage message
  <info>--dir</info>            Specify a path to scan; defaults to src/.

Scans the directory provided by --dir (defaulting to src/) for classes
that either implement Laminas\Stratigility\ErrorMiddlewareInterface,
or which implement __invoke() using that signature. Any that are
discovered are reported to the console.
  
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
        // Use basename of command if it is a realpath
        $command = (file_exists($this->command) && realpath($this->command) === $this->command)
            ? basename($this->command)
            : $this->command;

        $this->helper->writeLine(sprintf(
            self::TEMPLATE,
            $command
        ), true, $resource);
    }
}
