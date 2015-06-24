<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Twig\FormatExtension;

class FormatExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var FormatExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Oro\Bundle\UIBundle\Formatter\FormatterManager')
            ->getMock();
        $this->extension = new FormatExtension($this->manager);
    }

    public function testGetFilters()
    {
        $this->assertEquals(
            [new \Twig_SimpleFilter('oro_format', [$this->extension, 'format'], ['is_safe' => ['html']])],
            $this->extension->getFilters()
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_formatter_extension', $this->extension->getName());
    }

    public function testFormat()
    {
        $parameter = 'test';
        $formatterName = 'test_format';
        $formatterArguments = [];

        $this->manager
            ->expects($this->once())
            ->method('format')
            ->with($parameter, $formatterName, $formatterArguments);

        $this->extension->format($parameter, $formatterName, $formatterArguments);
    }
}
