<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Module;

use Mezzio\Tooling\Module\CommandCommonOptions;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Input\InputInterface;

class CommandCommonOptionsTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<InputInterface> */
    private $input;

    protected function setUp(): void
    {
        $this->input = $this->prophesize(InputInterface::class);
    }

    public function testGetModulesPathGetsOptionsFromInput(): void
    {
        $this->input->getOption('modules-path')->willReturn('path-from-input');
        $config[CommandCommonOptions::class]['--modules-path'] = 'path-from-config';

        self::assertEquals(
            'path-from-input',
            CommandCommonOptions::getModulesPath($this->input->reveal(), $config)
        );
    }

    public function testGetModulesPathGetsOptionsFromConfig(): void
    {
        $this->input->getOption('modules-path')->willReturn(null);
        $config[CommandCommonOptions::class]['--modules-path'] = 'path-from-config';

        self::assertEquals(
            'path-from-config',
            CommandCommonOptions::getModulesPath($this->input->reveal(), $config)
        );
    }

    public function testGetModulesPathGetsDefaultOption(): void
    {
        $this->input->getOption('modules-path')->willReturn(null);

        self::assertEquals(
            'src',
            CommandCommonOptions::getModulesPath($this->input->reveal())
        );
    }
}
