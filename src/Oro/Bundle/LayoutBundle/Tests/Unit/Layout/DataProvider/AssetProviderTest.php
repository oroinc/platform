<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Asset\Packages;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AssetProvider;

class AssetProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var Packages|\PHPUnit_Framework_MockObject_MockObject */
    protected $packages;

    /** @var AssetProvider */
    protected $provider;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->packages = $this->getMockBuilder('Symfony\Component\Asset\Packages')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new AssetProvider($this->packages);
    }

    /**
     * @param string      $path
     * @param string|null $packageName
     * @param string|null $normalizedPath
     * @param string|null $expected
     *
     * @dataProvider getUrlDataProvider
     */
    public function testGetUrl($path, $packageName, $normalizedPath, $expected)
    {
        $this->packages->expects($this->once())
            ->method('getUrl')
            ->with($normalizedPath, $packageName)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->provider->getUrl($path, $packageName));
    }

    /**
     * @return array
     */
    public function getUrlDataProvider()
    {
        return [
            'with_path_only'             => [
                'path'           => 'path',
                'packageName'    => null,
                'normalizedPath' => 'path',
                'expected'       => 'assets/path',
            ],
            'with_path_and_package_name' => [
                'path'           => 'path',
                'packageName'    => 'package',
                'normalizedPath' => 'path',
                'expected'       => 'assets/path',
            ],
            'with_full_path'             => [
                'path'           => '@AcmeTestBundle/Resources/public/images/Picture.png',
                'packageName'    => null,
                'normalizedPath' => 'bundles/acmetest/images/Picture.png',
                'expected'       => 'assets/bundles/acmetest/images/Picture.png',
            ],
            'with_non_bundle_path'       => [
                'path'           => '@AcmeTestBundle/Resources/public/images/Picture.png',
                'packageName'    => null,
                'normalizedPath' => 'bundles/acmetest/images/Picture.png',
                'expected'       => 'assets/@AcmeTest/Resources/public/images/Picture.png',
            ]
        ];
    }

    public function testGetUrlWithNullPath()
    {
        $this->packages->expects($this->never())
            ->method('getUrl');

        $this->assertNull($this->provider->getUrl(null));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected a string value for the path, got "array".
     */
    public function testAddErrorForInvalidPathType()
    {
        $this->packages->expects($this->never())
            ->method('getUrl');

        $this->provider->getUrl(['test']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected null or a string value for the package name, got "array".
     */
    public function testAddErrorForInvalidPackageNameType()
    {
        $this->packages->expects($this->never())
            ->method('getUrl');

        $this->provider->getUrl('test', ['test']);
    }
}
