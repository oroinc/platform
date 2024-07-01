<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Unit\RulesProcessors\Field\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendEntityMetadataProvider;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\EmailProcessor;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Guesser\EmailGuesser;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;
use PHPUnit\Framework\TestCase;

class EmailGuesserTest extends TestCase
{
    private const DUMMY_CLASS_NAME = 'DummyClass';

    private ?EmailProcessor $emailProcessorMock = null;
    private ?ClassMetadata $classMetadataMock = null;
    private ?ConfigManager $configManagerMock = null;
    private ?ExtendEntityMetadataProvider $extendEntityMetadataProvider = null;
    private ?ProcessorHelper $processorHelper = null;

    protected function setUp(): void
    {
        $this->emailProcessorMock = self::createMock(EmailProcessor::class);
        $this->classMetadataMock = self::createMock(ClassMetadata::class);
        $this->configManagerMock = self::createMock(ConfigManager::class);
        $this->extendEntityMetadataProvider = self::createMock(ExtendEntityMetadataProvider::class);
        $this->processorHelper = new ProcessorHelper($this->configManagerMock, $this->extendEntityMetadataProvider);
    }

    /**
     * @dataProvider properEmailFieldNamesProvider
     */
    public function testMatchedRegularFieldGuess(string $fieldName): void
    {
        $emailGuesser = new EmailGuesser($this->emailProcessorMock, $this->processorHelper);

        $this->classMetadataMock
            ->expects(self::once())
            ->method('getName')
            ->willReturn(self::DUMMY_CLASS_NAME);
        $this->extendEntityMetadataProvider
            ->expects(self::once())
            ->method('getExtendEntityFieldsMetadata')
            ->with(self::DUMMY_CLASS_NAME)
            ->willReturn([$fieldName => [ExtendEntityMetadataProvider::IS_SERIALIZED => false]]);
        $this->classMetadataMock
            ->expects(self::once())
            ->method('getFieldMapping')
            ->with($fieldName)
            ->willReturn(['type' => 'string']);

        self::assertEquals(
            $emailGuesser->guessProcessor($fieldName, $this->classMetadataMock),
            $this->emailProcessorMock
        );
    }

    /**
     * @dataProvider properEmailFieldNamesProvider
     */
    public function testMatchedSerializedFieldGuess(string $fieldName): void
    {
        $emailGuesser = new EmailGuesser($this->emailProcessorMock, $this->processorHelper);

        $this->classMetadataMock
            ->expects(self::exactly(2))
            ->method('getName')
            ->willReturn(self::DUMMY_CLASS_NAME);
        $this->extendEntityMetadataProvider
            ->expects(self::exactly(2))
            ->method('getExtendEntityFieldsMetadata')
            ->with(self::DUMMY_CLASS_NAME)
            ->willReturn([$fieldName => [
                ExtendEntityMetadataProvider::IS_SERIALIZED => true,
                ExtendEntityMetadataProvider::FIELD_TYPE => 'string'
            ]]);
        $this->classMetadataMock
            ->expects(self::never())
            ->method('getFieldMapping');

        self::assertEquals(
            $emailGuesser->guessProcessor($fieldName, $this->classMetadataMock),
            $this->emailProcessorMock
        );
    }

    public function properEmailFieldNamesProvider(): array
    {
        return [
            'email_simple_fieldname' => ['email'],
            'email_simple_fieldname_capital' => ['Email'],
            'email_fieldname_with_underscore_case_1' => ['email_second'],
            'email_fieldname_with_underscore_case_2' => ['third_email_lowercase'],
            'email_camel_case_1' => ['secondEmail'],
            'email_camel_case_1' => ['emailThird'],
            'email_camel_case_1' => ['fourthEmailName'],
        ];
    }

    /**
     * @dataProvider wrongFieldNameProvider
     */
    public function testNotMatchedFieldGuess(string $fieldName): void
    {
        $emailGuesser = new EmailGuesser($this->emailProcessorMock, $this->processorHelper);

        $this->classMetadataMock
            ->expects(self::never())
            ->method('getName');
        $this->extendEntityMetadataProvider
            ->expects(self::never())
            ->method('getExtendEntityFieldsMetadata');
        $this->classMetadataMock
            ->expects(self::never())
            ->method('getFieldMapping');

        self::assertNull($emailGuesser->guessProcessor($fieldName, $this->classMetadataMock));
    }

    public function wrongFieldNameProvider(): array
    {
        return [
            'non_captilized_email_word_case1' => ['firstemail'],
            'non_captilized_email_word_case2' => ['emailsecond'],
            'non_captilized_email_word_case3' => ['thirdemailname'],
            'improper_case_1' => ['mail'],
            'improper_case_2' => ['first_mail'],
            'improper_case_3' => ['second_mail_name'],
            'improper_case_4' => ['mail_third'],
            'improper_case_5' => ['mailFourth'],
            'non_capitalized_afer_email' => ['FifthEmailname']
        ];
    }

    /**
     * @dataProvider properEmailFieldNamesProvider
     */
    public function testWrongTypeForMatchedRegularFieldGuess(string $fieldName): void
    {
        $emailGuesser = new EmailGuesser($this->emailProcessorMock, $this->processorHelper);

        $this->classMetadataMock
            ->expects(self::once())
            ->method('getName')
            ->willReturn(self::DUMMY_CLASS_NAME);
        $this->extendEntityMetadataProvider
            ->expects(self::once())
            ->method('getExtendEntityFieldsMetadata')
            ->with(self::DUMMY_CLASS_NAME)
            ->willReturn([$fieldName => [ExtendEntityMetadataProvider::IS_SERIALIZED => false]]);
        $this->classMetadataMock
            ->expects(self::once())
            ->method('getFieldMapping')
            ->with($fieldName)
            ->willReturn(['type' => 'not_string']);

        self::assertNull($emailGuesser->guessProcessor($fieldName, $this->classMetadataMock));
    }
}
