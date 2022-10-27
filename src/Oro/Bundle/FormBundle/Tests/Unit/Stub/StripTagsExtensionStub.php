<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Stub;

use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\TestCase;

class StripTagsExtensionStub extends StripTagsExtension
{
    public function __construct(TestCase $testCase)
    {
        /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject $htmlTagHelper */
        $htmlTagHelper = $testCase->getMockBuilder(HtmlTagHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $htmlTagHelper->expects(FormIntegrationTestCase::any())
            ->method('stripTags')
            ->willReturnCallback(function ($str) {
                return sprintf('%s_stripped', $str);
            });

        parent::__construct(
            TestContainerBuilder::create()
                ->add('oro_ui.html_tag_helper', $htmlTagHelper)
                ->getContainer($testCase)
        );
    }
}
