<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Module;

trait CommonOptionsAndAttributesTrait
{
    public function testConfigureSetsExpectedArgument()
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasArgument('module'));
        $argument = $definition->getArgument('module');
        $this->assertTrue($argument->isRequired());
        $this->assertEquals($this->expectedModuleArgumentDescription, $argument->getDescription());
    }

    public function testConfigureSetsExpectedComposerOption()
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('composer'));
        $option = $definition->getOption('composer');
        $this->assertTrue($option->isValueRequired());
        $this->assertStringContainsString('path to the composer binary', $option->getDescription());
    }

    public function testConfigureSetsExpectedPathOption()
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('modules-path'));
        $option = $definition->getOption('modules-path');
        $this->assertTrue($option->isValueRequired());
        $this->assertStringContainsString('path to the modules directory', $option->getDescription());
    }
}
