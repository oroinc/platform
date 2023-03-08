<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class OrmRelatedTestCase extends OrmTestCase
{
    /** @var EntityManagerInterface */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $doctrine;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string[] */
    protected $notManageableClassNames = [];

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
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
        $this->doctrine->expects(self::any())
            ->method('getAliasNamespace')
            ->willReturnCallback(function ($alias) {
                if ('Test' !== $alias) {
                    throw ORMException::unknownEntityNamespace($alias);
                }

                return 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity';
            });

        $this->doctrineHelper = new DoctrineHelper($this->doctrine);
    }
}
