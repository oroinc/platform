<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Stub;

use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class StripTagsExtensionStub extends StripTagsExtension
{
    /**
     * {@inheritDoc}
     */
    public function __construct(\PHPUnit\Framework\MockObject\MockObject $htmlTagHelper)
    {
        $htmlTagHelper->expects(FormIntegrationTestCase::any())
            ->method('stripTags')
            ->willReturnCallback(function ($str) {
                return sprintf('%s_stripped', $str);
            });
        /** @var HtmlTagHelper $htmlTagHelper */
        parent::__construct($htmlTagHelper);
    }
}
