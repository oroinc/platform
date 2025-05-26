<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class OrmRelatedTestCase extends OrmTestCase
{
    protected EntityManagerInterface $em;
    protected ManagerRegistry&MockObject $doctrine;
    protected DoctrineHelper|MockObject $doctrineHelper;

    /** @var string[] */
    protected $notManageableClassNames = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AttributeDriver([]));
        $this->em->getConfiguration()->setEntityNamespaces([
            'Test' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity'
        ]);

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturnCallback(function ($class) {
                return !in_array($class, $this->notManageableClassNames, true)
                    ? $this->em
                    : null;
            });

        $this->doctrineHelper = new DoctrineHelper($this->doctrine);
    }
}
