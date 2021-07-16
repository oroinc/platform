<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\GuesserInterface;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptionsGuesser;
use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverterInterface;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InlineEditColumnOptionsGuesserTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_COLUMN_NAME = 'test_column';
    private const TEST_ENTITY_NAME = 'TestEntityName';

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var ConstraintConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $constraintConverter;

    /** @var GuesserInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerGuesser;

    /** @var ClassMetadataInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validatorMetaData;

    /** @var InlineEditColumnOptionsGuesser */
    private $guesser;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->constraintConverter = $this->createMock(ConstraintConverterInterface::class);
        $this->validatorMetaData = $this->createMock(ClassMetadataInterface::class);
        $this->innerGuesser = $this->createMock(GuesserInterface::class);

        $this->guesser = new InlineEditColumnOptionsGuesser(
            [$this->innerGuesser],
            $this->validator,
            $this->constraintConverter
        );
    }

    /**
     * @dataProvider getColumnOptionsDataProvider
     */
    public function testGetColumnOptions(array $column, string $behaviour, bool $exceptIsEnableInline): void
    {
        $this->validator->expects($this->once())
            ->method('getMetadataFor')
            ->with(self::TEST_ENTITY_NAME)
            ->willReturn($this->validatorMetaData);

        $this->validatorMetaData->expects($this->once())
            ->method('hasPropertyMetadata')
            ->with(self::TEST_COLUMN_NAME)
            ->willReturn(false);

        $this->innerGuesser->expects($this->once())
            ->method('guessColumnOptions')
            ->with(self::TEST_COLUMN_NAME, self::TEST_ENTITY_NAME, $column, $exceptIsEnableInline)
            ->willReturn($column);

        $this->assertSame(
            $column,
            $this->guesser->getColumnOptions(
                self::TEST_COLUMN_NAME,
                self::TEST_ENTITY_NAME,
                $column,
                $behaviour
            )
        );
    }

    public function getColumnOptionsDataProvider(): array
    {
        return [
            'enabled for column with enable_all behavior' => [
                'column' => [
                    Configuration::BASE_CONFIG_KEY => [
                        Configuration::CONFIG_ENABLE_KEY => true,
                    ]
                ],
                'behavior' => Configuration::BEHAVIOUR_ENABLE_ALL_VALUE,
                'exceptIsEnableInline' => true,
            ],
            'enabled for column with enable_selected behavior' => [
                'column' => [
                    Configuration::BASE_CONFIG_KEY => [
                        Configuration::CONFIG_ENABLE_KEY => true,
                    ]
                ],
                'behavior' => Configuration::BEHAVIOUR_ENABLE_SELECTED,
                'exceptIsEnableInline' => true,
            ],
            'disabled for column with enable_all behavior' => [
                'column' => [
                    Configuration::BASE_CONFIG_KEY => [
                        Configuration::CONFIG_ENABLE_KEY => false,
                    ]
                ],
                'behavior' => Configuration::BEHAVIOUR_ENABLE_ALL_VALUE,
                'exceptIsEnableInline' => false,
            ],
            'disabled for column with enable_selected behavior' => [
                'column' => [
                    Configuration::BASE_CONFIG_KEY => [
                        Configuration::CONFIG_ENABLE_KEY => false,
                    ]
                ],
                'behavior' => Configuration::BEHAVIOUR_ENABLE_SELECTED,
                'exceptIsEnableInline' => false,
            ],
            'option not set with enable_all behavior' => [
                'column' => [
                    Configuration::BASE_CONFIG_KEY => []
                ],
                'behavior' => Configuration::BEHAVIOUR_ENABLE_ALL_VALUE,
                'exceptIsEnableInline' => true,
            ],
            'option not set with enable_selected behavior' => [
                'column' => [
                    Configuration::BASE_CONFIG_KEY => []
                ],
                'behavior' => Configuration::BEHAVIOUR_ENABLE_SELECTED,
                'exceptIsEnableInline' => false,
            ],
        ];
    }
}
