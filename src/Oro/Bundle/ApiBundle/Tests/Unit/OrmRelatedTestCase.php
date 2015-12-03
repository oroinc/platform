<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class OrmRelatedTestCase extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

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

        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);
        $doctrine->expects($this->any())
            ->method('getAliasNamespace')
            ->will(
                $this->returnValueMap(
                    [
                        ['Test', 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity']
                    ]
                )
            );

        $this->doctrineHelper = new DoctrineHelper($doctrine);
    }
}
