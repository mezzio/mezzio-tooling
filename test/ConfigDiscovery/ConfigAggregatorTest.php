<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\ConfigDiscovery;

use Mezzio\Tooling\ConfigDiscovery\ConfigAggregator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class ConfigAggregatorTest extends TestCase
{
    private vfsStreamDirectory $configDir;

    private ConfigAggregator $locator;

    protected function setUp(): void
    {
        $this->configDir = vfsStream::setup('project');
        $this->locator   = new ConfigAggregator(
            vfsStream::url('project')
        );
    }

    public function testAbsenceOfFileReturnsFalseOnLocate(): void
    {
        $this->assertFalse($this->locator->locate());
    }

    public function testLocateReturnsFalseWhenFileDoesNotHaveExpectedContents(): void
    {
        vfsStream::newFile('config/config.php')
            ->at($this->configDir)
            ->setContent('<?php
return [];');
        $this->assertFalse($this->locator->locate());
    }

    /**
     * @psalm-return array<string, array{
     *     0: string
     * }>
     */
    public function validMezzioConfigContents(): array
    {
        // @codingStandardsIgnoreStart
        return [
            'fqcn-short-array'               => ['<?php
$aggregator = new Laminas\ConfigAggregator\ConfigAggregator([
]);'],
            'globally-qualified-short-array' => ['<?php
$aggregator = new \Laminas\ConfigAggregator\ConfigAggregator([
]);'],
            'imported-short-array'           => ['<?php
$aggregator = new ConfigAggregator([
]);'],
            'fqcn-long-array'                => ['<?php
$aggregator = new Laminas\ConfigAggregator\ConfigAggregator(array(
));'],
            'globally-qualified-long-array'  => ['<?php
$aggregator = new \Laminas\ConfigAggregator\ConfigAggregator(array(
));'],
            'imported-long-array'            => ['<?php
$aggregator = new ConfigAggregator(array(
));'],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider validMezzioConfigContents
     */
    public function testLocateReturnsTrueWhenFileExistsAndHasExpectedContent(string $contents): void
    {
        vfsStream::newFile('config/config.php')
            ->at($this->configDir)
            ->setContent($contents);

        $this->assertTrue($this->locator->locate());
    }
}
