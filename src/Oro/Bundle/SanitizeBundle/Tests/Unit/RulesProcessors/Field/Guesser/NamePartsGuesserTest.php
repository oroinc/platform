<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Unit\RulesProcessors\Field\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendEntityMetadataProvider;
use Oro\Bundle\LocaleBundle\Model\FullNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;
use Oro\Bundle\LocaleBundle\Model\MiddleNameInterface;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Guesser\NamePartsGuesser;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Md5Processor;
use PHPUnit\Framework\TestCase;

class NamePartsGuesserTest extends TestCase
{
    private const WRONG_FIELD_NAME = 'not_name_field';

    private ?Md5Processor $md5ProcessorMock = null;
    private ?ClassMetadata $classMetadataMock = null;
    private ?ConfigManager $configManagerMock = null;
    private ?ExtendEntityMetadataProvider $extendEntityMetadataProvider = null;
    private ?ProcessorHelper $processorHelper = null;

    #[\Override]
    protected function setUp(): void
    {
        $this->md5ProcessorMock = $this->createMock(Md5Processor::class);
        $this->classMetadataMock = $this->createMock(ClassMetadata::class);
        $this->configManagerMock = $this->createMock(ConfigManager::class);
        $this->extendEntityMetadataProvider = $this->createMock(ExtendEntityMetadataProvider::class);

        $this->processorHelper = new ProcessorHelper($this->configManagerMock, $this->extendEntityMetadataProvider);
    }

    /**
     * @dataProvider properEntityDataProvider
     */
    public function testSuccessfulGuess(string $fieldName, string $className): void
    {
        $namePartsGuesser = new NamePartsGuesser($this->md5ProcessorMock, $this->processorHelper);

        $this->classMetadataMock->expects(self::exactly(2))
            ->method('getName')
            ->willReturn($className);
        $this->extendEntityMetadataProvider->expects(self::once())
            ->method('getExtendEntityFieldsMetadata')
            ->with($className)
            ->willReturn([$fieldName => [ExtendEntityMetadataProvider::IS_SERIALIZED => false]]);
        $this->classMetadataMock->expects(self::once())
            ->method('getFieldMapping')
            ->with($fieldName)
            ->willReturn(['type' => 'string']);

        self::assertEquals(
            $namePartsGuesser->guessProcessor($fieldName, $this->classMetadataMock),
            $this->md5ProcessorMock
        );
    }

    public function properEntityDataProvider(): array
    {
        return [
            'middle_name_case_1' => ['middleName', MiddleNameInterface::class],
            'middle_name_case_2' => ['middleName', FullNameInterface::class],
            'last_name_case_1' => ['lastName', LastNameInterface::class],
            'last_name_case_2' => ['lastName', FullNameInterface::class],
        ];
    }

    /**
     * @dataProvider properEntityDataProvider
     */
    public function testWrongFieldTypeGuess(string $fieldName, string $className): void
    {
        $namePartsGuesser = new NamePartsGuesser($this->md5ProcessorMock, $this->processorHelper);

        $this->classMetadataMock->expects(self::once())
            ->method('getName')
            ->willReturn($className);
        $this->extendEntityMetadataProvider->expects(self::once())
            ->method('getExtendEntityFieldsMetadata')
            ->with($className)
            ->willReturn([$fieldName => [ExtendEntityMetadataProvider::IS_SERIALIZED => false]]);
        $this->classMetadataMock->expects(self::once())
            ->method('getFieldMapping')
            ->with($fieldName)
            ->willReturn(['type' => 'not_string']);

        self::assertNull($namePartsGuesser->guessProcessor($fieldName, $this->classMetadataMock));
    }

    public function testWrongFieldNameGuess(): void
    {
        $namePartsGuesser = new NamePartsGuesser($this->md5ProcessorMock, $this->processorHelper);

        $this->classMetadataMock->expects(self::once())
            ->method('getName')
            ->willReturn(FullNameInterface::class);
        $this->extendEntityMetadataProvider->expects(self::once())
            ->method('getExtendEntityFieldsMetadata')
            ->with(FullNameInterface::class)
            ->willReturn([self::WRONG_FIELD_NAME => [ExtendEntityMetadataProvider::IS_SERIALIZED => false]]);
        $this->classMetadataMock->expects(self::once())
            ->method('getFieldMapping')
            ->with(self::WRONG_FIELD_NAME)
            ->willReturn(['type' => 'string']);

        self::assertNull($namePartsGuesser->guessProcessor(self::WRONG_FIELD_NAME, $this->classMetadataMock));
    }
}
