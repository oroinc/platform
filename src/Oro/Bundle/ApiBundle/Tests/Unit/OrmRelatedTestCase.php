<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\ORMException;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class OrmRelatedTestCase extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $doctrine;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string[] */
    protected $notManageableClassNames = [];

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Test' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity'
            ]
        );

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturnCallback(
                function ($class) {
                    return !in_array($class, $this->notManageableClassNames, true)
                        ? $this->em
                        : null;
                }
            );
        $this->doctrine->expects(self::any())
            ->method('getAliasNamespace')
            ->willReturnCallback(
                function ($alias) {
                    if ('Test' !== $alias) {
                        throw ORMException::unknownEntityNamespace($alias);
                    }

                    return 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity';
                }
            );

        $this->doctrineHelper = new DoctrineHelper($this->doctrine);
    }
}
