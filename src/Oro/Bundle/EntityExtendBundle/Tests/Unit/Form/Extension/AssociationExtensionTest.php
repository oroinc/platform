<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormView;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

use Oro\Bundle\EntityExtendBundle\Form\Extension\AssociationExtension;
use Oro\Bundle\EntityExtendBundle\Form\Type\AssociationChoiceType;

class AssociationExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY = 'Oro\Bundle\UserBundle\Entity\User';
    const TARGET_SCOPE = 'test';
    const TARGET_ENTITY = 'Test\Entity';

    /** @var AssociationExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $testConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var Config */
    protected $extendConfig;

    public function setUp()
    {
        $testConfig = new Config(new EntityConfigId(self::TARGET_SCOPE, self::ENTITY));
        $testConfig->set('enabled', 1);
        $this->testConfigProvider =
            $this->getMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');
        $this->testConfigProvider
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($testConfig));

        $this->extendConfig = new Config(new EntityConfigId('extend', self::ENTITY));
        $this->extendConfig->set(
            'relation',
            [
                'manyToOne|' . self::TARGET_ENTITY . '|' . self::ENTITY . '|user' => [
                    'assign'          => false,
                    'field_id'        => false,
                    'owner'           => false,
                    'target_entity'   => self::TARGET_ENTITY,
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        self::TARGET_ENTITY,
                        'user',
                        'manyToOne'
                    )
                ]
            ]
        );
        $this->extendConfigProvider = $this
            ->getMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager
            ->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        [self::TARGET_SCOPE, $this->testConfigProvider],
                        ['extend', $this->extendConfigProvider],
                    ]
                )
            );

        $entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnArgument(0));

        $this->extension = new AssociationExtension($configManager, $entityClassResolver);
    }

    public function testGetExtendedType()
    {
        $this->assertSame(AssociationChoiceType::NAME, $this->extension->getExtendedType());
    }

    public function testBuildViewWithRelation()
    {
        $extendConfigForTargetEntity = new Config(new EntityConfigId('extend', self::TARGET_ENTITY));
        $extendConfigForTargetEntity->set(
            'relation',
            [
                'manyToOne|' . self::TARGET_ENTITY . '|' . self::ENTITY . '|user' => [
                    'assign' => true
                ]
            ]
        );
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::ENTITY, null, $this->extendConfig],
                        [self::TARGET_ENTITY, null, $extendConfigForTargetEntity],
                    ]
                )
            );

        $view     = $this->callBuildView();
        $expected = [
            'attr' => ['class' => 'disabled-choice'],
        ];
        foreach ($expected as $option => $value) {
            $this->assertArrayHasKey($option, $view->vars);
            $this->assertEquals($value, $view->vars[$option]);
        }
    }

    public function testBuildViewWithRelationAndCssClass()
    {
        $extendConfigForTargetEntity = new Config(new EntityConfigId('extend', self::TARGET_ENTITY));
        $extendConfigForTargetEntity->set(
            'relation',
            [
                'manyToOne|' . self::TARGET_ENTITY . '|' . self::ENTITY . '|user' => [
                    'assign' => true
                ]
            ]
        );
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::ENTITY, null, $this->extendConfig],
                        [self::TARGET_ENTITY, null, $extendConfigForTargetEntity],
                    ]
                )
            );

        $view     = $this->callBuildView(['attr' => ['class' => 'someCssClass']]);
        $expected = [
            'attr' => ['class' => 'someCssClass disabled-choice']
        ];
        foreach ($expected as $option => $value) {
            $this->assertArrayHasKey($option, $view->vars);
            $this->assertEquals($value, $view->vars[$option]);
        }
    }

    public function testBuildViewWithOutRelation()
    {
        $extendConfigForTargetEntity = new Config(new EntityConfigId('extend', self::TARGET_ENTITY));
        $extendConfigForTargetEntity->set(
            'relation',
            [
                'manyToOne|' . self::TARGET_ENTITY . '|' . self::ENTITY . '|user' => [
                    'assign' => false
                ]
            ]
        );
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::ENTITY, null, $this->extendConfig],
                        [self::TARGET_ENTITY, null, $extendConfigForTargetEntity],
                    ]
                )
            );

        $view     = $this->callBuildView();
        $expected = [
            'attr' => []
        ];
        foreach ($expected as $option => $value) {
            $this->assertArrayHasKey($option, $view->vars);
            $this->assertEquals($value, $view->vars[$option]);
        }
    }

    /**
     * @param array $vars
     *
     * @return FormView
     */
    protected function callBuildView($vars = [])
    {
        $options = [
            'config_id'                    => new EntityConfigId(self::TARGET_SCOPE, self::ENTITY),
            'entity_class'                 => self::TARGET_ENTITY,
            'entity_config_scope'          => self::TARGET_SCOPE,
            'entity_config_attribute_name' => 'enabled'
        ];

        $view = new FormView();

        if ($vars) {
            $view->vars = array_merge($view->vars, $vars);
        }

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('enabled'));

        $this->extension->buildView($view, $form, $options);

        return $view;
    }
}
