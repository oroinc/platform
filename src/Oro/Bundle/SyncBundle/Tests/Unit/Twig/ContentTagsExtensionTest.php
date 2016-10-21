<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Twig;

use Oro\Bundle\SyncBundle\Content\TagGeneratorChain;
use Oro\Bundle\SyncBundle\Twig\ContentTagsExtension;

class ContentTagsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TagGeneratorChain
     */
    private $service;

    /**
     * @var ContentTagsExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->service   = $this->getMock('Oro\Bundle\SyncBundle\Content\TagGeneratorChain');
        $this->extension = new ContentTagsExtension($this->service);
    }

    protected function tearDown()
    {
        unset($this->service, $this->extension);
    }

    public function testFunctionDeclaration()
    {
        $functions = $this->extension->getFunctions();
        $required  = ['oro_sync_get_content_tags'];
        $defined   = [];
        foreach ($functions as $function) {
            if ($function instanceof \Twig_SimpleFunction && in_array($function->getName(), $required)) {
                $defined[] = $function->getName();
            }
        }

        $this->assertCount(0, array_diff($defined, $required), 'Required functions are not defined');
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
