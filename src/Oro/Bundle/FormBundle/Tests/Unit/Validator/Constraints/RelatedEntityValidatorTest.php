<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\FormBundle\Validator\Constraints\RelatedEntity;
use Oro\Bundle\FormBundle\Validator\Constraints\RelatedEntityValidator;

class RelatedEntityValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityClassNameHelper;

    /** @var RelatedEntityValidator */
    protected $validator;

    protected function setUp()
    {
        $this->doctrineHelper        = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassNameHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new RelatedEntityValidator(
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

        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->with($value)
            ->willReturn(true);

        $constraint = new RelatedEntity();
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
            [[]],
            [['id' => 123]],
            [['entity' => 'Test\Entity']],
            [new \stdClass()]
        ];
    }

    public function testValidateValidAlias()
    {
        $value = ['id' => 123, 'entity' => 'alias'];

        $constraint = new RelatedEntity();

        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->once())
            ->method('addViolation')
            ->with($constraint->message, ['{{ value }}' => '[id => 123, entity => "alias"]']);

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn('Test\Entity');
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with('Test\Entity')
            ->willReturn(true);

        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }

    public function testValidateValidClass()
    {
        $value = ['id' => 123, 'entity' => 'Test\Entity'];

        $constraint = new RelatedEntity();

        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->once())
            ->method('addViolation')
            ->with($constraint->message, ['{{ value }}' => '[id => 123, entity => "Test\Entity"]']);

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn($value['entity']);
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with('Test\Entity')
            ->willReturn(true);

        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }

    public function testValidateUnknownAlias()
    {
        $value = ['id' => 123, 'entity' => 'alias'];

        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->never())
            ->method('addViolation');

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->will($this->throwException(new EntityAliasNotFoundException()));
        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntity');

        $constraint = new RelatedEntity();
        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }

    public function testValidateNotManageableEntity()
    {
        $value = ['id' => 123, 'entity' => 'Test\Entity'];

        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->never())
            ->method('addViolation');

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn($value['entity']);
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with($value['entity'])
            ->willReturn(false);

        $constraint = new RelatedEntity();
        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }
}
