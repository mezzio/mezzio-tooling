<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Tooling\MigrateMezzio22;

use Symfony\Component\Console\Output\OutputInterface;

class UpdateConfig
{
    /**
     * @param string $projectPath
     * @return void
     */
    public function __invoke(OutputInterface $output, $projectPath)
    {
        $filename = sprintf('%s/config/config.php', $projectPath);
        $contents = file_get_contents($filename);

        $components = [
            \Mezzio\Router\ConfigProvider::class,
            \Mezzio\ConfigProvider::class,
        ];

        $pattern = sprintf(
            "/(new (?:%s?%s)?ConfigAggregator\(\s*(?:array\(|\[)\s*)(?:\r|\n|\r\n)(\s*)/",
            preg_quote('\\'),
            preg_quote('Laminas\ConfigAggregator\\')
        );

        $replacementTemplate = "\$1\n\$2\\%s::class,\n\$2";

        foreach ($components as $component) {
            $output->writeln(sprintf(
                '<info>Adding %s to config/config.php</info>',
                $component
            ));
            $replacement = sprintf($replacementTemplate, $component);
            $contents = preg_replace($pattern, $replacement, $contents);
        }

        file_put_contents($filename, $contents);
    }
}
