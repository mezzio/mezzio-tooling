<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Module;

use Mezzio\Tooling\Module\Create;
use Mezzio\Tooling\Module\RuntimeException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function preg_match;
use function sprintf;

class CreateTest extends TestCase
{
    use PHPMock;

    private Create $command;

    private vfsStreamDirectory $dir;

    private vfsStreamDirectory $modulesDir;

    private string $modulesPath = 'my-modules';

    private string $projectDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir        = vfsStream::setup('project');
        $this->modulesDir = vfsStream::newDirectory($this->modulesPath)->at($this->dir);
        $this->projectDir = vfsStream::url('project');
        $this->command    = new Create();
    }

    public function testErrorsWhenModuleDirectoryAlreadyExists(): void
    {
        vfsStream::newDirectory('MyApp')->at($this->modulesDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Module "MyApp" already exists');
        $this->command->process('MyApp', $this->modulesPath, $this->projectDir);
    }

    public function testErrorsWhenCannotCreateModuleDirectory(): void
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

    public function testErrorsWhenCannotCreateModuleSrcDirectory(): void
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

    public function testErrorsWhenCannotCreateModuleTemplatesDirectory(): void
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

    public function testCreatesConfigProvider(): void
    {
        $configProvider = vfsStream::url('project/my-modules/MyApp/src/ConfigProvider.php');
        $metadata       = $this->command->process('MyApp', $this->modulesPath, $this->projectDir);

        self::assertEquals('my-modules/MyApp', $metadata->rootPath());
        self::assertFileExists($configProvider);
        $configProviderContent = file_get_contents($configProvider);
        self::assertSame(1, preg_match('/\bnamespace MyApp\b/', $configProviderContent));
        self::assertSame(1, preg_match('/\bclass ConfigProvider\b/', $configProviderContent));
        $command         = $this->command;
        $expectedContent = sprintf($command::TEMPLATE_CONFIG_PROVIDER_RECOMMENDED, 'MyApp', 'my-app', '');
        self::assertSame($expectedContent, $configProviderContent);
    }

    public function testModuleTemplatePathNameWithNumber(): void
    {
        $this->command->process('My2App', $this->modulesPath, $this->projectDir);
        $configProvider        = vfsStream::url('project/my-modules/My2App/src/ConfigProvider.php');
        $configProviderContent = file_get_contents($configProvider);
        $command               = $this->command;
        $expectedContent       = sprintf($command::TEMPLATE_CONFIG_PROVIDER_RECOMMENDED, 'My2App', 'my2-app', '');
        self::assertSame($expectedContent, $configProviderContent);
    }

    public function testModuleTemplatePathNameWithSequentialUppercase(): void
    {
        $this->command->process('THEApp', $this->modulesPath, $this->projectDir);
        $configProvider        = vfsStream::url('project/my-modules/THEApp/src/ConfigProvider.php');
        $configProviderContent = file_get_contents($configProvider);
        $command               = $this->command;
        $expectedContent       = sprintf($command::TEMPLATE_CONFIG_PROVIDER_RECOMMENDED, 'THEApp', 'the-app', '');
        self::assertSame($expectedContent, $configProviderContent);
    }

    public function testCanCreateFlatStructureWhenRequested(): void
    {
        $command  = new Create(true);
        $metadata = $command->process('MyApp', $this->modulesPath, $this->projectDir);

        $expectedPaths = 'my-modules/MyApp';
        self::assertEquals($expectedPaths, $metadata->rootPath());
        self::assertEquals($expectedPaths, $metadata->sourcePath());

        $configProvider        = vfsStream::url('project/my-modules/MyApp/ConfigProvider.php');
        $configProviderContent = file_get_contents($configProvider);
        $expectedContent       = sprintf($command::TEMPLATE_CONFIG_PROVIDER_FLAT, 'MyApp', '');
        self::assertSame($expectedContent, $configProviderContent);
    }

    public function testWillCreateRouteDelegatorWhenRequested(): void
    {
        $command  = new Create(false);
        $metadata = $command->process('MyApp', $this->modulesPath, $this->projectDir, true);

        $expectedPath = 'my-modules/MyApp';
        self::assertEquals($expectedPath, $metadata->rootPath());
        self::assertEquals($expectedPath . '/src', $metadata->sourcePath());

        $configProvider        = vfsStream::url('project/my-modules/MyApp/src/ConfigProvider.php');
        $configProviderContent = file_get_contents($configProvider);
        $expectedContent       = sprintf(
            $command::TEMPLATE_CONFIG_PROVIDER_RECOMMENDED,
            'MyApp',
            'my-app',
            Create::TEMPLATE_ROUTE_DELEGATOR_CONFIG
        );
        self::assertSame($expectedContent, $configProviderContent);

        $routesDelegator        = vfsStream::url('project/my-modules/MyApp/src/RoutesDelegator.php');
        $routesDelegatorContent = file_get_contents($routesDelegator);
        $expectedContent        = sprintf(
            $command::TEMPLATE_ROUTE_DELEGATOR,
            'MyApp'
        );
        self::assertSame($expectedContent, $routesDelegatorContent);
    }

    public function testWillCreateRouteDelegatorInFlatStructureWhenRequested(): void
    {
        $command  = new Create(true, true);
        $metadata = $command->process('MyApp', $this->modulesPath, $this->projectDir, true);

        $expectedPath = 'my-modules/MyApp';
        self::assertEquals($expectedPath, $metadata->rootPath());
        self::assertEquals($expectedPath, $metadata->sourcePath());

        $configProvider        = vfsStream::url('project/my-modules/MyApp/ConfigProvider.php');
        $configProviderContent = file_get_contents($configProvider);
        $expectedContent       = sprintf(
            $command::TEMPLATE_CONFIG_PROVIDER_FLAT,
            'MyApp',
            Create::TEMPLATE_ROUTE_DELEGATOR_CONFIG
        );
        self::assertSame($expectedContent, $configProviderContent);

        $routesDelegator        = vfsStream::url('project/my-modules/MyApp/RoutesDelegator.php');
        $routesDelegatorContent = file_get_contents($routesDelegator);
        $expectedContent        = sprintf(
            $command::TEMPLATE_ROUTE_DELEGATOR,
            'MyApp'
        );
        self::assertSame($expectedContent, $routesDelegatorContent);
    }

    public function testCanCreateRecommendedStructureModuleUsingParentNamespace(): void
    {
        $configProvider = vfsStream::url('project/my-modules/MyApp/src/ConfigProvider.php');
        $metadata       = $this->command->process(
            'MyApp',
            $this->modulesPath,
            $this->projectDir,
            false,
            'ParentNamespace'
        );

        self::assertEquals('my-modules/MyApp', $metadata->rootPath());
        self::assertFileExists($configProvider);
        $configProviderContent = file_get_contents($configProvider);
        self::assertSame(1, preg_match('/\bnamespace ParentNamespace\\\\MyApp\b/', $configProviderContent));
        self::assertSame(1, preg_match('/\bclass ConfigProvider\b/', $configProviderContent));
        $command         = $this->command;
        $expectedContent = sprintf(
            $command::TEMPLATE_CONFIG_PROVIDER_RECOMMENDED,
            'ParentNamespace\\MyApp',
            'my-app',
            ''
        );
        self::assertSame($expectedContent, $configProviderContent);
    }

    public function testCanCreateFlatStructureModuleUsingParentNamespace(): void
    {
        $command  = new Create(true);
        $metadata = $command->process('MyApp', $this->modulesPath, $this->projectDir, false, 'ParentNamespace');

        $expectedPaths = 'my-modules/MyApp';
        self::assertEquals($expectedPaths, $metadata->rootPath());
        self::assertEquals($expectedPaths, $metadata->sourcePath());

        $configProvider        = vfsStream::url('project/my-modules/MyApp/ConfigProvider.php');
        $configProviderContent = file_get_contents($configProvider);
        $expectedContent       = sprintf($command::TEMPLATE_CONFIG_PROVIDER_FLAT, 'ParentNamespace\\MyApp', '');
        self::assertSame($expectedContent, $configProviderContent);
    }

    public function testWillCreateRouteDelegatorUsingParentNamespace(): void
    {
        $command  = new Create(false);
        $metadata = $command->process('MyApp', $this->modulesPath, $this->projectDir, true, 'ParentNamespace');

        $expectedPath = 'my-modules/MyApp';
        self::assertEquals($expectedPath, $metadata->rootPath());
        self::assertEquals($expectedPath . '/src', $metadata->sourcePath());

        $configProvider        = vfsStream::url('project/my-modules/MyApp/src/ConfigProvider.php');
        $configProviderContent = file_get_contents($configProvider);
        $expectedContent       = sprintf(
            $command::TEMPLATE_CONFIG_PROVIDER_RECOMMENDED,
            'ParentNamespace\\MyApp',
            'my-app',
            Create::TEMPLATE_ROUTE_DELEGATOR_CONFIG
        );
        self::assertSame($expectedContent, $configProviderContent);

        $routesDelegator        = vfsStream::url('project/my-modules/MyApp/src/RoutesDelegator.php');
        $routesDelegatorContent = file_get_contents($routesDelegator);
        $expectedContent        = sprintf(
            $command::TEMPLATE_ROUTE_DELEGATOR,
            'ParentNamespace\\MyApp'
        );
        self::assertSame($expectedContent, $routesDelegatorContent);
    }

    public function testWillCreateRouteDelegatorInFlatStructureUsingParentNamespace(): void
    {
        $command  = new Create(true, true);
        $metadata = $command->process('MyApp', $this->modulesPath, $this->projectDir, true, 'ParentNamespace');

        $expectedPath = 'my-modules/MyApp';
        self::assertEquals($expectedPath, $metadata->rootPath());
        self::assertEquals($expectedPath, $metadata->sourcePath());

        $configProvider        = vfsStream::url('project/my-modules/MyApp/ConfigProvider.php');
        $configProviderContent = file_get_contents($configProvider);
        $expectedContent       = sprintf(
            $command::TEMPLATE_CONFIG_PROVIDER_FLAT,
            'ParentNamespace\\MyApp',
            Create::TEMPLATE_ROUTE_DELEGATOR_CONFIG
        );
        self::assertSame($expectedContent, $configProviderContent);

        $routesDelegator        = vfsStream::url('project/my-modules/MyApp/RoutesDelegator.php');
        $routesDelegatorContent = file_get_contents($routesDelegator);
        $expectedContent        = sprintf(
            $command::TEMPLATE_ROUTE_DELEGATOR,
            'ParentNamespace\\MyApp'
        );
        self::assertSame($expectedContent, $routesDelegatorContent);
    }
}
