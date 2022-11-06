<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractConfigTypeTestCase extends TypeTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $typeHelper;

    protected function setUp(): void
    {
        $this->typeHelper = $this->createMock(ConfigTypeHelper::class);

        parent::setUp();
    }

    protected function doTestConfigureOptions(
        AbstractType $type,
        ConfigIdInterface $configId,
        bool $immutable,
        array $options = [],
        array $expectedOptions = []
    ): array {
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

    protected function setIsReadOnlyExpectations(ConfigIdInterface $configId, bool $immutable): void
    {
        $className = $configId->getClassName();
        if (empty($className)) {
            $this->typeHelper->expects($this->never())
                ->method('isImmutable');
        } else {
            $this->typeHelper->expects($this->once())
                ->method('getFieldName')
                ->with($this->identicalTo($configId))
                ->willReturn($configId instanceof FieldConfigId ? $configId->getFieldName() : null);
            $this->typeHelper->expects($this->once())
                ->method('isImmutable')
                ->with(
                    $configId->getScope(),
                    $configId->getClassName(),
                    $configId instanceof FieldConfigId ? $configId->getFieldName() : null
                )
                ->willReturn($immutable);
        }
    }

    protected function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'config_id'         => null,
            'disabled'          => false,
            'validation_groups' => true
        ]);

        return $resolver;
    }

    public function configureOptionsProvider(): array
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
