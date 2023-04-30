<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Module;

use Mezzio\Tooling\Module\CommandCommonOptions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;

class CommandCommonOptionsTest extends TestCase
{
    /** @var InputInterface&MockObject */
    private InputInterface $input;

    protected function setUp(): void
    {
        $this->input = $this->createMock(InputInterface::class);
    }

    public function testGetModulesPathGetsOptionsFromInput(): void
    {
        $config = [];
        $this->input->method('getOption')->with('modules-path')->willReturn('path-from-input');
        $config[CommandCommonOptions::class]['--modules-path'] = 'path-from-config';

        self::assertEquals(
            'path-from-input',
            CommandCommonOptions::getModulesPath($this->input, $config)
        );
    }

    public function testGetModulesPathGetsOptionsFromConfig(): void
    {
        $config = [];
        $this->input->method('getOption')->with('modules-path')->willReturn(null);
        $config[CommandCommonOptions::class]['--modules-path'] = 'path-from-config';

        self::assertEquals(
            'path-from-config',
            CommandCommonOptions::getModulesPath($this->input, $config)
        );
    }

    public function testGetModulesPathGetsDefaultOption(): void
    {
        $this->input->method('getOption')->with('modules-path')->willReturn(null);

        self::assertEquals(
            'src',
            CommandCommonOptions::getModulesPath($this->input)
        );
    }
}
