<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NonExtendedEntityBidirectional;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NonExtendedEntityBidirectionalValidator;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NonExtendedEntityBidirectionalValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var Form|\PHPUnit\Framework\MockObject\MockObject */
    private $rootMock;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        parent::setUp();

        $this->rootMock = $this->createMock(Form::class);
        $this->constraint = new NonExtendedEntityBidirectional();
        $this->context->setConstraint($this->constraint);
        $this->setRoot($this->rootMock);
    }

    /**
     * @dataProvider validateDataProvider
     *
     * @param string      $className
     * @param string      $relationType
     * @param bool        $isBidirectional
     * @param bool        $isExtended
     * @param bool|string $violation
     */
    public function testValidateBidirectionalRelation(
        $className,
        $relationType,
        $isExtended,
        $isBidirectional,
        $violation
    ) {
        $this->configureRootFormFieldType($relationType);

        $config = new Config(new EntityConfigId('extend', $className));
        $config->set('is_extend', $isExtended);

        $this->configManager->expects($this->any())
            ->method('getEntityConfig')
            ->with('extend', $className)
            ->willReturn($config);

        $value = ['target_entity' => $className];
        if (is_bool($isBidirectional)) {
            $value['bidirectional'] = $isBidirectional;
        }

        $this->validator->validate($value, $this->constraint);

        if ($violation) {
            $this->buildViolation($violation)
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    public function validateDataProvider(): array
    {
        return [
            'is extend entity' => [
                'Test\Entity1',
                RelationType::MANY_TO_MANY,
                true,
                true,
                false
            ],
            'is not extend entity' => [
                'Test\Entity2',
                RelationType::MANY_TO_MANY,
                false,
                true,
                'The field can\'t be set to \'Yes\' when target entity isn\'t extended'
            ],
            'oneToMany bidirectional' => [
                'Test\Entity1',
                RelationType::ONE_TO_MANY,
                true,
                true,
                false
            ],
            'oneToMany not bidirectional' => [
                'Test\Entity1',
                RelationType::ONE_TO_MANY,
                false,
                false,
                'The field can\'t be set to \'No\' when relation type is \'oneToMany\''
            ],
            'oneToMany not bidirectional without parameter' => [
                'Test\Entity1',
                RelationType::ONE_TO_MANY,
                null,
                false,
                'The field can\'t be set to \'No\' when relation type is \'oneToMany\''
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function createValidator()
    {
        return new NonExtendedEntityBidirectionalValidator($this->configManager);
    }

    /**
     * @param string $type
     */
    private function configureRootFormFieldType($type)
    {
        $fieldConfigModel = new FieldConfigModel('whatever', $type);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->willReturn($fieldConfigModel);

        $this->rootMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);
    }
}
