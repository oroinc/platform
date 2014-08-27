<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class AbstractConfigTypeTestCase extends TypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    /**
     * @param AbstractType      $type
     * @param ConfigIdInterface $configId
     * @param bool              $hasConfig
     * @param bool|null         $immutable
     * @param array             $options
     * @param array             $expectedOptions
     *
     * @return array
     */
    protected function doTestSetDefaultOptions(
        AbstractType $type,
        ConfigIdInterface $configId,
        $hasConfig,
        $immutable,
        array $options = [],
        array $expectedOptions = []
    ) {
        $this->setIsReadOnlyExpectations($configId, $hasConfig, $immutable);

        $resolver = $this->getOptionsResolver();
        $type->setDefaultOptions($resolver);

        $options['config_id'] = $configId;

        $resolvedOptions = $resolver->resolve($options);

        foreach ($expectedOptions as $name => $val) {
            $this->assertEquals($val, $resolvedOptions[$name], $name);
            unset($resolvedOptions[$name]);
        }

        return $resolvedOptions;
    }

    /**
     * @param ConfigIdInterface $configId
     * @param bool              $hasConfig
     * @param bool|null         $immutable
     */
    protected function setIsReadOnlyExpectations(
        ConfigIdInterface $configId,
        $hasConfig,
        $immutable
    ) {
        $className = $configId->getClassName();
        if (empty($className)) {
            $this->configManager->expects($this->never())
                ->method('getProvider');
        } else {
            $configProvider = $this->getConfigProviderMock();
            $this->configManager->expects($this->once())
                ->method('getProvider')
                ->with($configId->getScope())
                ->will($this->returnValue($configProvider));
            $configProvider->expects($this->once())
                ->method('hasConfig')
                ->with($className, $configId instanceof FieldConfigId ? $configId->getFieldName() : null)
                ->will($this->returnValue($hasConfig));
            if ($hasConfig) {
                $config = new Config($configId);
                if ($immutable !== null) {
                    $config->set('immutable', $immutable);
                }
                $configProvider->expects($this->once())
                    ->method('getConfig')
                    ->with($className, $configId instanceof FieldConfigId ? $configId->getFieldName() : null)
                    ->will($this->returnValue($config));
            } else {
                $configProvider->expects($this->never())
                    ->method('getConfig');
            }
        }
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
                'config_id'         => null,
                'disabled'          => false,
                'validation_groups' => true
            ]
        );

        return $resolver;
    }

    public function setDefaultOptionsProvider()
    {
        return [
            [
                new EntityConfigId('test', null),
                false,
                null,
                [],
                ['disabled' => false, 'validation_groups' => true]
            ],
            [
                new EntityConfigId('test', 'Test\Entity'),
                false,
                null,
                [],
                ['disabled' => false, 'validation_groups' => true]
            ],
            [
                new FieldConfigId('test', 'Test\Entity', 'testField'),
                false,
                null,
                [],
                ['disabled' => false, 'validation_groups' => true]
            ],
            [
                new EntityConfigId('test', 'Test\Entity'),
                true,
                null,
                [],
                ['disabled' => false, 'validation_groups' => true]
            ],
            [
                new FieldConfigId('test', 'Test\Entity', 'testField'),
                true,
                null,
                [],
                ['disabled' => false, 'validation_groups' => true]
            ],
            [
                new EntityConfigId('test', 'Test\Entity'),
                true,
                false,
                [],
                ['disabled' => false, 'validation_groups' => true]
            ],
            [
                new FieldConfigId('test', 'Test\Entity', 'testField'),
                true,
                false,
                [],
                ['disabled' => false, 'validation_groups' => true]
            ],
            [
                new EntityConfigId('test', 'Test\Entity'),
                false,
                null,
                ['disabled' => true],
                ['disabled' => true, 'validation_groups' => false]
            ],
            [
                new FieldConfigId('test', 'Test\Entity', 'testField'),
                false,
                null,
                ['disabled' => true],
                ['disabled' => true, 'validation_groups' => false]
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
