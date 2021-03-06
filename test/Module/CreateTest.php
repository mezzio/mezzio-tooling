<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Module;

use Mezzio\Tooling\Module\Create;
use Mezzio\Tooling\Module\RuntimeException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    use PHPMock;

    /** @var Create */
    private $command;

    /** @var vfsStreamDirectory */
    private $dir;

    /** @var vfsStreamDirectory */
    private $modulesDir;

    /** @var string */
    private $modulesPath = 'my-modules';

    /** @var string */
    private $projectDir;

    protected function setUp() : void
    {
        parent::setUp();

        $this->dir = vfsStream::setup('project');
        $this->modulesDir = vfsStream::newDirectory($this->modulesPath)->at($this->dir);
        $this->projectDir = vfsStream::url('project');
        $this->command = new Create();
    }

    public function testErrorsWhenModuleDirectoryAlreadyExists()
    {
        vfsStream::newDirectory('MyApp')->at($this->modulesDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Module "MyApp" already exists');
        $this->command->process('MyApp', $this->modulesPath, $this->projectDir);
    }

    public function testErrorsWhenCannotCreateModuleDirectory()
    {
        $baseModulePath = sprintf('%s/my-modules/MyApp', $this->dir->url());

        $mkdir = $this->getFunctionMock('Mezzio\Tooling\Module', 'mkdir');
        $mkdir->expects(self::once())
            ->with($baseModulePath)
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Module directory "%s" cannot be created',
            $baseModulePath
        ));
        $this->command->process('MyApp', $this->modulesPath, $this->projectDir);
    }

    public function testErrorsWhenCannotCreateModuleSrcDirectory()
    {
        $baseModulePath = sprintf('%s/my-modules/MyApp', $this->dir->url());

        $mkdir = $this->getFunctionMock('Mezzio\Tooling\Module', 'mkdir');
        $mkdir
            ->expects(self::exactly(2))
            ->withConsecutive([$baseModulePath], [$baseModulePath . '/src'])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Module source directory "%s/src" cannot be created',
            $baseModulePath
        ));
        $this->command->process('MyApp', $this->modulesPath, $this->projectDir);
    }

    public function testErrorsWhenCannotCreateModuleTemplatesDirectory()
    {
        $baseModulePath = sprintf('%s/my-modules/MyApp', $this->dir->url());

        $mkdir = $this->getFunctionMock('Mezzio\Tooling\Module', 'mkdir');
        $mkdir
            ->expects(self::exactly(3))
            ->withConsecutive([$baseModulePath], [$baseModulePath . '/src'], [$baseModulePath . '/templates'])
            ->willReturnOnConsecutiveCalls(true, true, false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Module templates directory "%s/templates" cannot be created',
            $baseModulePath
        ));
        $this->command->process('MyApp', $this->modulesPath, $this->projectDir);
    }

    public function testCreatesConfigProvider()
    {
        $configProvider = vfsStream::url('project/my-modules/MyApp/src/ConfigProvider.php');
        self::assertEquals(
            sprintf('Created module MyApp in %s/MyApp', $this->modulesDir->url()),
            $this->command->process('MyApp', $this->modulesPath, $this->projectDir)
        );
        self::assertFileExists($configProvider);
        $configProviderContent = file_get_contents($configProvider);
        self::assertSame(1, preg_match('/\bnamespace MyApp\b/', $configProviderContent));
        self::assertSame(1, preg_match('/\bclass ConfigProvider\b/', $configProviderContent));
        $command = $this->command;
        $expectedContent = sprintf($command::TEMPLATE_CONFIG_PROVIDER, 'MyApp', 'my-app');
        self::assertSame($expectedContent, $configProviderContent);
    }

    public function testModuleTemplatePathNameWithNumber()
    {
        $this->command->process('My2App', $this->modulesPath, $this->projectDir);
        $configProvider = vfsStream::url('project/my-modules/My2App/src/ConfigProvider.php');
        $configProviderContent = file_get_contents($configProvider);
        $command = $this->command;
        $expectedContent = sprintf($command::TEMPLATE_CONFIG_PROVIDER, 'My2App', 'my2-app');
        self::assertSame($expectedContent, $configProviderContent);
    }

    public function testModuleTemplatePathNameWithSequentialUppercase()
    {
        $this->command->process('THEApp', $this->modulesPath, $this->projectDir);
        $configProvider = vfsStream::url('project/my-modules/THEApp/src/ConfigProvider.php');
        $configProviderContent = file_get_contents($configProvider);
        $command = $this->command;
        $expectedContent = sprintf($command::TEMPLATE_CONFIG_PROVIDER, 'THEApp', 'the-app');
        self::assertSame($expectedContent, $configProviderContent);
    }
}
