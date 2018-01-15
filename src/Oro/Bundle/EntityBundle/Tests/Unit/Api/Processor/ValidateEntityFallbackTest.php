<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Api\Processor\ValidateEntityFallback;

class ValidateEntityFallbackTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityFallbackResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fallbackResolver;

    /**
     * @var ValueNormalizer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $valueNormalizer;

    /**
     * @var ValidateEntityFallback
     */
    protected $processor;

    /**
     * @var CreateContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    protected function setUp()
    {
        $this->fallbackResolver = $this->getMockBuilder(EntityFallbackResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->valueNormalizer = $this->getMockBuilder(ValueNormalizer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->willReturn('entityfieldfallbackvalues');
        $this->processor = new ValidateEntityFallback(
            $this->fallbackResolver,
            $this->valueNormalizer
        );
        $this->context = $this->getMockBuilder(CreateContext::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @param string $mainClass
     * @param array $requestData
     * @dataProvider getIgnoreTestProvider
     */
    public function testProcessShouldBeIgnored($mainClass, $requestData)
    {
        $this->valueNormalizer->expects($this->never())
            ->method('normalizeValue');
        $this->context->expects($this->once())
            ->method('get')
            ->willReturn($mainClass);
        $this->context->expects($this->once())
            ->method('getRequestData')
            ->willReturn($requestData);

        $this->processor->process($this->context);
    }

    public function getIgnoreTestProvider()
    {
        return [
            [null, []],
            [
                'nonExistentClass',
                [
                    JsonApiDoc::INCLUDED => [],
                    JsonApiDoc::DATA => [JsonApiDoc::RELATIONSHIPS => []],
                ],
            ],
            [
                'nonExistentClass',
                [
                    JsonApiDoc::INCLUDED => [],
                    JsonApiDoc::DATA => [JsonApiDoc::RELATIONSHIPS => []],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getErrorOnInvalidIncludedItemProvider
     */
    public function testProcessShouldSetErrorOnInvalidIncludedItem($includedData)
    {
        $initialIncluded = [
            JsonApiDoc::TYPE => 'entityfieldfallbackvalues',
        ];
        $requestData = [
            JsonApiDoc::DATA => [JsonApiDoc::RELATIONSHIPS => []],
            JsonApiDoc::INCLUDED => [array_merge($initialIncluded, $includedData)],
        ];

        $this->context->expects($this->once())
            ->method('get')
            ->willReturn(\stdClass::class);
        $this->context->expects($this->once())
            ->method('getRequestData')
            ->willReturn($requestData);

        $this->context->expects($this->once())
            ->method('addError')
            ->willReturnCallback(
                function ($error) {
                    $this->assertEquals(
                        'Invalid entity fallback value provided for the included value with id \'0\'.'
                        . ' Please provide a correct id, and an attribute section with'
                        . ' either a \'fallback\' identifier, an \'arrayValue\' or \'scalarValue\'',
                        $error->getDetail()
                    );
                }
            );

        $this->processor->process($this->context);
    }

    public function getErrorOnInvalidIncludedItemProvider()
    {
        return [
            [
                [
                    JsonApiDoc::ID => '1',
                    JsonApiDoc::ATTRIBUTES => null,
                ],
            ],
        ];
    }

    /**
     * @dataProvider getValidationErrorTestProvider
     */
    public function testProcessShouldSetErrorOnValidationError(
        $fallbackAttributes,
        $requiredValueField = null,
        $isValid = false
    ) {
        $requestData = [
            JsonApiDoc::DATA => [
                JsonApiDoc::RELATIONSHIPS => [
                    "field1" => [
                        JsonApiDoc::DATA => [
                            JsonApiDoc::TYPE => 'entityfieldfallbackvalues',
                            JsonApiDoc::ID => '1',
                        ],
                    ],
                ],
            ],
            JsonApiDoc::INCLUDED => [
                [
                    JsonApiDoc::TYPE => 'entityfieldfallbackvalues',
                    JsonApiDoc::ID => '1',
                    JsonApiDoc::ATTRIBUTES => $fallbackAttributes,
                ],
            ],
        ];
        $fallbackConfig = [
            'systemConfig' => [],
            'testField' => [],
        ];
        $this->fallbackResolver->expects($this->any())
            ->method('getFallbackConfig')
            ->willReturn($fallbackConfig);
        $this->fallbackResolver->expects($this->any())
            ->method('getRequiredFallbackFieldByType')
            ->willReturn($requiredValueField);
        $this->context->expects($this->once())
            ->method('get')
            ->willReturn(\stdClass::class);
        $this->context->expects($this->once())
            ->method('getRequestData')
            ->willReturn($requestData);

        if (false === $isValid) {
            $this->context->expects($this->once())
                ->method('addError')
                ->willReturnCallback(
                    function ($error) {
                        $this->assertEquals(
                            'Invalid entity fallback value provided for the included value with id \'1\'.'
                            . ' Please provide a correct id, and an attribute section with'
                            . ' either a \'fallback\' identifier, an \'arrayValue\' or \'scalarValue\'',
                            $error->getDetail()
                        );
                    }
                );
        } else {
            $this->context->expects($this->never())->method('addError');
        }

        $this->processor->process($this->context);
    }

    public function getValidationErrorTestProvider()
    {
        return [
            [
                [
                    EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD => null,
                    EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD => null,
                    EntityFieldFallbackValue::FALLBACK_PARENT_FIELD => null,
                ],
            ],
            [
                [
                    EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD => [],
                    EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD => null,
                    EntityFieldFallbackValue::FALLBACK_PARENT_FIELD => null,
                ],
            ],
            [
                [
                    EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD => null,
                    EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD => 'string',
                    EntityFieldFallbackValue::FALLBACK_PARENT_FIELD => null,
                ],
            ],
            [
                [
                    EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD => null,
                    EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD => [],
                    EntityFieldFallbackValue::FALLBACK_PARENT_FIELD => 'systemConfig',
                ],
            ],
            [
                [
                    EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD => null,
                    EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD => null,
                    EntityFieldFallbackValue::FALLBACK_PARENT_FIELD => 'nonExistentFallbackConfig',
                ],
            ],
            [
                [
                    EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD => null,
                    EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD => [],
                    EntityFieldFallbackValue::FALLBACK_PARENT_FIELD => null,
                ],
                EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD,
                false,
            ],
            [
                [
                    EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD => '123',
                    EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD => null,
                    EntityFieldFallbackValue::FALLBACK_PARENT_FIELD => null,
                ],
                EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD,
                false,
            ],
            [
                [
                    EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD => null,
                    EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD => [],
                    EntityFieldFallbackValue::FALLBACK_PARENT_FIELD => null,
                ],
                EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD,
                true,
            ],
            [
                [
                    EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD => '123',
                    EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD => null,
                    EntityFieldFallbackValue::FALLBACK_PARENT_FIELD => null,
                ],
                EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD,
                true,
            ],
            [
                [
                    EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD => null,
                    EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD => null,
                    EntityFieldFallbackValue::FALLBACK_PARENT_FIELD => 'systemConfig',
                ],
                null,
                true,
            ],
        ];
    }
}
