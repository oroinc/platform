<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Oro\Bundle\EntityBundle\Twig\Sandbox\SystemVariablesTemplateProcessor;
use PHPUnit\Framework\TestCase;

class SystemVariablesTemplateProcessorTest extends TestCase
{
    private SystemVariablesTemplateProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->processor = new SystemVariablesTemplateProcessor();
    }

    /**
     * @dataProvider processSystemVariablesDataProvider
     */
    public function testProcessSystemVariables(string $content, string $expectedContent, array $filters): void
    {
        foreach ($filters as $var => $filter) {
            $this->processor->addSystemVariableDefaultFilter($var, $filter);
        }

        self::assertEquals($expectedContent, $this->processor->processSystemVariables($content));
    }

    public function processSystemVariablesDataProvider(): \Generator
    {
        yield 'empty content, no filters' => [
            'content' => '',
            'expectedContent' => '',
            'filters' => [],
        ];

        yield 'empty content' => [
            'content' => '',
            'expectedContent' => '',
            'filters' => ['userSignature' => 'oro_html_sanitize'],
        ];

        yield 'with content, no applicable vars' => [
            'content' => 'sample content',
            'expectedContent' => 'sample content',
            'filters' => ['userSignature' => 'oro_html_sanitize'],
        ];

        yield 'with content, with applicable vars' => [
            'content' => 'sample content with {{ system.userSignature }}',
            'expectedContent' => 'sample content with {{ system.userSignature|oro_html_sanitize }}',
            'filters' => ['userSignature' => 'oro_html_sanitize'],
        ];
    }
}
