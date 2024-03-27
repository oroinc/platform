<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Unit\RulesProcessors\Field\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendEntityMetadataProvider;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Guesser\CryptedStringGuesser;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Md5Processor;

class CryptedStringGuesserTest extends \PHPUnit\Framework\TestCase
{
    private const DUMMY_CLASS_NAME = 'DummyClass';
    private const DUMMY_FIELD_NAME = 'dummyField';

    private ?Md5Processor $md5ProcessorMock = null;
    private ?ClassMetadata $classMetadataMock = null;
    private ?ConfigManager $configManagerMock = null;
    private ?ExtendEntityMetadataProvider $extendEntityMetadataProvider = null;
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
        $cryptedStringGuesser = new CryptedStringGuesser($this->md5ProcessorMock, $this->processorHelper);

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
            ->willReturn(['type' => 'crypted_string']);

        self::assertEquals(
            $cryptedStringGuesser->guessProcessor(self::DUMMY_FIELD_NAME, $this->classMetadataMock),
            $this->md5ProcessorMock
        );
    }

    public function testWrongTypeGuess(): void
    {
        $cryptedStringGuesser = new CryptedStringGuesser($this->md5ProcessorMock, $this->processorHelper);

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
            ->willReturn(['type' => 'not_crypted_string']);

        self::assertNull($cryptedStringGuesser->guessProcessor(self::DUMMY_FIELD_NAME, $this->classMetadataMock));
    }
}
