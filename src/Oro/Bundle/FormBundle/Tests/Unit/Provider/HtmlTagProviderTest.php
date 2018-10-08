<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Provider;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;

class HtmlTagProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var HtmlTagProvider
     */
    protected $htmlTagProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $elements = [
            'p' => [],
            'span' => [
                'attributes' => ['id']
            ],
            'br' => [
                'hasClosingTag' => false
            ],
        ];

        $this->htmlTagProvider = new HtmlTagProvider($elements);
    }

    public function testGetAllowedElements()
    {
        $allowedElements = $this->htmlTagProvider->getAllowedElements();
        $this->assertEquals(['@[style|class]', 'p', 'span[id]', 'br'], $allowedElements);
    }

    public function testGetAllowedTags()
    {
        $allowedTags = $this->htmlTagProvider->getAllowedTags();
        $this->assertEquals('<p></p><span></span><br>', $allowedTags);
    }
}
