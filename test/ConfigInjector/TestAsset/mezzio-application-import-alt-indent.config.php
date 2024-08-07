<?php
use Laminas\ConfigAggregator\ConfigAggregator;

$aggregator = new ConfigAggregator(
    array(
        Application\ConfigProvider::class,
    ),
    'data/cache/config.php'
);

return $aggregator->getMergedConfig();
