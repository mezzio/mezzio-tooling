<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Composer;

use Mezzio\Tooling\Composer\FileSystemBasedComposerPackage;
use PHPUnit\Framework\TestCase;

class FileSystemBasedComposerPackageTest extends TestCase
{
    public function setUp(): void
    {
        $this->tearDownAssets();
    }

    public function tearDown(): void
    {
        $this->tearDownAssets();
    }

    public function tearDownAssets(): void
    {
        $assetDirectories = [
            __DIR__ . '/TestAsset/rule-exists',
            __DIR__ . '/TestAsset/rule-does-not-exist',
            __DIR__ . '/TestAsset/composer-file-does-not-exist',
        ];

        foreach ($assetDirectories as $dir) {
            $file = $dir . '/composer.json';
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function copyDistAsset(string $path): string
    {
        $path   = __DIR__ . '/TestAsset/' . $path;
        $source = $path . '/composer.json.dist';
        $target = $path . '/composer.json';
        if (! file_exists($source)) {
            return $path;
        }

        copy($source, $target);
        return $path;
    }

    public function getComposerJson(string $filename): array
    {
        $json = file_get_contents($filename);
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function assertAutoloadRuleExists(
        string $namespace,
        string $path,
        bool $isDev,
        string $composerJsonFile,
        ?string $message = null,
        bool $provideComposerDetails = false
    ): void {
        $namespace = rtrim($namespace, '\\') . '\\';
        $path      = rtrim($path, '/') . '/';
        $package   = $this->getComposerJson($composerJsonFile);
        $key       = $isDev ? 'autoload-dev' : 'autoload';

        $value     = $package[$key]['psr-4'][$namespace] ?? null;
        $message   = $message ?: sprintf(
            'Expected to find path "%s" registered for namespace "%s"; received "%s"',
            $path,
            $namespace,
            var_export($value, true)
        );

        if ($provideComposerDetails) {
            $message .= "\n" . var_export($package, true);
        }

        self::assertSame($path, $value, $message);
    }

    public function assertAutoloadRuleDoesNotExist(
        string $namespace,
        bool $isDev,
        string $composerJsonFile,
        ?string $message = null,
        bool $provideComposerDetails = false
    ): void {
        $namespace = rtrim($namespace, '\\') . '\\';
        $package   = $this->getComposerJson($composerJsonFile);
        $key       = $isDev ? 'autoload-dev' : 'autoload';

        $message   = $message ?: sprintf(
            'Did NOT expect to find "%s" rule registered for namespace "%s"; received "%s"',
            $key,
            $namespace,
            var_export($package[$key]['psr-4'][$namespace] ?? '', true)
        );

        if ($provideComposerDetails) {
            $message .= "\n" . var_export($package, true);
        }

        if (! isset($package[$key]['psr-4'])) {
            return;
        }

        self::assertArrayNotHasKey($namespace, $package[$key]['psr-4'], $message);
    }

    public function addRuleProvider(): array
    {
        return [
            'production rule for recommended structure' => [false, 'TestModule', 'src/TestModule/src'],
            'dev rule for recommended structure'        => [true,  'TestModule', 'src/TestModule/src'],
            'production rule for flat structure'        => [false, 'TestModule', 'src/TestModule'],
            'dev rule for flat structure'               => [true,  'TestModule', 'src/TestModule'],
        ];
    }

    /** @dataProvider addRuleProvider */
    public function testCanAddRule(bool $isDev, string $module, string $moduleSourcePath): void
    {
        $projectRoot = $this->copyDistAsset('rule-does-not-exist');
        $package     = new FileSystemBasedComposerPackage($projectRoot);
        $package->addPsr4AutoloadRule($module, $moduleSourcePath, $isDev);

        $this->assertAutoloadRuleExists($module, $moduleSourcePath, $isDev, $projectRoot . '/composer.json');
    }

    public function removeRuleProvider(): array
    {
        return [
            'production rule' => [false, 'TestModule', 'rule-exists', 'autoload'],
            'dev rule'        => [true,  'TestModule', 'rule-exists-dev', 'autoload-dev'],
        ];
    }

    /** @dataProvider removeRuleProvider */
    public function testCanRemoveRule(bool $isDev, string $module, string $assetDir, string $autoloadKey): void
    {
        $projectRoot = $this->copyDistAsset($assetDir);
        $package     = new FileSystemBasedComposerPackage($projectRoot);
        $package->removePsr4AutoloadRule($module, $isDev);

        $this->assertAutoloadRuleDoesNotExist($module, $isDev, $projectRoot . '/composer.json');
    }

    public function testDoesNotAddRuleIfRuleExists(): void
    {
        $module      = 'TestModule';
        $projectRoot = $this->copyDistAsset('rule-exists');
        $package     = new FileSystemBasedComposerPackage($projectRoot);
        $package->addPsr4AutoloadRule($module, 'library/modules/TestModule');

        $this->assertAutoloadRuleExists($module, 'src/TestModule/', false, $projectRoot . '/composer.json');
    }

    public function testRemovalDoesNotChangePackageIfRuleDoesNotExist(): void
    {
        $module      = 'TestUncreatedModule';
        $projectRoot = $this->copyDistAsset('rule-exists');
        $package     = new FileSystemBasedComposerPackage($projectRoot);
        $package->removePsr4AutoloadRule($module);

        $this->assertAutoloadRuleExists('TestModule', 'src/TestModule/', false, $projectRoot . '/composer.json');
        $this->assertAutoloadRuleDoesNotExist($module, false, $projectRoot . '/composer.json');
    }
}
