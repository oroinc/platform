<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Condition;

use Oro\Bundle\WorkflowBundle\Model\Condition\AclGranted;

class AclGrantedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var AclGranted
     */
    protected $condition;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ContextAccessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->condition = new AclGranted($this->contextAccessor, $this->securityFacade, $this->doctrineHelper);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     * @param array $options
     * @param string $exceptionName
     * @param string $exceptionMessage
     */
    public function testInitializeExceptions($options, $exceptionName, $exceptionMessage)
    {
        $this->setExpectedException($exceptionName, $exceptionMessage);
        $this->condition->initialize($options);
    }

    /**
     * @return array
     */
    public function invalidOptionsDataProvider()
    {
        return array(
            array(
                array(),
                'Oro\Bundle\WorkflowBundle\Exception\ConditionException',
                'Condition requires ACL attributes'
            ),
            array(
                array(null),
                'Oro\Bundle\WorkflowBundle\Exception\ConditionException',
                'ACL attributes can not be empty'
            ),
            array(
                array('test', null),
                'Oro\Bundle\WorkflowBundle\Exception\ConditionException',
                'ACL object can not be empty'
            ),
        );
    }

    /**
     * @dataProvider validOptionsDataProvider
     * @param array $options
     */
    public function testInitialize($options)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
    }

    /**
     * @return array
     */
    public function validOptionsDataProvider()
    {
        return array(
            array(
                array('test')
            ),
            array(
                array('test', new \stdClass())
            ),
        );
    }

    public function testIsAllowedWithoutObject()
    {
        $context = array();
        $attribute = 'test';
        $object = null;
        $options = array($attribute);
        $this->assertContextAccessorCalls($context, 'test', $object);

        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('_test', $object)
            ->will($this->returnValue(true));

        $this->condition->initialize($options);
        $this->assertTrue($this->condition->isAllowed($context));
    }

    public function testIsAllowedWithObject()
    {
        $context = array();
        $attribute = 'test';
        $object = new \stdClass();
        $options = array($attribute, $object);
        $this->assertContextAccessorCalls($context, $attribute, $object);

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('isInIdentityMap')
            ->with($object)
            ->will($this->returnValue(true));
        $uow->expects($this->once())
            ->method('isScheduledForInsert')
            ->with($object)
            ->will($this->returnValue(false));

        $this->assertEntityManagerCall($uow, $object);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('_test', $object)
            ->will($this->returnValue(true));

        $this->condition->initialize($options);
        $this->assertTrue($this->condition->isAllowed($context));
    }

    public function testIsAllowedWithObjectNotInUowYet()
    {
        $context = array();
        $attribute = 'test';
        $object = new \stdClass();
        $options = array($attribute, $object);
        $this->assertContextAccessorCalls($context, $attribute, $object);

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('isInIdentityMap')
            ->with($object)
            ->will($this->returnValue(false));
        $uow->expects($this->never())
            ->method('isScheduledForInsert');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($object)
            ->will($this->returnValue('Class'));

        $this->assertEntityManagerCall($uow, $object);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('_test', 'Entity:Class')
            ->will($this->returnValue(true));

        $this->condition->initialize($options);
        $this->assertTrue($this->condition->isAllowed($context));
    }

    public function testIsAllowedWithObjectNotScheduledForInsert()
    {
        $context = array();
        $attribute = 'test';
        $object = new \stdClass();
        $options = array($attribute, $object);
        $this->assertContextAccessorCalls($context, $attribute, $object);

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('isInIdentityMap')
            ->with($object)
            ->will($this->returnValue(true));
        $uow->expects($this->once())
            ->method('isScheduledForInsert')
            ->with($object)
            ->will($this->returnValue(true));
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($object)
            ->will($this->returnValue('Class'));

        $this->assertEntityManagerCall($uow, $object);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('_test', 'Entity:Class')
            ->will($this->returnValue(true));

        $this->condition->initialize($options);
        $this->assertTrue($this->condition->isAllowed($context));
    }

    protected function assertEntityManagerCall($uow, $object)
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($object)
            ->will($this->returnValue($em));
    }

    protected function assertContextAccessorCalls($context, $attribute, $object)
    {
        $this->contextAccessor->expects($this->at(0))
            ->method('getValue')
            ->with($context, $attribute)
            ->will($this->returnValue('_test'));

        $this->contextAccessor->expects($this->at(1))
            ->method('getValue')
            ->with($context, $object)
            ->will($this->returnValue($object));
    }
}
