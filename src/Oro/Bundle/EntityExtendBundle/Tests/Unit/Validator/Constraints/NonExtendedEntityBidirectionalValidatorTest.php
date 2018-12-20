<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NonExtendedEntityBidirectional;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NonExtendedEntityBidirectionalValidator;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NonExtendedEntityBidirectionalValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var Form|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $rootMock;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();

        $this->rootMock = $this->getMockBuilder(Form::class)->disableOriginalConstructor()->getMock();
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

        $this->configManager->method('getEntityConfig')
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

    /**
     * @return array
     */
    public function validateDataProvider()
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
     * @return NonExtendedEntityBidirectionalValidator
     */
    protected function createValidator()
    {
        return new NonExtendedEntityBidirectionalValidator($this->configManager);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string $type
     */
    protected function configureRootFormFieldType($type)
    {
        $fieldConfigModel = new FieldConfigModel('whatever', $type);
        $formConfig = $this->getMockBuilder(FormConfigInterface::class)->getMock();
        $formConfig->expects($this->once())->method('getOption')->willReturn($fieldConfigModel);

        $this->rootMock->expects($this->once())->method('getConfig')->willReturn($formConfig);
    }
}
