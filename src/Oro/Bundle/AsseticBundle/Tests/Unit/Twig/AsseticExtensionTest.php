<?php
namespace Oro\Bundle\AsseticBundle\Tests\Unit\Twig;

use Oro\Bundle\AsseticBundle\Twig\AsseticExtension;

class AsseticExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $assetsConfiguration;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $assetFactory;

    /**
     * @var AsseticExtension
     */
    private $extension;

    public function setUp()
    {
        $this->assetsConfiguration = $this->getMockBuilder('Oro\Bundle\AsseticBundle\AssetsConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assetFactory = $this->getMockBuilder('Symfony\Bundle\AsseticBundle\Factory\AssetFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new AsseticExtension(
            $this->assetsConfiguration,
            $this->assetFactory
        );
    }

    public function testGetTokenParsers()
    {
        $parsers = $this->extension->getTokenParsers();
        $this->assertInternalType('array', $parsers);
        $this->assertCount(1, $parsers);
        $this->assertInstanceOf('Oro\Bundle\AsseticBundle\Twig\AsseticTokenParser', $parsers[0]);
        $this->assertEquals('oro_css', $parsers[0]->getTag());
        $this->assertAttributeSame($this->assetsConfiguration, 'assetsConfiguration', $parsers[0]);
        $this->assertAttributeSame($this->assetFactory, 'assetFactory', $parsers[0]);
        $this->assertAttributeSame('css/*.css', 'output', $parsers[0]);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_assetic', $this->extension->getName());
    }
}
