<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Provider;

use Oro\Bundle\NotificationBundle\Provider\AdditionalEmailAssociationProviderInterface;
use Oro\Bundle\NotificationBundle\Provider\ChainAdditionalEmailAssociationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChainAdditionalEmailAssociationProviderTest extends TestCase
{
    private AdditionalEmailAssociationProviderInterface&MockObject $provider1;
    private AdditionalEmailAssociationProviderInterface&MockObject $provider2;
    private ChainAdditionalEmailAssociationProvider $chainProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider1 = $this->createMock(AdditionalEmailAssociationProviderInterface::class);
        $this->provider2 = $this->createMock(AdditionalEmailAssociationProviderInterface::class);

        $this->chainProvider = new ChainAdditionalEmailAssociationProvider(
            [$this->provider1, $this->provider2]
        );
    }

    public function testGetAssociations(): void
    {
        $this->provider1->expects(self::once())
            ->method('getAssociations')
            ->willReturn([
                'commonAssociation' => ['label' => 'commonField_association_1', 'target_class' => 'Test\Entity1'],
                'association_1_1'   => ['label' => 'association_label_1_1', 'target_class' => 'Test\Entity1_1'],
                'association_1_2'   => ['label' => 'association_label_1_2', 'target_class' => 'Test\Entity1_2']
            ]);
        $this->provider2->expects(self::once())
            ->method('getAssociations')
            ->willReturn([
                'commonAssociation' => ['label' => 'commonField_association_2', 'target_class' => 'Test\Entity2'],
                'association_2_1'   => ['label' => 'association_label_2_1', 'target_class' => 'Test\Entity2_1'],
                'association_2_2'   => ['label' => 'association_label_2_2', 'target_class' => 'Test\Entity2_2']
            ]);

        self::assertEquals(
            [
                'commonAssociation' => ['label' => 'commonField_association_1', 'target_class' => 'Test\Entity1'],
                'association_1_1'   => ['label' => 'association_label_1_1', 'target_class' => 'Test\Entity1_1'],
                'association_1_2'   => ['label' => 'association_label_1_2', 'target_class' => 'Test\Entity1_2'],
                'association_2_1'   => ['label' => 'association_label_2_1', 'target_class' => 'Test\Entity2_1'],
                'association_2_2'   => ['label' => 'association_label_2_2', 'target_class' => 'Test\Entity2_2']
            ],
            $this->chainProvider->getAssociations(\stdClass::class)
        );
    }

    public function testGetAssociationValueWithSupportedProvider(): void
    {
        $entity = new \stdClass();
        $entity->testField = 'test';

        $this->provider1->expects(self::once())
            ->method('isAssociationSupported')
            ->with($entity, 'testField')
            ->willReturn(false);
        $this->provider2->expects(self::once())
            ->method('isAssociationSupported')
            ->with($entity, 'testField')
            ->willReturn(true);

        $this->provider1->expects(self::never())
            ->method('getAssociationValue');
        $this->provider2->expects(self::once())
            ->method('getAssociationValue')
            ->with($entity, 'testField')
            ->willReturn($entity->testField);

        self::assertEquals('test', $this->chainProvider->getAssociationValue($entity, 'testField'));
    }

    public function testGetAssociationValueWithoutSupportedProvider(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('There is no provider to get the value.');

        $entity = new \stdClass();

        $this->provider1->expects(self::once())
            ->method('isAssociationSupported')
            ->with($entity, 'testField')
            ->willReturn(false);
        $this->provider2->expects(self::once())
            ->method('isAssociationSupported')
            ->with($entity, 'testField')
            ->willReturn(false);

        $this->provider1->expects(self::never())
            ->method('getAssociationValue');
        $this->provider2->expects(self::never())
            ->method('getAssociationValue');

        $this->chainProvider->getAssociationValue($entity, 'testField');
    }
}
