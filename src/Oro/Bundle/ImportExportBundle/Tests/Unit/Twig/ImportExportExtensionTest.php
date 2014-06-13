<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Twig;

use Oro\Bundle\ImportExportBundle\Twig\ImportExportExtension;

class ImportExportExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $templateFixtureRegistry;

    /**
     * @var ImportExportExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->templateFixtureRegistry = $this
            ->getMockBuilder('Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new ImportExportExtension($this->templateFixtureRegistry);
    }

    public function testGetName()
    {
        $this->assertEquals(ImportExportExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(1, $functions);
        $this->assertInstanceOf('\Twig_SimpleFunction', $functions[0]);
    }

    public function testHasTemplateFixture()
    {
        $entityName = 'stdClass';
        $this->templateFixtureRegistry->expects($this->once())
            ->method('hasEntityFixture')
            ->with($entityName)
            ->will($this->returnValue(true));
        $this->assertTrue($this->extension->hasTemplateFixture($entityName));
    }
}
