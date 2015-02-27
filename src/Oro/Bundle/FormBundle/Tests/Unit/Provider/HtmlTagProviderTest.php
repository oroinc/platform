<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Provider;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;

class HtmlTagProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HtmlTagProvider
     */
    protected $htmlTagProvider;

    protected function setUp()
    {
        $this->htmlTagProvider = new HtmlTagProvider();
    }

    public function testGetAllowedElements()
    {
        $allowedElements = $this->htmlTagProvider->getAllowedElements();

        $this->assertTrue(is_array($allowedElements));
        $this->assertEquals(27, sizeof($allowedElements));
    }
}
