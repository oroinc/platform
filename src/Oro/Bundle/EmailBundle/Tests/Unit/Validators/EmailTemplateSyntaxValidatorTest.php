<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Validator\EmailTemplateSyntaxValidator;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateSyntax;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class EmailTemplateSyntaxValidatorTest extends \PHPUnit_Framework_TestCase
{
    const TEST_SUBJECT       = '{{entity.subject}}';
    const TEST_TRANS_SUBJECT = '{{entity.trans.subject}}';
    const TEST_CONTENT       = '{{entity.content}}';

    const ENTITY_CLASS          = 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity';
    const ABSTRACT_ENTITY_CLASS = 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeAbstractEntity';

    /** @var EmailTemplateSyntax */
    protected $constraint;

    /** @var EmailTemplate */
    protected $template;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $twig;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $localeSettings;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    protected function setUp()
    {
        $this->constraint = new EmailTemplateSyntax();

        $this->twig = $this
            ->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this
            ->getMock('Symfony\Component\Validator\ExecutionContextInterface');

        $this->localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->template = new EmailTemplate();
    }

    public function testValidateNoErrors()
    {
        $subjectTokenStream = $this->getMockBuilder('\Twig_TokenStream')
            ->disableOriginalConstructor()
            ->getMock();
        $contentTokenStream = $this->getMockBuilder('\Twig_TokenStream')
            ->disableOriginalConstructor()
            ->getMock();

        $tokenizeMap = [
            [self::TEST_SUBJECT, null, $subjectTokenStream],
            [self::TEST_CONTENT, null, $contentTokenStream]
        ];
        $this->twig->expects($this->exactly(count($tokenizeMap)))
            ->method('tokenize')
            ->will($this->returnValueMap($tokenizeMap));
        $parseMap = [
            [$subjectTokenStream, null],
            [$contentTokenStream, null]
        ];
        $this->twig->expects($this->exactly(count($parseMap)))
            ->method('parse')
            ->will($this->returnValueMap($parseMap));

        $this->context->expects($this->never())
            ->method('addViolation');

        $validator = $this->getValidator(self::ENTITY_CLASS);
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

        $subjectTokenStream      = $this->getMockBuilder('\Twig_TokenStream')
            ->disableOriginalConstructor()
            ->getMock();
        $contentTokenStream      = $this->getMockBuilder('\Twig_TokenStream')
            ->disableOriginalConstructor()
            ->getMock();
        $transSubjectTokenStream = $this->getMockBuilder('\Twig_TokenStream')
            ->disableOriginalConstructor()
            ->getMock();

        $tokenizeMap = [
            [self::TEST_SUBJECT, null, $subjectTokenStream],
            [self::TEST_CONTENT, null, $contentTokenStream],
            [self::TEST_TRANS_SUBJECT, null, $transSubjectTokenStream]
        ];
        $this->twig->expects($this->exactly(count($tokenizeMap)))
            ->method('tokenize')
            ->will($this->returnValueMap($tokenizeMap));

        $this->twig->expects($this->exactly(count($tokenizeMap)))
            ->method('parse')
            ->will(
                $this->throwException(new \Twig_Error_Syntax('message'))
            );

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

        $validator = $this->getValidator(self::ENTITY_CLASS);
        $validator->validate($this->template, $this->constraint);
    }

    /**
     * @param string $className
     * @return EmailTemplateSyntaxValidator
     */
    protected function getValidator($className)
    {
        $this->template
            ->setContent(self::TEST_CONTENT)
            ->setSubject(self::TEST_SUBJECT)
            ->setEntityName($className);

        $sandbox = $this->getMockBuilder('\Twig_Extension_Sandbox')
            ->disableOriginalConstructor()
            ->getMock();

        $sandbox->expects($this->once())
            ->method('enableSandbox');

        $sandbox->expects($this->once())
            ->method('disableSandbox');

        $this->twig->expects($this->once())
            ->method('getExtension')
            ->with($this->equalTo('sandbox'))
            ->will($this->returnValue($sandbox));

        $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $validator = new EmailTemplateSyntaxValidator(
            $this->twig,
            $this->localeSettings,
            $this->entityConfigProvider,
            $translator
        );
        $validator->initialize($this->context);

        return $validator;
    }
}
