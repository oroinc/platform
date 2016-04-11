<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\ConfigExpression;

use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Bundle\SecurityBundle\ConfigExpression\AclGranted;

class AclGrantedTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var AclGranted */
    protected $condition;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine       = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->condition = new AclGranted($this->securityFacade, $this->doctrine);
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testEvaluateByAclAnnotationId()
    {
        $options        = ['acme_product_view'];
        $context        = [];
        $expectedResult = true;

        $this->setupSecurityFacade($options[0], null, $expectedResult);

        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function testEvaluateByObjectIdentityDescriptor()
    {
        $options        = ['VIEW', 'entity:Acme/DemoBundle/Entity/AcmeEntity'];
        $context        = [];
        $expectedResult = true;

        $this->setupSecurityFacade($options[0], $options[1], $expectedResult);

        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function testEvaluateForNotEntityObject()
    {
        $options        = ['VIEW', new \stdClass()];
        $context        = [];
        $expectedResult = true;

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($options[1]))
            ->will($this->returnValue(null));

        $this->setupSecurityFacade($options[0], $this->identicalTo($options[1]), $expectedResult);

        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function testEvaluateForExistingEntity()
    {
        $options        = ['VIEW', new \stdClass()];
        $context        = [];
        $expectedResult = true;

        $em  = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('isScheduledForInsert')
            ->with($options[1])
            ->will($this->returnValue(false));
        $uow->expects($this->once())
            ->method('isInIdentityMap')
            ->with($options[1])
            ->will($this->returnValue(true));

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($options[1]))
            ->will($this->returnValue($em));

        $this->setupSecurityFacade($options[0], $this->identicalTo($options[1]), $expectedResult);

        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function testEvaluateForNewEntity()
    {
        $options        = ['VIEW', new \stdClass()];
        $context        = [];
        $expectedResult = true;

        $em  = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('isScheduledForInsert')
            ->with($options[1])
            ->will($this->returnValue(true));
        $uow->expects($this->never())
            ->method('isInIdentityMap');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($options[1]))
            ->will($this->returnValue($em));

        $this->setupSecurityFacade($options[0], 'entity:' . ClassUtils::getRealClass($options[1]), $expectedResult);

        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function testEvaluateForEntityWhichIsNotInUowYet()
    {
        $options        = ['VIEW', new \stdClass()];
        $context        = [];
        $expectedResult = true;

        $em  = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('isScheduledForInsert')
            ->with($options[1])
            ->will($this->returnValue(false));
        $uow->expects($this->once())
            ->method('isInIdentityMap')
            ->with($options[1])
            ->will($this->returnValue(false));

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($options[1]))
            ->will($this->returnValue($em));

        $this->setupSecurityFacade($options[0], 'entity:' . ClassUtils::getRealClass($options[1]), $expectedResult);

        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function testEvaluateHasNoUser()
    {
        $options        = ['acme_product_view'];
        $context        = [];

        $this->securityFacade->expects($this->once())
            ->method('hasLoggedUser')
            ->with()
            ->will($this->returnValue(false));

        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertFalse($this->condition->evaluate($context));
    }

    /**
     * @param string|string[] $attributes
     * @param mixed $object
     * @param bool $expectedResult
     */
    protected function setupSecurityFacade($attributes, $object, $expectedResult)
    {
        $this->securityFacade->expects($this->once())
            ->method('hasLoggedUser')
            ->with()
            ->will($this->returnValue(true));

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($attributes, $object)
            ->will($this->returnValue($expectedResult));
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 or 2 elements, but 0 given.
     */
    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->condition->initialize([]);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage ACL attributes must not be empty.
     */
    public function testInitializeFailsWhenEmptyAttributes()
    {
        $this->condition->initialize(['']);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage ACL object must not be empty.
     */
    public function testInitializeFailsWhenEmptyObject()
    {
        $this->condition->initialize(['VIEW', '']);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray($options, $message, $expected)
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider()
    {
        return [
            [
                'options'  => ['acme_product_view'],
                'message'  => null,
                'expected' => [
                    '@acl' => [
                        'parameters' => [
                            'acme_product_view'
                        ]
                    ]
                ]
            ],
            [
                'options'  => ['VIEW', new PropertyPath('entity')],
                'message'  => 'Test',
                'expected' => [
                    '@acl' => [
                        'message'    => 'Test',
                        'parameters' => [
                            'VIEW',
                            '$entity'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile($options, $message, $expected)
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider()
    {
        return [
            [
                'options'  => ['acme_product_view'],
                'message'  => null,
                'expected' => '$factory->create(\'acl\', [\'acme_product_view\'])'
            ],
            [
                'options'  => ['VIEW', new PropertyPath('entity')],
                'message'  => 'Test',
                'expected' => '$factory->create(\'acl\', [\'VIEW\', '
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'entity\', [\'entity\'], [false])'
                    . '])->setMessage(\'Test\')'
            ]
        ];
    }
}
