<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\FeatureDependedTextProcessor;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class FeatureDependedTextProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureDependedTextProcessor */
    private $processor;

    protected function setUp(): void
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->willReturnCallback(function ($feature) {
                return in_array($feature, ['feature1', 'feature2']);
            });

        $this->processor = new FeatureDependedTextProcessor($featureChecker);
    }

    /**
     * @dataProvider validTextExpressionProvider
     */
    public function testProcess(string $text, string $expected)
    {
        self::assertEquals(
            $expected,
            $this->processor->process($text)
        );
    }

    public function validTextExpressionProvider(): array
    {
        return [
            ['', ''],
            ['Text', 'Text'],
            ['{@feature:feature1}FEATURE 1{@/feature}', 'FEATURE 1'],
            ['{@feature:feature1}FEATURE 2{@/feature}', 'FEATURE 2'],
            ['{@feature:feature3}FEATURE 3{@/feature}', ''],
            ['{@feature:feature1&feature2}FEATURE 1 & FEATURE 2{@/feature}', 'FEATURE 1 & FEATURE 2'],
            ['{@feature:feature1&!feature2}FEATURE 1 & !FEATURE 2{@/feature}', ''],
            ['{@feature:feature1&feature3}FEATURE 1 & FEATURE 3{@/feature}', ''],
            ['{@feature:feature1&!feature3}FEATURE 1 & !FEATURE 3{@/feature}', 'FEATURE 1 & !FEATURE 3'],
            ['{@feature:feature1|feature2}FEATURE 1 | FEATURE 2{@/feature}', 'FEATURE 1 | FEATURE 2'],
            ['{@feature:feature1|!feature2}FEATURE 1 | !FEATURE 2{@/feature}', 'FEATURE 1 | !FEATURE 2'],
            ['{@feature:feature1|feature3}FEATURE 1 | FEATURE 3{@/feature}', 'FEATURE 1 | FEATURE 3'],
            ['{@feature:feature1|!feature3}FEATURE 1 | !FEATURE 3{@/feature}', 'FEATURE 1 | !FEATURE 3'],
            ['Hello {@feature:feature1}FEATURE 1{@/feature}!', 'Hello FEATURE 1!'],
            ['{@feature:feature2}FEATURE 2{@/feature} {@feature:feature1}FEATURE 1{@/feature}', 'FEATURE 2 FEATURE 1']
        ];
    }

    /**
     * @dataProvider invalidTextExpressionProvider
     */
    public function testProcessForInvalidExpression(string $text)
    {
        self::assertEquals(
            $text,
            $this->processor->process($text)
        );
    }

    public function invalidTextExpressionProvider(): array
    {
        return [
            ['{@feature:}FEATURE 1{@/feature}'],
            ['{@feature}FEATURE 1{@/feature}'],
            ['{@featureFEATURE 1{@/feature}'],
            ['{@feature:feature1}FEATURE 1'],
            ['{@feature:feature1}FEATURE 1{@feature}'],
            ['{@feature:feature1}FEATURE 1{@/feature'],
            ['{@feature:feature1}FEATURE 1{/@feature}'],
            ['{@feature:feature1}FEATURE 1@/feature}']
        ];
    }
}
