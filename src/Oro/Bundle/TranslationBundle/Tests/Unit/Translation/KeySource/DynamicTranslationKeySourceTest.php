<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation\KeySource;

use Oro\Bundle\TranslationBundle\Translation\KeySource\DynamicTranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;

class DynamicTranslationKeySourceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DynamicTranslationKeySource
     */
    private $dynamicSource;

    protected function setUp()
    {
        $this->dynamicSource = new DynamicTranslationKeySource();
    }

    public function testNonConfiguredCalls()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Can\'t build source without template. Please configure source by ->configure($template) method.'
        );

        $this->dynamicSource->getTemplate();
    }

    public function testMergedConfiguredData()
    {
        $dynamicSource = new DynamicTranslationKeySource(['a' => 1, 'c' => 42]);
        $template = $this->createMock(TranslationKeyTemplateInterface::class);
        $template->expects($this->once())->method('getRequiredKeys')->willReturn([]);
        $dynamicSource->configure($template, ['b' => 2, 'a' => 3]);

        $this->assertEquals(
            [
                'c' => 42,
                'b' => 2,
                'a' => 3
            ],
            $dynamicSource->getData()
        );
    }

    public function testConstructorDataValidationFailure()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected not empty value for key "some_key" in data, null given for template '
        );

        $templateMock = $this->createMock(TranslationKeyTemplateInterface::class);
        $templateMock->expects($this->once())->method('getRequiredKeys')->willReturn(['some_key']);
        $this->dynamicSource->configure($templateMock, ['some_other_key' => 'someValue']);
    }

    public function testGetTemplate()
    {
        $template = $this->createMock(TranslationKeyTemplateInterface::class);
        $template->expects($this->once())->method('getRequiredKeys')->willReturn([]);
        $template->expects($this->once())->method('getTemplate')->willReturn('template string');
        $this->dynamicSource->configure($template);
        $this->assertEquals('template string', $this->dynamicSource->getTemplate());
    }
}
