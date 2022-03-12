<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Stub;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use PHPUnit\Framework\TestCase;

class TooltipFormExtensionStub extends TooltipFormExtension
{
    public function __construct(TestCase $testCase)
    {
        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider */
        $configProvider = $testCase->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Translator|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $testCase->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        parent::__construct($configProvider, $translator);
    }
}
