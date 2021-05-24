<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Step;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Model\Step\RemoveEntitiesStep;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class RemoveEntitiesStepTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var RemoveEntitiesStep */
    private $step;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->step = new RemoveEntitiesStep($this->entityManager, $this->doctrineHelper);
    }

    public function testRun()
    {
        $foo = new EntityStub(1);
        $bar = new EntityStub(2);
        $baz = new EntityStub(3);

        $data = $this->createMock(EntityData::class);
        $data->expects($this->once())
            ->method('getMasterEntity')
            ->willReturn($foo);
        $data->expects($this->once())
            ->method('getEntities')
            ->willReturn([$foo, $bar, $baz]);

        $this->doctrineHelper->expects($this->exactly(3))
            ->method('isEntityEqual')
            ->willReturnMap([
                [$foo, $foo, true],
                [$foo, $bar, false],
                [$foo, $baz, false]
            ]);

        $this->entityManager->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive(
                [$this->identicalTo($bar)],
                [$this->identicalTo($baz)]
            );

        $this->step->run($data);
    }
}
