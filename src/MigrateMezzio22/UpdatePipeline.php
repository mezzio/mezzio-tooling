<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Tooling\MigrateMezzio22;

use Symfony\Component\Console\Output\OutputInterface;

class UpdatePipeline
{
    // @codingStandardsIgnoreStart
    /**
     * PCRE strings to match, and their replacements.
     * @var string[]
     */
    private $matches = [
        '/-\>pipeRoutingMiddleware\(\)/'                        => '->pipe(\Mezzio\Router\Middleware\RouteMiddleware::class)',
        '/-\>pipeDispatchMiddleware\(\)/'                       => '->pipe(\Mezzio\Router\Middleware\DispatchMiddleware::class)',
        '/-\>pipe\(.*?(Implicit(Head|Options)Middleware).*?\)/' => '->pipe(\Mezzio\Router\Middleware\\\\$1::class)'
    ];
    // @codingStandardsIgnoreEnd

    /**
     * @param string $projectPath
     * @return void
     */
    public function __invoke(OutputInterface $output, $projectPath)
    {
        $filename = sprintf('%s/config/pipeline.php', $projectPath);
        $contents = '';
        $fh = fopen($filename, 'r+');

        while (! feof($fh)) {
            if (false === ($line = fgets($fh))) {
                break;
            }

            $contents .= $this->matchAndReplace($output, $line);
        }

        fclose($fh);

        file_put_contents($filename, $contents);
    }

    /**
     * @param string $line
     * @return string
     */
    private function matchAndReplace(OutputInterface $output, $line)
    {
        $updated = $line;
        foreach ($this->matches as $pattern => $replacement) {
            $updated = preg_replace($pattern, $replacement, $updated);
        }

        if ($updated !== $line) {
            $output->writeln(sprintf(
                '<info>Rewrote line "%s" to "%s"</info>',
                $line,
                $updated
            ));
        }

        return $updated;
    }
}
