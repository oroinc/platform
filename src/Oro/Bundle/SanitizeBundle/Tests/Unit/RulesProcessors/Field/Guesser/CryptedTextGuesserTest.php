<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Unit\RulesProcessors\Field\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendEntityMetadataProvider;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Guesser\CryptedTextGuesser;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Md5Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CryptedTextGuesserTest extends TestCase
{
    private const DUMMY_CLASS_NAME = 'DummyClass';
    private const DUMMY_FIELD_NAME = 'dummyField';

    private Md5Processor&MockObject $md5ProcessorMock;
    private ClassMetadata&MockObject $classMetadataMock;
    private ConfigManager&MockObject $configManagerMock;
    private ExtendEntityMetadataProvider&MockObject $extendEntityMetadataProvider;
    private ?ProcessorHelper $processorHelper = null;

    protected function setUp(): void
    {
        $this->md5ProcessorMock = self::createMock(Md5Processor::class);
        $this->classMetadataMock = self::createMock(ClassMetadata::class);
        $this->configManagerMock = self::createMock(ConfigManager::class);
        $this->extendEntityMetadataProvider = self::createMock(ExtendEntityMetadataProvider::class);
        $this->processorHelper = new ProcessorHelper($this->configManagerMock, $this->extendEntityMetadataProvider);
    }

    public function testSuccessfulGuess(): void
    {
        $cryptedTextGuesser = new CryptedTextGuesser($this->md5ProcessorMock, $this->processorHelper);

        $this->classMetadataMock
            ->expects(self::once())
            ->method('getName')
            ->willReturn(self::DUMMY_CLASS_NAME);
        $this->extendEntityMetadataProvider
            ->expects(self::once())
            ->method('getExtendEntityFieldsMetadata')
            ->with(self::DUMMY_CLASS_NAME)
            ->willReturn([self::DUMMY_FIELD_NAME => [ExtendEntityMetadataProvider::IS_SERIALIZED => false]]);
        $this->classMetadataMock
            ->expects(self::once())
            ->method('getFieldMapping')
            ->with(self::DUMMY_FIELD_NAME)
            ->willReturn(['type' => 'crypted_text']);

        self::assertEquals(
            $cryptedTextGuesser->guessProcessor(self::DUMMY_FIELD_NAME, $this->classMetadataMock),
            $this->md5ProcessorMock
        );
    }

    public function testWrongTypeGuess(): void
    {
        $cryptedTextGuesser = new CryptedTextGuesser($this->md5ProcessorMock, $this->processorHelper);

        $this->classMetadataMock
            ->expects(self::once())
            ->method('getName')
            ->willReturn(self::DUMMY_CLASS_NAME);
        $this->extendEntityMetadataProvider
            ->expects(self::once())
            ->method('getExtendEntityFieldsMetadata')
            ->with(self::DUMMY_CLASS_NAME)
            ->willReturn([self::DUMMY_FIELD_NAME => [ExtendEntityMetadataProvider::IS_SERIALIZED => false]]);
        $this->classMetadataMock
            ->expects(self::once())
            ->method('getFieldMapping')
            ->with(self::DUMMY_FIELD_NAME)
            ->willReturn(['type' => 'not_crypted_text']);

        self::assertNull($cryptedTextGuesser->guessProcessor(self::DUMMY_FIELD_NAME, $this->classMetadataMock));
    }
}
