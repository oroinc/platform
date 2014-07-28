<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Validator\VariablesValidator;
use Oro\Bundle\EmailBundle\Validator\Constraints\VariablesConstraint;

class VariablesValidatorTest extends \PHPUnit_Framework_TestCase
{
    const TEST_SUBJECT       = '{{entity.subject}}';
    const TEST_TRANS_SUBJECT = '{{entity.trans.subject}}';
    const TEST_CONTENT       = '{{entity.content}}';

    const ENTITY_CLASS          = 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity';
    const ABSTRACT_ENTITY_CLASS = 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeAbstractEntity';

    /**
     * @var VariablesConstraint
     */
    protected $variablesConstraint;

    /**
     * @var EmailTemplate
     */
    protected $template;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $twig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    protected function setUp()
    {
        $this->variablesConstraint = new VariablesConstraint();

        $this->twig = $this
            ->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this
            ->getMock('Symfony\Component\Validator\ExecutionContextInterface');

        $this->template = new EmailTemplate();
    }

    protected function tearDown()
    {
        unset($this->variablesConstraint);
        unset($this->twig);
        unset($this->context);
    }

    public function testValidateNoErrors()
    {
        $callback = function ($template, $params) {
            $this->assertInternalType('string', $template);

            $this->assertArrayHasKey('entity', $params);
            $this->assertArrayHasKey('user', $params);

            $this->assertInstanceOf(
                'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity',
                $params['entity']
            );
            $this->assertSame(['testVar' => 'test'], $params['system']);
        };

        $map = [
            [self::TEST_SUBJECT, $callback],
            [self::TEST_CONTENT, $callback]
        ];

        $this->twig
            ->expects($this->exactly(2))->method('render')
            ->will($this->returnValueMap($map));

        $this->context
            ->expects($this->never())
            ->method('addViolation');

        $validator = $this->getValidator(self::ENTITY_CLASS);
        $validator->validate($this->template, $this->variablesConstraint);
    }

    public function testValidateSandboxErrors()
    {
        $trans = new EmailTemplateTranslation();
        $trans
            ->setField('subject')
            ->setContent(self::TEST_TRANS_SUBJECT);
        $this->template->getTranslations()->add($trans);

        $this->twig
            ->expects($this->exactly(3))
            ->method('render')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TEST_SUBJECT],
                        [self::TEST_CONTENT],
                        [self::TEST_TRANS_SUBJECT]
                    ]
                )
            );

        $this->twig
            ->expects($this->at(2))
            ->method('render')->will(
                $this->throwException(new \Twig_Error('message'))
            );

        $this->context
            ->expects($this->once())
            ->method('addViolation')
            ->with(
                $this->variablesConstraint->message
            );

        $validator = $this->getValidator(self::ENTITY_CLASS);
        $validator->validate($this->template, $this->variablesConstraint);
    }

    public function testAbstractOrNonExistingFile()
    {
        $this->context
            ->expects($this->once())
            ->method('addViolation')
            ->with(
                sprintf(
                    'Its not possible to create template for "%s"',
                    self::ABSTRACT_ENTITY_CLASS
                )
            );

        $validator = $this->getValidator(self::ABSTRACT_ENTITY_CLASS);

        $validator->validate($this->template, $this->variablesConstraint);
    }

    /**
     * @param string $className
     * @return VariablesValidator
     */
    protected function getValidator($className)
    {
        $sandbox = $this
            ->getMockBuilder('\Twig_Extension_Sandbox')
            ->disableOriginalConstructor()
            ->getMock();

        $sandbox
            ->expects($this->once())
            ->method('enableSandbox');

        $sandbox
            ->expects($this->once())
            ->method('disableSandbox');

        $this->twig
            ->expects($this->once())
            ->method('getExtension')
            ->with($this->equalTo('sandbox'))
            ->will($this->returnValue($sandbox));

        $variablesProvider = $this->getMock(
            'Oro\Bundle\EmailBundle\Provider\VariablesProvider'
        );
        $variablesProvider->expects($this->any())
            ->method('getSystemVariableValues')
            ->will($this->returnValue(['testVar' => 'test']));

        $entityManager = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $classMetadata = $this
            ->getMockBuilder('Doctrine\\ORM\\Mapping\\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new \ReflectionClass($className);

        $classMetadata
            ->expects($this->any())
            ->method('getReflectionClass')
            ->will($this->returnValue($reflection));

        $entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($classMetadata));

        $this->template
            ->setContent(self::TEST_CONTENT)
            ->setSubject(self::TEST_SUBJECT)
            ->setEntityName($className);

        $validator = new VariablesValidator(
            $this->twig,
            $variablesProvider,
            $entityManager
        );
        $validator->initialize($this->context);

        return $validator;
    }
}
