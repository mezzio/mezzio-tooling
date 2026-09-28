<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;
use Boundwize\StructArmed\Rule\Rules\Class_\MustBeFinalRule;

return Architecture::define()
    ->skip([
        'test/**/TestAsset',
    ])
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->layer('tests', 'test')
    ->rule(
        'tests_classes.must_be_final',
        new MustBeFinalRule(layer: 'tests')
    )
    ->layer('Exception', 'src/Exception')
    ->layer('TemplateResolution', 'src/TemplateResolutionTrait.php')
    ->layer('Composer', 'src/Composer')
    ->layer('ConfigDiscovery', 'src/ConfigDiscovery')
    ->layer('ConfigInjector', 'src/ConfigInjector')
    ->layer('CreateHandler', 'src/CreateHandler')
    ->layer('CreateMiddleware', 'src/CreateMiddleware')
    ->layer('Factory', 'src/Factory')
    ->layer('MigrateInteropMiddleware', 'src/MigrateInteropMiddleware')
    ->layer('MigrateMiddlewareToRequestHandler', 'src/MigrateMiddlewareToRequestHandler')
    ->layer('Module', 'src/Module')
    ->layer('RoutesFilter', 'src/Routes/Filter')
    ->layer('Routes', 'src/Routes', 'src/Routes/Filter')
    ->layer('ConfigProvider', 'src/ConfigProvider.php')
    ->ruleset([
        'Exception'                         => [],
        'TemplateResolution'                => [],
        'Composer'                          => [],
        'ConfigDiscovery'                   => [],
        'ConfigInjector'                    => ['ConfigDiscovery', 'Exception'],
        'CreateHandler'                     => ['TemplateResolution'],
        'CreateMiddleware'                  => [],
        'Factory'                           => [],
        'MigrateInteropMiddleware'          => [],
        'MigrateMiddlewareToRequestHandler' => [],
        'Module'                            => ['Composer', '+ConfigInjector', 'TemplateResolution'],
        'RoutesFilter'                      => [],
        'Routes'                            => ['RoutesFilter'],
        'ConfigProvider'                    => [
            '+CreateHandler',
            'CreateMiddleware',
            'Factory',
            'MigrateInteropMiddleware',
            'MigrateMiddlewareToRequestHandler',
            '+Module',
            '+Routes',
        ],
    ]);
