<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Metadata;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

abstract class AbstractMetadataTest extends OrmTestCase
{
    /**
     * @var EntityManagerMock
     */
    protected $em;

    /**
     * @var \Oro\Bundle\DataAuditBundle\Metadata\Driver\AnnotationDriver
     */
    protected $loggableAnnotationDriver;

    public function setUp()
    {
        $reader = new AnnotationReader();

        $metadataDriver = new AnnotationDriver($reader, 'Oro\Bundle\DataAuditBundle\Tests\Unit\Fixture');

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setEntityNamespaces(
            array(
                'OroUserBundle' => 'Oro\\Bundle\\UserBundle\\Entity',
                'OroDataAuditBundle' => 'Oro\\Bundle\\DataAuditBundle\\Entity'
            )
        );
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);

        $this->loggableAnnotationDriver = new \Oro\Bundle\DataAuditBundle\Metadata\Driver\AnnotationDriver(
            new AnnotationReader()
        );
    }
}
