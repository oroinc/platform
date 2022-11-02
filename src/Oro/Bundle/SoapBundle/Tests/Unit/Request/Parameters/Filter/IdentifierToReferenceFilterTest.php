<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Request\Parameters\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\IdentifierToReferenceFilter;
use Oro\Bundle\SoapBundle\Tests\Unit\Entity\Manager\Stub\Entity;

class IdentifierToReferenceFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testFilterWithIdentifierField()
    {
        $testClassName = Entity::class;
        $testReference = new \stdClass();
        $testId = 111;

        $em = $this->createMock(EntityManager::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $filter = new IdentifierToReferenceFilter($registry, $testClassName);

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($testClassName)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getReference')
            ->with($testClassName, $testId)
            ->willReturn($testReference);

        $this->assertSame($testReference, $filter->filter($testId, null));
    }

    public function testFilterWithNonIdentifierField()
    {
        $testClassName = Entity::class;
        $testFieldName = 'nickname';
        $testResult = new \stdClass();
        $testFieldValue = 'john_doe';

        $em = $this->createMock(EntityManager::class);
        $repo = $this->createMock(ObjectRepository::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $filter = new IdentifierToReferenceFilter($registry, $testClassName, $testFieldName);

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($testClassName)
            ->willReturn($em);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with([$testFieldName => $testFieldValue])
            ->willReturn($testResult);
        $em->expects($this->once())
            ->method('getRepository')
            ->with($testClassName)
            ->willReturn($repo);

        $this->assertSame($testResult, $filter->filter($testFieldValue, null));
    }
}
