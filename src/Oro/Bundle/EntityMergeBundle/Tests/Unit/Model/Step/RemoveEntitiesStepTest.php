<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Step;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Model\Step\RemoveEntitiesStep;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoveEntitiesStepTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private DoctrineHelper&MockObject $doctrineHelper;
    private RemoveEntitiesStep $step;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManager')
            ->willReturn($this->entityManager);

        $this->step = new RemoveEntitiesStep($doctrine, $this->doctrineHelper);
    }

    public function testRun(): void
    {
        $foo = new EntityStub(1);
        $bar = new EntityStub(2);
        $baz = new EntityStub(3);

        $data = $this->createMock(EntityData::class);
        $data->expects(self::once())
            ->method('getMasterEntity')
            ->willReturn($foo);
        $data->expects(self::once())
            ->method('getEntities')
            ->willReturn([$foo, $bar, $baz]);

        $this->doctrineHelper->expects(self::exactly(3))
            ->method('isEntityEqual')
            ->willReturnMap([
                [$foo, $foo, true],
                [$foo, $bar, false],
                [$foo, $baz, false]
            ]);

        $this->entityManager->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive(
                [$this->identicalTo($bar)],
                [$this->identicalTo($baz)]
            );

        $this->step->run($data);
    }
}
