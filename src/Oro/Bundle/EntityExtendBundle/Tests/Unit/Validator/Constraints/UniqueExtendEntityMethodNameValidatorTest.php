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
    const TEST_CLASS_NAME = 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Tools\TestEntity';

    /** @var UniqueExtendEntityFieldValidator */
    protected $validator;

    protected function setUp()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->validator = new UniqueExtendEntityMethodNameValidator(
            new FieldNameValidationHelper(new ConfigProviderMock($configManager, 'extend')),
            new ClassMethodNameChecker()
        );
    }

    /**
     * @dataProvider validateProvider
     *
     * @param string $fieldName
     * @param string $type
     * @param        $hasMethods
     */
    public function testValidate($fieldName, $type, $hasMethods)
    {
        $entity = new EntityConfigModel(self::TEST_CLASS_NAME);
        $field  = new FieldConfigModel($fieldName, $type);
        $entity->addField($field);
        $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->validator->initialize($context);
        $constraint = new UniqueExtendEntityMethodName();

        if ($hasMethods) {
            $violation = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
            $context->expects(self::once())
                ->method('buildViolation')
                ->with($constraint->message)
                ->willReturn($violation);
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
            'has conflict methods' => ['name', 'string', true],
            'without conflicts'    => ['noOne', 'string', false],
            'with relations field' => ['someField', 'manyToOne', true],
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
