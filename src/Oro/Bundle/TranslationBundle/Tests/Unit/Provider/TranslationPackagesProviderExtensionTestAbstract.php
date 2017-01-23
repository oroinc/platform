<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\TranslationPackagesProviderExtensionInterface;

abstract class TranslationPackagesProviderExtensionTestAbstract extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationPackagesProviderExtensionInterface */
    protected $extension;
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->extension = $this->createExtension();
    }

    /**
     * @dataProvider packagePathProvider
     *
     * @param string $path
     */
    public function testGetPackagePaths($path)
    {
        $fileLocator = $this->extension->getPackagePaths();
        $this->assertNotEmpty($fileLocator->locate($path));
    }

    public function testGetPackageNames()
    {
        $this->assertEquals(
            $this->getPackagesName(),
            $this->extension->getPackageNames()
        );
    }

    /**
     * @return TranslationPackagesProviderExtensionInterface
     */
    abstract protected function createExtension();

    /**
     * @return array
     */
    abstract protected function getPackagesName();

    /**
     * @return array
     */
    abstract public function packagePathProvider();
}
