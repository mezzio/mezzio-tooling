<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

use Mezzio\Template\TemplateRendererInterface;

return [
    'templates' => [
        'extension' => '%s',
        'paths' => [
            'test' => ['templates/test'],
        ],
    ],
];
