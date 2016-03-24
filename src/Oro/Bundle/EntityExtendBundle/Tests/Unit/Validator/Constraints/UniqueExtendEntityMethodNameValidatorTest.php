<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\Tools\ClassMethodNameChecker;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityFieldValidator;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityMethodName;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityMethodNameValidator;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;

class UniqueExtendEntityMethodNameValidatorTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass';

    /** @var UniqueExtendEntityFieldValidator */
    protected $validator;

    /** @var ClassMethodNameChecker */
    protected $methodNameChecker;

    protected function setUp()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->methodNameChecker = $this
            ->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\ClassMethodNameChecker')
            ->disableOriginalConstructor()
            ->getMock();

        $extendConfigProvider = new ConfigProviderMock($configManager, 'extend');

        $this->validator = new UniqueExtendEntityMethodNameValidator(
            new FieldNameValidationHelper($extendConfigProvider),
            $this->methodNameChecker
        );
    }

    /**
     * @dataProvider validateProvider
     *
     * @param string $fieldName
     * @param string $type
     * @param bool   $hasMethod
     * @param string $getter
     * @param string $setter
     * @param string $hasRelation
     */
    public function testValidate($fieldName, $type, $hasMethod, $getter = '', $setter = '', $hasRelation = '')
    {
        $entity = new EntityConfigModel(self::ENTITY_CLASS);
        $field  = new FieldConfigModel($fieldName, $type);
        $entity->addField($field);

        $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->validator->initialize($context);

        $constraint = new UniqueExtendEntityMethodName();
        $violation  = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');

        $this->methodNameChecker->expects(self::once())
            ->method('hasGetters')
            ->willReturn($getter);

        $this->methodNameChecker->expects(self::once())
            ->method('hasSetters')
            ->willReturn($setter);


        if (strlen($hasRelation) > 0) {
            $this->methodNameChecker->expects(self::once())
                ->method('hasRelationMethods')
                ->willReturn($hasRelation);
        } else {
            $this->methodNameChecker->expects(self::never())
                ->method('hasRelationMethods');
        }

        if (strlen($getter) > 0 || strlen($setter) > 0 || strlen($hasRelation) > 0) {
            $context->expects(self::once())
                ->method('buildViolation')
                ->with($constraint->message)
                ->willReturn($violation);
        }

        if ($hasMethod) {
            $violation->expects(self::once())
                ->method('atPath')
                ->with('fieldName')
                ->willReturnSelf();

            $violation->expects(self::once())
                ->method('addViolation');
        }

        $this->validator->validate($field, $constraint);
    }

    /**
     * @return array
     */
    public function validateProvider()
    {
        return [
            'With getter'          => ['email', 'string', true, 'getEmail'],
            'Without conflicts'    => ['noOne', 'string', false],
            'With relations field' => ['email', 'manyToOne', true, '', '', 'addEmail'],
            'With setter'          => ['email', 'string', true, '', 'setEmail'],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailedValidate()
    {
        $field   = '';
        $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->validator->initialize($context);

        $constraint = new UniqueExtendEntityMethodName();

        $this->validator->validate($field, $constraint);
    }
}
