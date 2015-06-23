<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\FormBundle\Validator\Constraints\EntityClass;
use Oro\Bundle\FormBundle\Validator\Constraints\EntityClassValidator;

class EntityClassValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityClassNameHelper;

    /** @var EntityClassValidator */
    protected $validator;

    protected function setUp()
    {
        $this->doctrineHelper        = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassNameHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new EntityClassValidator(
            $this->doctrineHelper,
            $this->entityClassNameHelper
        );
    }

    /**
     * @dataProvider validItemsDataProvider
     *
     * @param string $value
     */
    public function testValidateValid($value)
    {
        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->never())
            ->method('addViolation');

        $this->entityClassNameHelper->expects($this->any())
            ->method('resolveEntityClass')
            ->with($value)
            ->willReturn($value);
        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->with($value)
            ->willReturn(true);

        $constraint = new EntityClass();
        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }

    /**
     * @return array
     */
    public function validItemsDataProvider()
    {
        return [
            [null],
            [''],
            ['Test\Entity'],
        ];
    }

    public function testValidateValidAlias()
    {
        $value = 'alias';

        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->never())
            ->method('addViolation');

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value)
            ->willReturn('Test\Entity');
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with('Test\Entity')
            ->willReturn(true);

        $constraint = new EntityClass();
        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }

    public function testValidateUnknownAlias()
    {
        $value = 'alias';

        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->once())
            ->method('addViolation');

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value)
            ->will($this->throwException(new EntityAliasNotFoundException()));
        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntity');

        $constraint = new EntityClass();
        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }

    public function testValidateNotManageableEntity()
    {
        $value = 'Test\Entity';

        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->once())
            ->method('addViolation');

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value)
            ->willReturn($value);
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with($value)
            ->willReturn(false);

        $constraint = new EntityClass();
        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }

    public function testValidateInvalidValue()
    {
        $value      = 123;
        $constraint = new EntityClass();

        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->once())
            ->method('addViolation')
            ->with($constraint->message, ['{{ value }}' => '123']);

        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }
}
