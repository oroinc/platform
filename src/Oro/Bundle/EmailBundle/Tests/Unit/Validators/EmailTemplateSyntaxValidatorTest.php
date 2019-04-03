<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validators;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateSyntax;
use Oro\Bundle\EmailBundle\Validator\EmailTemplateSyntaxValidator;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EmailTemplateSyntaxValidatorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_SUBJECT       = '{{entity.subject}}';
    private const TEST_TRANS_SUBJECT = '{{entity.trans.subject}}';
    private const TEST_CONTENT       = '{{entity.content}}';

    /** @var EmailTemplateSyntax */
    private $constraint;

    /** @var EmailTemplate */
    private $template;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $emailRenderer;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    protected function setUp()
    {
        $this->constraint = new EmailTemplateSyntax();
        $this->emailRenderer = $this->createMock(EmailRenderer::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);

        $this->template = new EmailTemplate();
    }

    public function testValidateNoErrors()
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

    public function testValidateSandboxErrors()
    {
        $trans = new EmailTemplateTranslation();
        $trans
            ->setField('subject')
            ->setLocale('fr')
            ->setContent(self::TEST_TRANS_SUBJECT);
        $this->template->getTranslations()->add($trans);

        $this->emailRenderer->expects($this->exactly(3))
            ->method('validateTemplate')
            ->willThrowException(new \Twig_Error_Syntax('message'));

        $this->entityConfigProvider->expects($this->exactly(3))
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [get_class($this->template), 'subject', true],
                        [get_class($this->template), 'content', false],
                    ]
                )
            );
        $subjectConfig = new Config(new FieldConfigId('entity', get_class($this->template), 'subject', 'string'));
        $subjectConfig->set('label', 'subject.label');
        $this->entityConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->with(get_class($this->template), 'subject')
            ->will($this->returnValue($subjectConfig));

        $this->localeSettings->expects($this->exactly(3))
            ->method('getLanguage')
            ->will($this->returnValue('en'));
        $this->localeSettings->expects($this->exactly(3))
            ->method('getLocalesByCodes')
            ->will(
                $this->returnValueMap(
                    [
                        [['en'], 'en', ['en' => 'English']],
                        [['fr'], 'en', ['fr' => 'French']],
                    ]
                )
            );

        $this->context->expects($this->at(0))
            ->method('addViolation')
            ->with(
                $this->constraint->message,
                [
                    '{{ field }}'  => 'subject.label',
                    '{{ locale }}' => 'English',
                    '{{ error }}'  => 'message',
                ]
            );
        $this->context->expects($this->at(1))
            ->method('addViolation')
            ->with(
                $this->constraint->message,
                [
                    '{{ field }}'  => 'content',
                    '{{ locale }}' => 'English',
                    '{{ error }}'  => 'message',
                ]
            );
        $this->context->expects($this->at(2))
            ->method('addViolation')
            ->with(
                $this->constraint->message,
                [
                    '{{ field }}'  => 'subject.label',
                    '{{ locale }}' => 'French',
                    '{{ error }}'  => 'message',
                ]
            );

        $validator = $this->getValidator(SomeEntity::class);
        $validator->validate($this->template, $this->constraint);
    }

    /**
     * @param string $className
     * @return EmailTemplateSyntaxValidator
     */
    private function getValidator($className)
    {
        $this->template
            ->setContent(self::TEST_CONTENT)
            ->setSubject(self::TEST_SUBJECT)
            ->setEntityName($className);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $validator = new EmailTemplateSyntaxValidator(
            $this->emailRenderer,
            $this->localeSettings,
            $this->entityConfigProvider,
            $translator
        );
        $validator->initialize($this->context);

        return $validator;
    }
}
