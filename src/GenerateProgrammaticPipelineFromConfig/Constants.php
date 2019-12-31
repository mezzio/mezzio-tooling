<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Tooling\GenerateProgrammaticPipelineFromConfig;

interface Constants
{
    const PATH_APPLICATION = '/public/index.php';
    const PATH_CONFIG      = '/config/autoload/programmatic-pipeline.global.php';
    const PATH_PIPELINE    = '/config/pipeline.php';
    const PATH_ROUTES      = '/config/routes.php';
}
