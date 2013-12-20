<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Oro\Bundle\NavigationBundle\Content\TagGeneratorContext;
use Oro\Bundle\NavigationBundle\Twig\ContentTagsExtension;

class ContentTagsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TagGeneratorContext
     */
    private $service;

    /**
     * @var ContentTagsExtension
     */
    private $extension;

    public function setUp()
    {
        $this->service   = $this->getMock('Oro\Bundle\NavigationBundle\Content\TagGeneratorContext');
        $this->extension = new ContentTagsExtension($this->service);
    }

    public function tearDown()
    {
        unset($this->service, $this->extension);
    }

    public function testFunctionDeclaration()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('oro_navigation_get_content_tags', $functions);
        $this->assertContainsOnlyInstancesOf('\Twig_Function', $functions);
    }

    public function testNameConfigured()
    {
        $this->assertInternalType('string', $this->extension->getName());
    }

    public function testGenerate()
    {
        $data = 'string';
        $tags = ['string_tag'];

        $this->service->expects($this->once())->method('generate')
            ->with($this->equalTo($data), $this->equalTo(false))
            ->will($this->returnValue($tags));

        $this->assertSame($tags, $this->extension->generate($data), 'Should return exact value from service');
    }
}
