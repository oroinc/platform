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

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $twig;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $sandbox;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityContext;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $user;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $classMetadata;

    /** @var EmailTemplate */
    protected $template;

    /** @var VariablesValidator */
    protected $validator;

    /** @var VariablesConstraint */
    protected $variablesConstraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    public function setUp()
    {
        $this->twig = $this
            ->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sandbox = $this
            ->getMockBuilder('\Twig_Extension_Sandbox')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sandbox
            ->expects($this->once())
            ->method('enableSandbox');

        $this->sandbox
            ->expects($this->once())
            ->method('disableSandbox');

        $this->twig
            ->expects($this->once())
            ->method('getExtension')
            ->with($this->equalTo('sandbox'))
            ->will($this->returnValue($this->sandbox));

        $this->securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $token                 = $this->getMockForAbstractClass(
            'Symfony\Component\Security\Core\Authentication\Token\TokenInterface'
        );
        $this->user            = $this
            ->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($this->user));

        $this->securityContext
            ->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->context = $this
            ->getMockForAbstractClass('Symfony\Component\Validator\ExecutionContextInterface');

        $this->entityManager = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->classMetadata = $this
            ->getMockBuilder('Doctrine\\ORM\\Mapping\\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->classMetadata
            ->expects($this->any())
            ->method('getAssociationMappings')
            ->will(
                $this->returnValue(
                    [
                        'stdClass' => [
                            'targetEntity' => '\stdClass',
                            'fieldName'    => 'stdClass',
                            'type'         => 1
                        ]
                    ]
                )
            );

        $this->entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($this->classMetadata));

        $this->template = new EmailTemplate();

        $this->template
            ->setContent(self::TEST_CONTENT)
            ->setSubject(self::TEST_SUBJECT)
            ->setEntityName('Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity');

        $this->variablesConstraint = new VariablesConstraint();

        $this->validator = new VariablesValidator(
            $this->twig,
            $this->securityContext,
            $this->entityManager
        );
        $this->validator->initialize($this->context);
    }

    public function tearDown()
    {
        unset($this->twig);
        unset($this->securityContext);
        unset($this->user);
        unset($this->template);
        unset($this->validator);
        unset($this->variablesConstraint);
        unset($this->context);
    }

    public function testValidateNotErrors()
    {
        $phpUnit  = $this;
        $user     = $this->user;
        $callback = function ($template, $params) use ($phpUnit, $user) {
            $phpUnit->assertInternalType('string', $template);

            $phpUnit->assertArrayHasKey('entity', $params);
            $phpUnit->assertArrayHasKey('user', $params);

            $phpUnit->assertInstanceOf(
                'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity',
                $params['entity']
            );
            $phpUnit->assertInstanceOf(get_class($user), $params['user']);
        };

        $map = [
            [self::TEST_SUBJECT, $callback],
            [self::TEST_CONTENT, $callback]
        ];

        $this->twig->expects($this->exactly(2))->method('render')->will($this->returnValueMap($map));

        $this->context->expects($this->never())->method('addViolation');

        $this->validator->validate($this->template, $this->variablesConstraint);
    }

    public function testValidateSandboxErrors()
    {
        $trans = new EmailTemplateTranslation();
        $trans
            ->setField('subject')
            ->setContent(self::TEST_TRANS_SUBJECT);
        $this->template->getTranslations()->add($trans);

        $map = [
            [self::TEST_SUBJECT],
            [self::TEST_CONTENT],
            [self::TEST_TRANS_SUBJECT]
        ];

        $this->twig->expects($this->exactly(3))->method('render')->will($this->returnValueMap($map));

        $this->twig->expects($this->at(2))->method('render')->will(
            $this->throwException(new \Twig_Sandbox_SecurityError('message'))
        );

        $this->context->expects($this->once())->method('addViolation')->with($this->variablesConstraint->message);

        $this->validator->validate($this->template, $this->variablesConstraint);
    }

    public function testValidateRuntimeErrors()
    {
        $trans = new EmailTemplateTranslation();
        $trans
            ->setField('subject')
            ->setContent(self::TEST_TRANS_SUBJECT);
        $this->template->getTranslations()->add($trans);

        $map = [
            [self::TEST_SUBJECT],
            [self::TEST_CONTENT],
            [self::TEST_TRANS_SUBJECT]
        ];

        $this->twig->expects($this->exactly(3))->method('render')->will($this->returnValueMap($map));

        $this->twig->expects($this->at(2))->method('render')->will(
            $this->throwException(new \Twig_Error_Runtime('message'))
        );

        $this->context->expects($this->once())->method('addViolation')->with($this->variablesConstraint->message);

        $this->validator->validate($this->template, $this->variablesConstraint);
    }
}
