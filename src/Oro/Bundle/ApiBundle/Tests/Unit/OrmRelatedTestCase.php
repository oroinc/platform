<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\ORMException;

use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class OrmRelatedTestCase extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
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

        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnCallback(
                function ($class) {
                    return !in_array($class, $this->notManageableClassNames, true)
                        ? $this->em
                        : null;
                }
            );
        $this->doctrine->expects($this->any())
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getQueryBuilderMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
