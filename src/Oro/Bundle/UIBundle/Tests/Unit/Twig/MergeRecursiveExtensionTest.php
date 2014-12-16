<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Twig\MergeRecursiveExtension;
use Twig_SimpleFilter;

class MergeRecursiveExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MergeRecursiveExtension
     */
    private $extension;

    /**
     * Set up test environment
     */
    protected function setUp()
    {
        $this->extension = new MergeRecursiveExtension();
    }

    public function testName()
    {
        $this->assertEquals('oro_ui.merge_recursive', $this->extension->getName());
    }

    public function testSetFilters()
    {
        $filters = [];
        foreach ($this->extension->getFilters() as $filter) {
            /** @var $filter Twig_SimpleFilter */
            $filters[$filter->getName()] = $filter->getCallable();
        }

        $this->assertArrayHasKey('merge_recursive', $filters);
        $this->assertEquals(
            ['Oro\Bundle\UIBundle\Tools\ArrayUtils', 'arrayMergeRecursiveDistinct'],
            $filters['merge_recursive']
        );
    }
}
