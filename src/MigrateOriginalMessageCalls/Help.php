<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Tooling\MigrateOriginalMessageCalls;

use Laminas\Stdlib\ConsoleHelper;

class Help
{
    const TEMPLATE = <<< 'EOT'
<info>Usage:</info>

  %s [command] [options]

<info>Commands:</info>

  <info>help</info>             Display this help/usage message
  <info>scan</info>             Scan for files to migrate.

<info>Options:</info>

  <info>--help|-h</info>        Display this help/usage message
  <info>--src</info>            Specify a path to PHP files to scan for
                   getOriginalRequest/getOriginalUri/getOriginalResponse
                   calls. If not specified, assumes src/ under the
                   current working path.

Migrate an Mezzio application to remove calls to legacy
request/response methods.

Scans all PHP files under the --src directory for any calls to
getOriginalRequest(), getOriginalUri(), or getOriginalResponse().

In the case of getOriginalResponse(), the call will be rewritten to
getAttribute('originalRequest', {requestVariable}).

In the case of getOriginalUri(), the call will be rewritten to
getAttribute('originalUri, {requestVariable}->getUri()).

If any getOriginalResponse() calls are detected, the script will present
a warning indicating the file(s) and detail how to correct these
manually.
  
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
     * @param string $command Name of command.
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
