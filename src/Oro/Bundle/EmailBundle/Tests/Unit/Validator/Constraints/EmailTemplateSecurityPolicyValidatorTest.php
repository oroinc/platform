<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\TranslatedEmailTemplateProvider;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\EmailTemplateSecurityPolicyCheckerInterface;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyFilterViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyFunctionViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyMethodViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyPropertyViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyTagViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyViolationInterface;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateSecurityPolicy;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateSecurityPolicyValidator;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\Localization as LocalizationStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\SyntaxError;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class EmailTemplateSecurityPolicyValidatorTest extends ConstraintValidatorTestCase
{
    private EmailTemplateSecurityPolicyCheckerInterface&MockObject $securityPolicyChecker;
    private TranslatedEmailTemplateProvider&MockObject $translatedEmailTemplateProvider;
    private LocalizationManager&MockObject $localizationManager;
    private ConfigProvider&MockObject $entityConfigProvider;
    private TranslatorInterface&MockObject $translator;

    #[\Override]
    protected function createValidator(): ConstraintValidatorInterface
    {
        $this->securityPolicyChecker = $this->createMock(EmailTemplateSecurityPolicyCheckerInterface::class);
        $this->translatedEmailTemplateProvider = $this->createMock(TranslatedEmailTemplateProvider::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        return new EmailTemplateSecurityPolicyValidator(
            $this->securityPolicyChecker,
            $this->translatedEmailTemplateProvider,
            $this->localizationManager,
            $this->entityConfigProvider,
            $this->translator
        );
    }

    public function testValidateThrowsExceptionForUnsupportedConstraintType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new EmailTemplate(), $this->createMock(Constraint::class));
    }

    public function testValidateThrowsExceptionForUnsupportedValueType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('not-an-email-template', new EmailTemplateSecurityPolicy());
    }

    public function testValidateDoesNothingWhenValueIsNull(): void
    {
        $this->validator->validate(null, new EmailTemplateSecurityPolicy());
        $this->assertNoViolation();
    }

    public function testValidateAcceptsPlainEmailTemplateModelAndSkipsTranslationValidation(): void
    {
        $emailTemplateModel = new EmailTemplateModel('plain_model_template');

        $this->localizationManager
            ->expects(self::once())
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->securityPolicyChecker
            ->expects(self::once())
            ->method('checkSecurityPolicy')
            ->with($emailTemplateModel)
            ->willReturn([]);

        $this->translatedEmailTemplateProvider
            ->expects(self::never())
            ->method('getTranslatedEmailTemplate');

        $this->validator->validate($emailTemplateModel, new EmailTemplateSecurityPolicy());
        $this->assertNoViolation();
    }

    public function testValidateProducesNoViolationsWhenCheckerReturnsNoneAndNoTranslations(): void
    {
        $emailTemplate = new EmailTemplate();

        $this->localizationManager
            ->expects(self::once())
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->securityPolicyChecker
            ->expects(self::once())
            ->method('checkSecurityPolicy')
            ->with($emailTemplate)
            ->willReturn([]);

        $this->validator->validate($emailTemplate, new EmailTemplateSecurityPolicy());
        $this->assertNoViolation();
    }

    public function testValidateSwallowsSyntaxErrorThrownByCheckerForDefaultTemplate(): void
    {
        $this->localizationManager
            ->expects(self::once())
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->securityPolicyChecker
            ->expects(self::once())
            ->method('checkSecurityPolicy')
            ->willThrowException(new SyntaxError('Twig syntax error'));

        $this->validator->validate(new EmailTemplate(), new EmailTemplateSecurityPolicy());
        $this->assertNoViolation();
    }

    /**
     * @dataProvider violationTypeToMessageAndCodeProvider
     */
    public function testValidateMapsViolationTypeToCorrectMessageAndCode(
        EmailTemplateSecurityPolicyViolationInterface $violation,
        string $expectedMessage,
        string $expectedErrorCode,
        array $expectedParameters,
    ): void {
        $this->localizationManager
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->securityPolicyChecker
            ->expects(self::once())
            ->method('checkSecurityPolicy')
            ->willReturn([$violation]);

        $this->entityConfigProvider
            ->method('hasConfig')
            ->willReturn(false);

        $this->validator->validate(new EmailTemplate(), new EmailTemplateSecurityPolicy());

        $this->buildViolation($expectedMessage)
            ->setParameters($expectedParameters)
            ->setCode($expectedErrorCode)
            ->setCause($violation)
            ->assertRaised();
    }

    public static function violationTypeToMessageAndCodeProvider(): iterable
    {
        $cause = new \RuntimeException('test cause');

        yield 'tag violation maps to tag message and not-allowed-tag error code' => [
            'violation' => new EmailTemplateSecurityPolicyTagViolation(
                name: 'block',
                templateLine: 1,
                cause: $cause,
                templateField: 'subject'
            ),
            'expectedMessage' => 'oro.email.validator.security_policy.disallowed_tag',
            'expectedErrorCode' => EmailTemplateSecurityPolicy::NOT_ALLOWED_TAG_ERROR,
            'expectedParameters' => [
                '{{ field }}' => 'subject',
                '{{ locale }}' => '',
                '{{ name }}' => 'block',
                '{{ variable }}' => null,
            ],
        ];

        yield 'filter violation maps to filter message and not-allowed-filter error code' => [
            'violation' => new EmailTemplateSecurityPolicyFilterViolation(
                name: 'raw',
                templateLine: 2,
                cause: $cause,
                templateField: 'subject'
            ),
            'expectedMessage' => 'oro.email.validator.security_policy.disallowed_filter',
            'expectedErrorCode' => EmailTemplateSecurityPolicy::NOT_ALLOWED_FILTER_ERROR,
            'expectedParameters' => [
                '{{ field }}' => 'subject',
                '{{ locale }}' => '',
                '{{ name }}' => 'raw',
                '{{ variable }}' => null,
            ],
        ];

        yield 'function violation maps to function message and not-allowed-function error code' => [
            'violation' => new EmailTemplateSecurityPolicyFunctionViolation(
                name: 'constant',
                templateLine: 3,
                cause: $cause,
                templateField: 'content'
            ),
            'expectedMessage' => 'oro.email.validator.security_policy.disallowed_function',
            'expectedErrorCode' => EmailTemplateSecurityPolicy::NOT_ALLOWED_FUNCTION_ERROR,
            'expectedParameters' => [
                '{{ field }}' => 'content',
                '{{ locale }}' => '',
                '{{ name }}' => 'constant',
                '{{ variable }}' => null,
            ],
        ];

        yield 'property violation maps to property message and not-allowed-property error code' => [
            'violation' => new EmailTemplateSecurityPolicyPropertyViolation(
                name: 'secret',
                variableName: 'entity',
                entityClass: \stdClass::class,
                templateLine: 4,
                cause: $cause,
                templateField: 'content'
            ),
            'expectedMessage' => 'oro.email.validator.security_policy.disallowed_property',
            'expectedErrorCode' => EmailTemplateSecurityPolicy::NOT_ALLOWED_PROPERTY_ERROR,
            'expectedParameters' => [
                '{{ field }}' => 'content',
                '{{ locale }}' => '',
                '{{ name }}' => 'secret',
                '{{ variable }}' => 'entity',
            ],
        ];

        yield 'method violation maps to method message and not-allowed-method error code' => [
            'violation' => new EmailTemplateSecurityPolicyMethodViolation(
                name: 'dangerousMethod',
                variableName: 'entity',
                entityClass: \stdClass::class,
                templateLine: 5,
                cause: $cause,
                templateField: 'content'
            ),
            'expectedMessage' => 'oro.email.validator.security_policy.disallowed_method',
            'expectedErrorCode' => EmailTemplateSecurityPolicy::NOT_ALLOWED_METHOD_ERROR,
            'expectedParameters' => [
                '{{ field }}' => 'content',
                '{{ locale }}' => '',
                '{{ name }}' => 'dangerousMethod',
                '{{ variable }}' => 'entity',
            ],
        ];
    }

    public function testValidateBuildViolationPassesViolationNameAndVariableAsParameters(): void
    {
        $cause = new \RuntimeException('cause');
        $violation = new EmailTemplateSecurityPolicyPropertyViolation(
            name: 'dangerousProperty',
            variableName: 'entity',
            entityClass: \stdClass::class,
            templateLine: 1,
            cause: $cause,
            templateField: 'content'
        );

        $this->localizationManager
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->securityPolicyChecker
            ->expects(self::once())
            ->method('checkSecurityPolicy')
            ->willReturn([$violation]);

        $this->entityConfigProvider
            ->method('hasConfig')
            ->willReturn(false);

        $this->validator->validate(new EmailTemplate(), new EmailTemplateSecurityPolicy());

        $this->buildViolation('oro.email.validator.security_policy.disallowed_property')
            ->setParameters([
                '{{ field }}' => 'content',
                '{{ locale }}' => '',
                '{{ name }}' => 'dangerousProperty',
                '{{ variable }}' => 'entity',
            ])
            ->setCode(EmailTemplateSecurityPolicy::NOT_ALLOWED_PROPERTY_ERROR)
            ->setCause($violation)
            ->assertRaised();
    }

    public function testValidateBuildViolationPassesNullVariableNameForTagViolation(): void
    {
        $cause = new \RuntimeException('cause');
        $violation = new EmailTemplateSecurityPolicyTagViolation(
            name: 'block',
            templateLine: 1,
            cause: $cause,
            templateField: 'subject'
        );
        $this->localizationManager
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->securityPolicyChecker
            ->expects(self::once())
            ->method('checkSecurityPolicy')
            ->willReturn([$violation]);

        $this->entityConfigProvider
            ->method('hasConfig')
            ->willReturn(false);

        $this->validator->validate(new EmailTemplate(), new EmailTemplateSecurityPolicy());

        $this->buildViolation('oro.email.validator.security_policy.disallowed_tag')
            ->setParameters([
                '{{ field }}' => 'subject',
                '{{ locale }}' => '',
                '{{ name }}' => 'block',
                '{{ variable }}' => null,
            ])
            ->setCode(EmailTemplateSecurityPolicy::NOT_ALLOWED_TAG_ERROR)
            ->setCause($violation)
            ->assertRaised();
    }

    public function testValidateBuildViolationUsesRawFieldNameWhenEntityConfigAbsent(): void
    {
        $cause = new \RuntimeException('cause');
        $fieldName = 'subject';
        $violation = new EmailTemplateSecurityPolicyTagViolation(
            name: 'block',
            templateLine: 1,
            cause: $cause,
            templateField: $fieldName
        );

        $this->localizationManager
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->securityPolicyChecker
            ->expects(self::once())
            ->method('checkSecurityPolicy')
            ->willReturn([$violation]);

        $this->entityConfigProvider
            ->expects(self::once())
            ->method('hasConfig')
            ->with(EmailTemplate::class, $fieldName)
            ->willReturn(false);

        $this->translator
            ->expects(self::never())
            ->method('trans');

        $this->validator->validate(new EmailTemplate(), new EmailTemplateSecurityPolicy());

        $this->buildViolation('oro.email.validator.security_policy.disallowed_tag')
            ->setParameters([
                '{{ field }}' => $fieldName,
                '{{ locale }}' => '',
                '{{ name }}' => 'block',
                '{{ variable }}' => null,
            ])
            ->setCode(EmailTemplateSecurityPolicy::NOT_ALLOWED_TAG_ERROR)
            ->setCause($violation)
            ->assertRaised();
    }

    public function testValidateBuildViolationUsesTranslatedFieldLabelWhenEntityConfigPresent(): void
    {
        $cause = new \RuntimeException('cause');
        $fieldName = 'subject';
        $fieldConfigLabel = 'oro.email.emailtemplate.subject.label';
        $translatedLabel = 'Subject';
        $violation = new EmailTemplateSecurityPolicyTagViolation(
            name: 'block',
            templateLine: 1,
            cause: $cause,
            templateField: $fieldName
        );

        $this->localizationManager
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->securityPolicyChecker
            ->expects(self::once())
            ->method('checkSecurityPolicy')
            ->willReturn([$violation]);

        $fieldConfig = $this->createMock(ConfigInterface::class);
        $fieldConfig
            ->expects(self::once())
            ->method('get')
            ->with('label')
            ->willReturn($fieldConfigLabel);

        $this->entityConfigProvider
            ->expects(self::once())
            ->method('hasConfig')
            ->with(EmailTemplate::class, $fieldName)
            ->willReturn(true);

        $this->entityConfigProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with(EmailTemplate::class, $fieldName)
            ->willReturn($fieldConfig);

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with($fieldConfigLabel)
            ->willReturn($translatedLabel);

        $this->validator->validate(new EmailTemplate(), new EmailTemplateSecurityPolicy());

        $this->buildViolation('oro.email.validator.security_policy.disallowed_tag')
            ->setParameters([
                '{{ field }}' => $translatedLabel,
                '{{ locale }}' => '',
                '{{ name }}' => 'block',
                '{{ variable }}' => null,
            ])
            ->setCode(EmailTemplateSecurityPolicy::NOT_ALLOWED_TAG_ERROR)
            ->setCause($violation)
            ->assertRaised();
    }

    public function testValidateThrowsUnexpectedValueExceptionForUnknownViolationType(): void
    {
        $unknownViolation = $this->createMock(EmailTemplateSecurityPolicyViolationInterface::class);

        $this->localizationManager
            ->expects(self::once())
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->securityPolicyChecker
            ->expects(self::once())
            ->method('checkSecurityPolicy')
            ->willReturn([$unknownViolation]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/Unexpected security policy violation type/');

        $this->validator->validate(new EmailTemplate(), new EmailTemplateSecurityPolicy());
    }

    public function testValidateSkipsTranslationWhenBothSubjectAndContentAreFallback(): void
    {
        $translation = new EmailTemplateTranslation();
        $translation->setSubjectFallback(true);
        $translation->setContentFallback(true);
        $emailTemplate = new EmailTemplate();
        $emailTemplate->getTranslations()->add($translation);

        $this->localizationManager
            ->expects(self::once())
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->securityPolicyChecker
            ->expects(self::once())
            ->method('checkSecurityPolicy')
            ->with($emailTemplate)
            ->willReturn([]);

        $this->translatedEmailTemplateProvider
            ->expects(self::never())
            ->method('getTranslatedEmailTemplate');

        $this->validator->validate($emailTemplate, new EmailTemplateSecurityPolicy());
        $this->assertNoViolation();
    }

    public function testValidateProcessesTranslationWhenSubjectIsNotFallback(): void
    {
        $translation = new EmailTemplateTranslation();
        $translation->setSubjectFallback(false);
        $translation->setContentFallback(true);
        $translation->setLocalization(null);
        $emailTemplate = new EmailTemplate();
        $emailTemplate->getTranslations()->add($translation);

        $this->localizationManager
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->translatedEmailTemplateProvider
            ->expects(self::once())
            ->method('getTranslatedEmailTemplate')
            ->with($emailTemplate, null)
            ->willReturn(new EmailTemplate());

        $this->securityPolicyChecker
            ->expects(self::exactly(2))
            ->method('checkSecurityPolicy')
            ->willReturn([]);

        $this->validator->validate($emailTemplate, new EmailTemplateSecurityPolicy());
        $this->assertNoViolation();
    }

    public function testValidateProcessesTranslationWhenContentIsNotFallback(): void
    {
        $translation = new EmailTemplateTranslation();
        $translation->setSubjectFallback(true);
        $translation->setContentFallback(false);
        $translation->setLocalization(null);
        $emailTemplate = new EmailTemplate();
        $emailTemplate->getTranslations()->add($translation);

        $this->localizationManager
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->translatedEmailTemplateProvider
            ->expects(self::once())
            ->method('getTranslatedEmailTemplate')
            ->with($emailTemplate, null)
            ->willReturn(new EmailTemplate());

        $this->securityPolicyChecker
            ->expects(self::exactly(2))
            ->method('checkSecurityPolicy')
            ->willReturn([]);

        $this->validator->validate($emailTemplate, new EmailTemplateSecurityPolicy());
        $this->assertNoViolation();
    }

    public function testValidateSwallowsSyntaxErrorThrownByCheckerForTranslation(): void
    {
        $translation = new EmailTemplateTranslation();
        $translation->setSubjectFallback(false);
        $translation->setContentFallback(false);
        $translation->setLocalization(null);
        $translatedTemplate = new EmailTemplate();
        $emailTemplate = new EmailTemplate();
        $emailTemplate->getTranslations()->add($translation);

        $this->localizationManager
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->translatedEmailTemplateProvider
            ->expects(self::once())
            ->method('getTranslatedEmailTemplate')
            ->willReturn($translatedTemplate);

        $this->securityPolicyChecker
            ->expects(self::exactly(2))
            ->method('checkSecurityPolicy')
            ->willReturnCallback(
                static function (EmailTemplateInterface $template) use ($translatedTemplate): array {
                    if ($template === $translatedTemplate) {
                        throw new SyntaxError('Twig translation syntax error');
                    }

                    return [];
                }
            );

        $this->validator->validate($emailTemplate, new EmailTemplateSecurityPolicy());
        $this->assertNoViolation();
    }

    public function testValidateBuildViolationForTranslationViolationIncludesLocalizationTitle(): void
    {
        $cause = new \RuntimeException('cause');
        $localizationTitle = 'English (United States)';
        $defaultLocalization = new LocalizationStub();

        $localization = new LocalizationStub();
        $localization->setDefaultTitle($localizationTitle);

        $violation = new EmailTemplateSecurityPolicyTagViolation(
            name: 'block',
            templateLine: 1,
            cause: $cause,
            templateField: 'subject'
        );

        $translation = new EmailTemplateTranslation();
        $translation->setSubjectFallback(false);
        $translation->setContentFallback(true);
        $translation->setLocalization($localization);

        $translatedTemplate = new EmailTemplate();
        $emailTemplate = new EmailTemplate();
        $emailTemplate->getTranslations()->add($translation);

        $this->localizationManager
            ->method('getDefaultLocalization')
            ->willReturn($defaultLocalization);

        $this->securityPolicyChecker
            ->expects(self::exactly(2))
            ->method('checkSecurityPolicy')
            ->willReturnCallback(
                static function (EmailTemplateInterface $template) use ($translatedTemplate, $violation): array {
                    return $template === $translatedTemplate ? [$violation] : [];
                }
            );

        $this->translatedEmailTemplateProvider
            ->expects(self::once())
            ->method('getTranslatedEmailTemplate')
            ->willReturn($translatedTemplate);

        $this->entityConfigProvider
            ->method('hasConfig')
            ->willReturn(false);

        $this->validator->validate($emailTemplate, new EmailTemplateSecurityPolicy());

        $this->buildViolation('oro.email.validator.security_policy.disallowed_tag')
            ->setParameters([
                '{{ field }}' => 'subject',
                '{{ locale }}' => $localizationTitle,
                '{{ name }}' => 'block',
                '{{ variable }}' => null,
            ])
            ->setCode(EmailTemplateSecurityPolicy::NOT_ALLOWED_TAG_ERROR)
            ->setCause($violation)
            ->assertRaised();
    }

    public function testValidateBuildViolationForDefaultTemplateViolationUsesDefaultLocalizationAsLocale(): void
    {
        $cause = new \RuntimeException('cause');
        $defaultLocalizationTitle = 'Default Locale Title';
        $defaultLocalization = new LocalizationStub();
        $defaultLocalization->setDefaultTitle($defaultLocalizationTitle);

        $violation = new EmailTemplateSecurityPolicyTagViolation(
            name: 'block',
            templateLine: 1,
            cause: $cause,
            templateField: 'subject'
        );

        $this->localizationManager
            ->method('getDefaultLocalization')
            ->willReturn($defaultLocalization);

        $this->securityPolicyChecker
            ->expects(self::once())
            ->method('checkSecurityPolicy')
            ->willReturn([$violation]);

        $this->entityConfigProvider
            ->method('hasConfig')
            ->willReturn(false);

        $this->validator->validate(new EmailTemplate(), new EmailTemplateSecurityPolicy());

        $this->buildViolation('oro.email.validator.security_policy.disallowed_tag')
            ->setParameters([
                '{{ field }}' => 'subject',
                '{{ locale }}' => $defaultLocalizationTitle,
                '{{ name }}' => 'block',
                '{{ variable }}' => null,
            ])
            ->setCode(EmailTemplateSecurityPolicy::NOT_ALLOWED_TAG_ERROR)
            ->setCause($violation)
            ->assertRaised();
    }

    public function testValidateBuildViolationCalledForEachViolationInDefaultTemplate(): void
    {
        $cause = new \RuntimeException('cause');
        $tagViolation = new EmailTemplateSecurityPolicyTagViolation(
            name: 'block',
            templateLine: 1,
            cause: $cause,
            templateField: 'subject'
        );
        $filterViolation = new EmailTemplateSecurityPolicyFilterViolation(
            name: 'raw',
            templateLine: 2,
            cause: $cause,
            templateField: 'content'
        );

        $this->localizationManager
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->securityPolicyChecker
            ->expects(self::once())
            ->method('checkSecurityPolicy')
            ->willReturn([$tagViolation, $filterViolation]);

        $this->entityConfigProvider
            ->method('hasConfig')
            ->willReturn(false);

        $this->validator->validate(new EmailTemplate(), new EmailTemplateSecurityPolicy());

        $this->buildViolation('oro.email.validator.security_policy.disallowed_tag')
            ->setParameters([
                '{{ field }}' => 'subject',
                '{{ locale }}' => '',
                '{{ name }}' => 'block',
                '{{ variable }}' => null,
            ])
            ->setCode(EmailTemplateSecurityPolicy::NOT_ALLOWED_TAG_ERROR)
            ->setCause($tagViolation)
            ->buildNextViolation('oro.email.validator.security_policy.disallowed_filter')
            ->setParameters([
                '{{ field }}' => 'content',
                '{{ locale }}' => '',
                '{{ name }}' => 'raw',
                '{{ variable }}' => null,
            ])
            ->setCode(EmailTemplateSecurityPolicy::NOT_ALLOWED_FILTER_ERROR)
            ->setCause($filterViolation)
            ->assertRaised();
    }

    public function testValidateBuildViolationCalledForViolationsFromBothDefaultAndTranslatedTemplate(): void
    {
        $cause = new \RuntimeException('cause');
        $defaultViolation = new EmailTemplateSecurityPolicyTagViolation(
            name: 'block',
            templateLine: 1,
            cause: $cause,
            templateField: 'subject'
        );
        $translationViolation = new EmailTemplateSecurityPolicyFilterViolation(
            name: 'raw',
            templateLine: 2,
            cause: $cause,
            templateField: 'content'
        );

        $translation = new EmailTemplateTranslation();
        $translation->setSubjectFallback(false);
        $translation->setContentFallback(false);
        $translation->setLocalization(null);

        $translatedTemplate = new EmailTemplate();

        $emailTemplate = new EmailTemplate();
        $emailTemplate->getTranslations()->add($translation);

        $this->localizationManager
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->securityPolicyChecker
            ->expects(self::exactly(2))
            ->method('checkSecurityPolicy')
            ->willReturnCallback(
                static function (EmailTemplateInterface $template) use (
                    $translatedTemplate,
                    $defaultViolation,
                    $translationViolation
                ): array {
                    return $template === $translatedTemplate ? [$translationViolation] : [$defaultViolation];
                }
            );

        $this->translatedEmailTemplateProvider
            ->expects(self::once())
            ->method('getTranslatedEmailTemplate')
            ->willReturn($translatedTemplate);

        $this->entityConfigProvider
            ->method('hasConfig')
            ->willReturn(false);

        $this->validator->validate($emailTemplate, new EmailTemplateSecurityPolicy());

        $this->buildViolation('oro.email.validator.security_policy.disallowed_tag')
            ->setParameters([
                '{{ field }}' => 'subject',
                '{{ locale }}' => '',
                '{{ name }}' => 'block',
                '{{ variable }}' => null,
            ])
            ->setCode(EmailTemplateSecurityPolicy::NOT_ALLOWED_TAG_ERROR)
            ->setCause($defaultViolation)
            ->buildNextViolation('oro.email.validator.security_policy.disallowed_filter')
            ->setParameters([
                '{{ field }}' => 'content',
                '{{ locale }}' => '',
                '{{ name }}' => 'raw',
                '{{ variable }}' => null,
            ])
            ->setCode(EmailTemplateSecurityPolicy::NOT_ALLOWED_FILTER_ERROR)
            ->setCause($translationViolation)
            ->assertRaised();
    }
}
