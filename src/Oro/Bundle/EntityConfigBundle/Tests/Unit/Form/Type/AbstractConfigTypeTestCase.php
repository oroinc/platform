<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;

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
     * @param array             $expectedViewVars
     */
    protected function doTestBuildView(
        AbstractType $type,
        ConfigIdInterface $configId,
        $hasConfig,
        $immutable,
        $expectedViewVars
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

        $view = new FormView();
        $type->buildView(
            $view,
            $this->getMock('Symfony\Component\Form\Test\FormInterface'),
            [
                'config_id' => $configId
            ]
        );

        $expectedViewVars = array_merge(
            [
                'value' => null,
                'attr'  => []
            ],
            $expectedViewVars
        );

        $this->assertEquals(
            $expectedViewVars,
            $view->vars
        );
    }

    public function buildViewProvider()
    {
        return [
            [
                new EntityConfigId('test', null),
                false,
                null,
                []
            ],
            [
                new EntityConfigId('test', 'Test\Entity'),
                false,
                null,
                []
            ],
            [
                new FieldConfigId('test', 'Test\Entity', 'testField'),
                false,
                null,
                []
            ],
            [
                new EntityConfigId('test', 'Test\Entity'),
                true,
                null,
                []
            ],
            [
                new FieldConfigId('test', 'Test\Entity', 'testField'),
                true,
                null,
                []
            ],
            [
                new EntityConfigId('test', 'Test\Entity'),
                true,
                false,
                []
            ],
            [
                new FieldConfigId('test', 'Test\Entity', 'testField'),
                true,
                false,
                []
            ],
            [
                new EntityConfigId('test', 'Test\Entity'),
                true,
                true,
                ['disabled' => true]
            ],
            [
                new FieldConfigId('test', 'Test\Entity', 'testField'),
                true,
                true,
                ['disabled' => true]
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
