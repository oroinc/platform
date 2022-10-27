<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateSyntax;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateSyntaxValidator;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\Localization;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\SyntaxError;

class EmailTemplateSyntaxValidatorTest extends ConstraintValidatorTestCase
{
    private const TEST_SUBJECT = '{{entity.subject}}';
    private const TEST_TRANS_SUBJECT = '{{entity.trans.subject}}';
    private const TEST_CONTENT = '{{entity.content}}';

    /** @var EmailRenderer|\PHPUnit\Framework\MockObject\MockObject */
    private $emailRenderer;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    /** @var EmailTemplate */
    private $template;

    protected function setUp(): void
    {
        $this->emailRenderer = $this->createMock(EmailRenderer::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);

        $this->template = new EmailTemplate();
        $this->template
            ->setContent(self::TEST_CONTENT)
            ->setSubject(self::TEST_SUBJECT)
            ->setEntityName(SomeEntity::class);

        parent::setUp();
    }

    protected function createValidator()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . '_translated';
            });

        return new EmailTemplateSyntaxValidator(
            $this->emailRenderer,
            $this->localizationManager,
            $this->entityConfigProvider,
            $translator
        );
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(EmailTemplate::class), $this->createMock(Constraint::class));
    }

    public function testValueIsNotEmailTemplate()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('test', new EmailTemplateSyntax());
    }

    public function testValidateNoErrors(): void
    {
        $this->emailRenderer->expects($this->exactly(2))
            ->method('validateTemplate')
            ->withConsecutive(
                [self::TEST_SUBJECT],
                [self::TEST_CONTENT]
            );

        $constraint = new EmailTemplateSyntax();
        $this->validator->validate($this->template, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateSandboxErrors(): void
    {
        $defaultLocalization = new Localization();
        $defaultLocalization->addTitle(
            (new LocalizedFallbackValue())
                ->setLocalization($defaultLocalization)
                ->setString('English')
        );

        $frenchLocalization = new Localization();
        $frenchLocalization->addTitle(
            (new LocalizedFallbackValue())
                ->setLocalization($defaultLocalization)
                ->setString('French')
        );

        $this->template->addTranslation(
            (new EmailTemplateTranslation())
                ->setLocalization($frenchLocalization)
                ->setSubject(self::TEST_TRANS_SUBJECT)
                ->setSubjectFallback(false)
                ->setContentFallback(true)
        );

        $this->emailRenderer->expects($this->exactly(3))
            ->method('validateTemplate')
            ->willThrowException(new SyntaxError('message'));

        $this->entityConfigProvider->expects($this->exactly(3))
            ->method('hasConfig')
            ->willReturnMap([
                [get_class($this->template), 'subject', true],
                [get_class($this->template), 'content', false],
            ]);

        $subjectConfig = new Config(new FieldConfigId('entity', get_class($this->template), 'subject', 'string'));
        $subjectConfig->set('label', 'subject.label');
        $this->entityConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->with(get_class($this->template), 'subject')
            ->willReturn($subjectConfig);

        $this->localizationManager->expects($this->once())
            ->method('getDefaultLocalization')
            ->willReturn($defaultLocalization);

        $constraint = new EmailTemplateSyntax();
        $this->validator->validate($this->template, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameters([
                '{{ field }}'  => 'subject.label_translated',
                '{{ locale }}' => 'English',
                '{{ error }}'  => 'message'
            ])
            ->buildNextViolation($constraint->message)
            ->setParameters([
                '{{ field }}'  => 'content',
                '{{ locale }}' => 'English',
                '{{ error }}'  => 'message'
            ])
            ->buildNextViolation($constraint->message)
            ->setParameters([
                '{{ field }}'  => 'subject.label_translated',
                '{{ locale }}' => 'French',
                '{{ error }}'  => 'message'
            ])
            ->assertRaised();
    }
}
