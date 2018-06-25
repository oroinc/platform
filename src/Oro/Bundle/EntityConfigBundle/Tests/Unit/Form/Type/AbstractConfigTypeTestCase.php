<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractConfigTypeTestCase extends TypeTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $typeHelper;

    protected function setUp()
    {
        $this->typeHelper = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper')
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    /**
     * @param AbstractType      $type
     * @param ConfigIdInterface $configId
     * @param bool              $immutable
     * @param array             $options
     * @param array             $expectedOptions
     *
     * @return array
     */
    protected function doTestConfigureOptions(
        AbstractType $type,
        ConfigIdInterface $configId,
        $immutable,
        array $options = [],
        array $expectedOptions = []
    ) {
        $this->setIsReadOnlyExpectations($configId, $immutable);

        $resolver = $this->getOptionsResolver();
        $type->configureOptions($resolver);

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
     * @param bool              $immutable
     */
    protected function setIsReadOnlyExpectations(
        ConfigIdInterface $configId,
        $immutable
    ) {
        $className = $configId->getClassName();
        if (empty($className)) {
            $this->typeHelper->expects($this->never())
                ->method('isImmutable');
        } else {
            $this->typeHelper->expects($this->once())
                ->method('getFieldName')
                ->with($this->identicalTo($configId))
                ->will($this->returnValue($configId instanceof FieldConfigId ? $configId->getFieldName() : null));
            $this->typeHelper->expects($this->once())
                ->method('isImmutable')
                ->with(
                    $configId->getScope(),
                    $configId->getClassName(),
                    $configId instanceof FieldConfigId ? $configId->getFieldName() : null
                )
                ->will($this->returnValue($immutable));
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

    /**
     * @return array
     */
    public function configureOptionsProvider()
    {
        return [
            [
                new EntityConfigId('test', null),
                false,
                [],
                ['disabled' => false, 'validation_groups' => true]
            ],
            [
                new EntityConfigId('test', 'Test\Entity'),
                false,
                [],
                ['disabled' => false, 'validation_groups' => true]
            ],
            [
                new FieldConfigId('test', 'Test\Entity', 'testField'),
                false,
                [],
                ['disabled' => false, 'validation_groups' => true]
            ],
            [
                new EntityConfigId('test', 'Test\Entity'),
                true,
                [],
                ['disabled' => true, 'validation_groups' => false]
            ],
            [
                new FieldConfigId('test', 'Test\Entity', 'testField'),
                true,
                [],
                ['disabled' => true, 'validation_groups' => false]
            ],
            [
                new EntityConfigId('test', 'Test\Entity'),
                false,
                ['disabled' => true],
                ['disabled' => true, 'validation_groups' => false]
            ],
            [
                new FieldConfigId('test', 'Test\Entity', 'testField'),
                false,
                ['disabled' => true],
                ['disabled' => true, 'validation_groups' => false]
            ],
        ];
    }
}
