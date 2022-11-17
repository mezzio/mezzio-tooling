<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\CreateHandler;

use ArrayObject;
use Generator;
use Mezzio\LaminasView\LaminasViewRenderer;
use Mezzio\Plates\PlatesRenderer;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Tooling\CreateHandler\CreateTemplate;
use Mezzio\Tooling\CreateHandler\TemplatePathResolutionException;
use Mezzio\Tooling\CreateHandler\UnresolvableRendererException;
use Mezzio\Twig\TwigRenderer;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Test\TestHandler;

use function copy;
use function file_get_contents;
use function file_put_contents;
use function sprintf;
use function strrpos;
use function substr;
use function vsprintf;

/**
 * @runTestsInSeparateProcesses
 */
class CreateTemplateTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var array<string, string>
     */
    private const COMMON_FILES = [
        '/TestAsset/common/PlatesRenderer.php'      => '/src/PlatesRenderer.php',
        '/TestAsset/common/TwigRenderer.php'        => '/src/TwigRenderer.php',
        '/TestAsset/common/LaminasViewRenderer.php' => '/src/LaminasViewRenderer.php',
    ];

    /** @var ObjectProphecy<ContainerInterface> */
    private ObjectProphecy $container;

    private vfsStreamDirectory $dir;

    private string $projectRoot;

    private PlatesRenderer|TwigRenderer|LaminasViewRenderer|null $renderer;

    protected function setUp(): void
    {
        $this->dir         = vfsStream::setup('project');
        $this->projectRoot = vfsStream::url('project');
        $this->container   = $this->prophesize(ContainerInterface::class);
    }

    public function prepareCommonAssets(): void
    {
        foreach (self::COMMON_FILES as $source => $target) {
            copy(__DIR__ . $source, $this->projectRoot . $target);
        }
    }

    public function injectConfigInContainer(bool $configAsArrayObject = false): void
    {
        $configFile = $this->projectRoot . '/config/config.php';
        $config     = include $configFile;

        if ($configAsArrayObject) {
            $config = new ArrayObject($config);
        }

        $this->container->get('config')->willReturn($config);
    }

    public function configType(): Generator
    {
        yield 'array'       => [false];
        yield ArrayObject::class => [true];
    }

    public function injectRendererInContainer(string $renderer): void
    {
        $className  = substr($renderer, strrpos($renderer, '\\') + 1);
        $sourceFile = sprintf('%s/src/%s.php', $this->projectRoot, $className);
        require $sourceFile;
        $this->renderer = new $renderer();
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $this->container->get(TemplateRendererInterface::class)->willReturn($this->renderer);
    }

    public function updateConfigContents(string ...$replacements): void
    {
        $configFile = $this->projectRoot . '/config/config.php';
        $contents   = file_get_contents($configFile);
        $contents   = vsprintf($contents, $replacements);
        file_put_contents($configFile, $contents);
    }

    public function rendererTypes(): array
    {
        return [
            PlatesRenderer::class      => [PlatesRenderer::class, 'phtml'],
            TwigRenderer::class        => [TwigRenderer::class, 'html.twig'],
            LaminasViewRenderer::class => [LaminasViewRenderer::class, 'phtml'],
        ];
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileInExpectedLocationAndWithExpectedSuffixForFlatHierarchy(
        string $rendererType,
        string $extension
    ): void {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot, $this->container->reveal());

        $template = $generator->forHandler(TestHandler::class);
        self::assertSame($this->projectRoot . '/config/../templates/test/test.' . $extension, $template->getPath());
        self::assertSame('test::test', $template->getName());
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileInExpectedLocationAndWithExpectedSuffixForModuleHierarchy(
        string $rendererType,
        string $extension
    ): void {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot, $this->container->reveal());

        $template = $generator->forHandler(TestHandler::class);
        self::assertSame(
            $this->projectRoot . '/config/../src/Test/templates/test.' . $extension,
            $template->getPath()
        );
        self::assertSame('test::test', $template->getName());
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileInDefaultLocationWhenNoTemplatesConfigPresentForFlatHierarchy(
        string $rendererType,
        string $extension
    ): void {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.no-path', $this->projectRoot . '/config/config.php');
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot, $this->container->reveal());

        $template = $generator->forHandler(TestHandler::class);
        self::assertSame($this->projectRoot . '/templates/test/test.' . $extension, $template->getPath());
        self::assertSame('test::test', $template->getName());
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileInDefaultLocationWhenNoTemplatesConfigPresentForModuleHierarchy(
        string $rendererType,
        string $extension
    ): void {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.no-path', $this->projectRoot . '/config/config.php');
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot, $this->container->reveal());

        $template = $generator->forHandler(TestHandler::class);
        self::assertSame($this->projectRoot . '/src/Test/templates/test.' . $extension, $template->getPath());
        self::assertSame('test::test', $template->getName());
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileUsingConfiguredValuesForFlatHierarchy(
        string $rendererType
    ): void {
        $extension = 'custom';
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.custom', $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot, $this->container->reveal());

        $template = $generator->forHandler(TestHandler::class);
        self::assertSame($this->projectRoot . '/config/../view/for-testing/test.' . $extension, $template->getPath());
        self::assertSame('test::test', $template->getName());
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testGeneratesTemplateFileUsingConfiguredValuesForModuleHierarchy(
        string $rendererType
    ): void {
        $extension = 'custom';
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.custom', $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot, $this->container->reveal());

        $template = $generator->forHandler(TestHandler::class);
        self::assertSame($this->projectRoot . '/config/../view/for-testing/test.' . $extension, $template->getPath());
        self::assertSame('test::test', $template->getName());
    }

    /**
     * @dataProvider configType
     */
    public function testGeneratingTemplateWhenRendererServiceNotFoundResultsInException(bool $configAsArrayObject): void
    {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.missing-renderer', $this->projectRoot . '/config/config.php');
        $this->injectConfigInContainer($configAsArrayObject);
        $this->container->has(TemplateRendererInterface::class)->willReturn(false);
        $this->container->get(TemplateRendererInterface::class)->shouldNotBeCalled();

        $generator = new CreateTemplate($this->projectRoot, $this->container->reveal());

        $this->expectException(UnresolvableRendererException::class);
        $this->expectExceptionMessage('inability to detect a service alias');
        $generator->forHandler(TestHandler::class);
    }

    /**
     * @dataProvider configType
     */
    public function testGeneratingTemplateWhenRendererServiceIsNotInWhitelistResultsInException(
        bool $configAsArrayObject
    ): void {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy(
            $this->projectRoot . '/config/config.php.unrecognized-renderer',
            $this->projectRoot . '/config/config.php'
        );
        $this->injectConfigInContainer($configAsArrayObject);
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $this->container->get(TemplateRendererInterface::class)->willReturn($this);

        $generator = new CreateTemplate($this->projectRoot, $this->container->reveal());

        $this->expectException(UnresolvableRendererException::class);
        $this->expectExceptionMessage('unknown template renderer type');
        $generator->forHandler(TestHandler::class);
    }

    public function rendererTypesWithInvalidPathCounts(): iterable
    {
        foreach (['empty-paths'] as $config) {
            foreach ($this->rendererTypes() as $key => $arguments) {
                $arguments[] = sprintf('config.php.%s', $config);
                $name        = sprintf('%s-%s', $key, $config);
                yield $name => $arguments;
            }
        }
    }

    /**
     * @dataProvider rendererTypesWithInvalidPathCounts
     */
    public function testRaisesExceptionWhenConfiguredPathCountIsInvalidForFlatHierarchy(
        string $rendererType,
        string $extension,
        string $configFile
    ): void {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/' . $configFile, $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot, $this->container->reveal());

        $this->expectException(TemplatePathResolutionException::class);
        $generator->forHandler(TestHandler::class);
    }

    /**
     * @dataProvider rendererTypesWithInvalidPathCounts
     */
    public function testRaisesExceptionWhenConfiguredPathCountIsInvalidForModuleHierarchy(
        string $rendererType,
        string $extension,
        string $configFile
    ): void {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        copy($this->projectRoot . '/config/' . $configFile, $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot, $this->container->reveal());

        $this->expectException(TemplatePathResolutionException::class);
        $generator->forHandler(TestHandler::class);
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testCanGenerateTemplateUsingProvidedNamespaceAndNameWhenConfigurationMatchesForFlatHierarchy(
        string $rendererType,
        string $extension
    ): void {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/flat', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.custom-namespace', $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot, $this->container->reveal());

        $template = $generator->generateTemplate(TestHandler::class, 'custom', 'also-custom');
        self::assertSame(
            $this->projectRoot . '/config/../templates/custom/also-custom.' . $extension,
            $template->getPath()
        );
        self::assertSame('custom::also-custom', $template->getName());
    }

    /**
     * @dataProvider rendererTypes
     */
    public function testCanGenerateTemplateUsingProvidedNamespaceAndNameWhenConfigurationMatchesForModuleHierarchy(
        string $rendererType,
        string $extension
    ): void {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.custom-namespace', $this->projectRoot . '/config/config.php');
        $this->updateConfigContents($extension);
        $this->injectConfigInContainer();
        $this->injectRendererInContainer($rendererType);

        $generator = new CreateTemplate($this->projectRoot, $this->container->reveal());

        $template = $generator->generateTemplate(TestHandler::class, 'custom', 'also-custom');
        self::assertSame(
            $this->projectRoot . '/config/../src/Custom/templates/also-custom.' . $extension,
            $template->getPath()
        );
        self::assertSame('custom::also-custom', $template->getName());
    }

    /**
     * @dataProvider configType
     */
    public function testCanGenerateTemplateWithUnrecognizedRendererTypeIfTemplatSuffixIsProvided(
        bool $configAsArrayObject
    ): void {
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/module', $this->dir);
        $this->prepareCommonAssets();
        require $this->projectRoot . '/src/Test/src/TestHandler.php';
        copy($this->projectRoot . '/config/config.php.no-extension', $this->projectRoot . '/config/config.php');
        $this->injectConfigInContainer($configAsArrayObject);
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $this->container->get(TemplateRendererInterface::class)->willReturn($this);

        $generator = new CreateTemplate($this->projectRoot, $this->container->reveal());

        $template = $generator->generateTemplate(TestHandler::class, 'custom', 'also-custom', 'XHTML');
        self::assertSame(
            $this->projectRoot . '/config/../src/Custom/templates/also-custom.XHTML',
            $template->getPath()
        );
        self::assertSame('custom::also-custom', $template->getName());
    }
}
