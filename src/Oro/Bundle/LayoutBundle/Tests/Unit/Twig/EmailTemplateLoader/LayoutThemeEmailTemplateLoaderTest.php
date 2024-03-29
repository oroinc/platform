<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig\EmailTemplateLoader;

use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\LayoutBundle\Twig\EmailTemplateLoader\LayoutThemeEmailTemplateLoader;
use PHPUnit\Framework\TestCase;
use Twig\Loader\FilesystemLoader;
use Twig\Source;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LayoutThemeEmailTemplateLoaderTest extends TestCase
{
    private LayoutThemeEmailTemplateLoader $loader;

    protected function setUp(): void
    {
        $this->filesystemLoader = $this->createMock(FilesystemLoader::class);

        $this->loader = new LayoutThemeEmailTemplateLoader([], __DIR__);
    }

    public function testGetPaths(): void
    {
        $namespace = 'sample';
        $paths = ['email-templates1', 'email-templates2'];
        $this->loader->setPaths($paths, $namespace);

        self::assertEquals($paths, $this->loader->getPaths($namespace));
    }

    public function testNamespaces(): void
    {
        $namespace1 = 'sample1';
        $namespace2 = 'sample2';

        $this->loader->addPath('email-templates1', $namespace1);
        $this->loader->addPath('email-templates2', $namespace2);

        self::assertEquals([$namespace1, $namespace2], $this->loader->getNamespaces());
    }

    public function testPrependPath(): void
    {
        $namespace = 'sample';
        $path1 = 'email-templates1';
        $path2 = 'email-templates2';

        $this->loader->prependPath($path1, $namespace);
        $this->loader->prependPath($path2, $namespace);

        self::assertEquals([$path2, $path1], $this->loader->getPaths($namespace));
    }

    public function testExistsWhenNotSupportedNamespace(): void
    {
        self::assertFalse($this->loader->exists('@sample_namespace/sample_name.html.twig'));
    }

    public function testExistsWhenEmptyContext(): void
    {
        $templateName = 'sample_name.html.twig';
        $name = '@theme:/' . $templateName;

        self::assertFalse($this->loader->exists($name));
    }

    public function testExistsWhenNotEmptyContext(): void
    {
        $templateName = 'template1.html.twig';
        $name = '@theme:name=sample_theme1/' . $templateName;

        $this->loader->addPath('email-templates1', 'sample_theme1');
        $this->loader->addPath('email-templates2', 'sample_theme2');

        self::assertTrue($this->loader->exists($name));
    }

    public function testGetCacheKeyWhenNotSupportedNamespace(): void
    {
        self::assertEquals('', $this->loader->getCacheKey('@sample_namespace/sample_name.html.twig'));
    }

    public function testGetCacheKeyWhenEmptyContext(): void
    {
        $templateName = 'sample_name.html.twig';
        $name = '@theme:/' . $templateName;

        self::assertEquals('', $this->loader->getCacheKey($name));
    }

    public function testGetCacheKeyWhenNotEmptyContext(): void
    {
        $templateName = 'template1.html.twig';
        $name = '@theme:name=sample_theme1/' . $templateName;

        $this->loader->addPath('email-templates1', 'sample_theme1');
        $this->loader->addPath('email-templates2', 'sample_theme2');

        self::assertEquals('email-templates1/template1.html.twig', $this->loader->getCacheKey($name));
    }

    public function testIsFresh(): void
    {
        $templateName = 'template1.html.twig';
        $name = '@theme:name=sample_theme1/' . $templateName;
        $time = time();

        $this->loader->addPath('email-templates1', 'sample_theme1');
        $this->loader->addPath('email-templates2', 'sample_theme2');

        self::assertTrue($this->loader->isFresh($name, $time));
    }

    public function testGetSourceContext(): void
    {
        $templateName = 'template1.html.twig';
        $name = '@theme:name=sample_theme1/' . $templateName;

        $this->loader->addPath('email-templates1', 'sample_theme1');
        $this->loader->addPath('email-templates2', 'sample_theme2');

        $path = implode(DIRECTORY_SEPARATOR, [__DIR__, 'email-templates1', 'template1.html.twig']);

        self::assertEquals(
            new Source(file_get_contents($path), $name, $path),
            $this->loader->getSourceContext($name)
        );
    }

    public function testGetEmailTemplate(): void
    {
        $templateName = 'template1.html.twig';
        $name = '@theme:name=sample_theme1/' . $templateName;

        $this->loader->addPath('email-templates1', 'sample_theme1');
        $this->loader->addPath('email-templates2', 'sample_theme2');

        $path = implode(DIRECTORY_SEPARATOR, [__DIR__, 'email-templates1', 'template1.html.twig']);

        $emailTemplateModel = EmailTemplateModel::createFromContent(file_get_contents($path));

        self::assertEquals($emailTemplateModel, $this->loader->getEmailTemplate($name));
    }
}
