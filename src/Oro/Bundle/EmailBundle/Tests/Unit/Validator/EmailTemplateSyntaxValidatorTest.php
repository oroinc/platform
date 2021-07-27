<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateSyntax;
use Oro\Bundle\EmailBundle\Validator\EmailTemplateSyntaxValidator;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\Localization;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\SyntaxError;

class EmailTemplateSyntaxValidatorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_SUBJECT = '{{entity.subject}}';
    private const TEST_TRANS_SUBJECT = '{{entity.trans.subject}}';
    private const TEST_CONTENT = '{{entity.content}}';

    /** @var EmailTemplateSyntax */
    private $constraint;

    /** @var EmailTemplate */
    private $template;

    /** @var EmailRenderer|\PHPUnit\Framework\MockObject\MockObject */
    private $emailRenderer;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    protected function setUp(): void
    {
        $this->constraint = new EmailTemplateSyntax();
        $this->emailRenderer = $this->createMock(EmailRenderer::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);

        $this->template = new EmailTemplate();
    }

    public function testValidateNoErrors(): void
    {
        $this->emailRenderer->expects($this->at(0))
            ->method('validateTemplate')
            ->with(self::TEST_SUBJECT);
        $this->emailRenderer->expects($this->at(1))
            ->method('validateTemplate')
            ->with(self::TEST_CONTENT);

        $this->context->expects($this->never())
            ->method('addViolation');

        $validator = $this->getValidator(SomeEntity::class);
        $validator->validate($this->template, $this->constraint);
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

        $this->context->expects($this->exactly(3))
            ->method('addViolation')
            ->withConsecutive(
                [
                    $this->constraint->message,
                    [
                        '{{ field }}' => 'subject.label',
                        '{{ locale }}' => 'English',
                        '{{ error }}' => 'message',
                    ],
                ],
                [
                    $this->constraint->message,
                    [
                        '{{ field }}' => 'content',
                        '{{ locale }}' => 'English',
                        '{{ error }}' => 'message',
                    ],
                ],
                [
                    $this->constraint->message,
                    [
                        '{{ field }}' => 'subject.label',
                        '{{ locale }}' => 'French',
                        '{{ error }}' => 'message',
                    ],
                ]
            );

        $validator = $this->getValidator(SomeEntity::class);
        $validator->validate($this->template, $this->constraint);
    }

    /**
     * @param string $className
     * @return EmailTemplateSyntaxValidator
     */
    private function getValidator($className): EmailTemplateSyntaxValidator
    {
        $this->template
            ->setContent(self::TEST_CONTENT)
            ->setSubject(self::TEST_SUBJECT)
            ->setEntityName($className);

        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $validator = new EmailTemplateSyntaxValidator(
            $this->emailRenderer,
            $this->localizationManager,
            $this->entityConfigProvider,
            $translator
        );
        $validator->initialize($this->context);

        return $validator;
    }
}
