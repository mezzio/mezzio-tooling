<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Module;

trait CommonOptionsAndAttributesTrait
{
    public function testConfigureSetsExpectedArgument()
    {
        $definition = $this->command->getDefinition();
        self::assertTrue($definition->hasArgument('module'));
        $argument = $definition->getArgument('module');
        self::assertTrue($argument->isRequired());
        self::assertEquals($this->expectedModuleArgumentDescription, $argument->getDescription());
    }

    public function testConfigureSetsExpectedComposerOption()
    {
        $definition = $this->command->getDefinition();
        self::assertTrue($definition->hasOption('composer'));
        $option = $definition->getOption('composer');
        self::assertTrue($option->isValueRequired());
        self::assertStringContainsString('path to the composer binary', $option->getDescription());
    }

    public function testConfigureSetsExpectedPathOption()
    {
        $definition = $this->command->getDefinition();
        self::assertTrue($definition->hasOption('modules-path'));
        $option = $definition->getOption('modules-path');
        self::assertTrue($option->isValueRequired());
        self::assertStringContainsString('path to the modules directory', $option->getDescription());
    }
}
