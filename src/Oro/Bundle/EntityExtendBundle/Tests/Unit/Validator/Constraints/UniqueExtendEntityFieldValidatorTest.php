<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityField;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityFieldValidator;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\NewEntitiesHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UniqueExtendEntityFieldValidatorTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_CLASS = 'Test\Entity';

    /** @var UniqueExtendEntityFieldValidator */
    protected $validator;

    protected function setUp()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $extendConfigProvider = new ConfigProviderMock(
            $configManager,
            'extend'
        );

        $extendConfigProvider->addFieldConfig(self::ENTITY_CLASS, 'activeField', 'int');
        $extendConfigProvider->addFieldConfig(self::ENTITY_CLASS, 'activeHiddenField', 'int', [], true);
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'deletedField',
            'int',
            ['is_deleted' => true]
        );
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'toBeDeletedField',
            'int',
            ['state' => ExtendScope::STATE_DELETE]
        );

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->validator = new UniqueExtendEntityFieldValidator(
            new FieldNameValidationHelper($extendConfigProvider, $eventDispatcher, new NewEntitiesHelper())
        );
    }

    /**
     * @dataProvider validateProvider
     *
     * @param string $fieldName
     * @param string $expectedValidationMessageType
     */
    public function testValidate($fieldName, $expectedValidationMessageType)
    {
        $entity = new EntityConfigModel(self::ENTITY_CLASS);
        $field  = new FieldConfigModel($fieldName);
        $entity->addField($field);

        $context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($context);

        $constraint = new UniqueExtendEntityField();

        if ($expectedValidationMessageType) {
            $message   = PropertyAccess::createPropertyAccessor()
                ->getValue($constraint, $expectedValidationMessageType);
            $violation = $this->createMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
            $context->expects($this->once())
                ->method('buildViolation')
                ->with($message)
                ->willReturn($violation);
            $violation->expects($this->once())
                ->method('atPath')
                ->with('fieldName')
                ->willReturnSelf();
            $violation->expects($this->once())
                ->method('addViolation');
        } else {
            $context->expects($this->never())
                ->method('buildViolation');
        }

        $this->validator->validate($field, $constraint);
    }

    public function validateProvider()
    {
        return [
            ['id', 'sameFieldMessage'],
            ['i_d', 'similarFieldMessage'],
            ['anotherField', null],
            ['activeField', 'sameFieldMessage'],
            ['active_field', 'similarFieldMessage'],
            ['activeHiddenField', 'sameFieldMessage'],
            ['active_hidden_field', 'similarFieldMessage'],
            ['deletedField', 'sameFieldMessage'],
            ['deleted_field', null],
            ['toBeDeletedField', 'sameFieldMessage'],
            ['to_be_deleted_field', null],
        ];
    }
}
