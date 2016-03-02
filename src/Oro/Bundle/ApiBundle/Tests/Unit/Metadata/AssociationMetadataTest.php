<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

class AssociationMetadataTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityMetadata */
    protected $entityMetadata;

    protected function setUp()
    {
        $this->entityMetadata = new EntityMetadata();
        $this->entityMetadata->setClassName('entityClassName');
        $this->entityMetadata->setInheritedType(true);
    }

    public function testActions()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertNull($associationMetadata->getTargetClassName());
        $associationMetadata->setTargetClassName('targetClassName');
        $this->assertSame('targetClassName', $associationMetadata->getTargetClassName());

        $this->assertNull($associationMetadata->getTargetMetadata());
        $associationMetadata->setTargetMetadata($this->entityMetadata);
        $this->assertSame($this->entityMetadata, $associationMetadata->getTargetMetadata());

        $this->assertSame([], $associationMetadata->getAcceptableTargetClassNames());
        $associationMetadata->setAcceptableTargetClassNames(['targetClassName0', 'targetClassName1']);
        $this->assertSame(
            ['targetClassName0', 'targetClassName1'],
            $associationMetadata->getAcceptableTargetClassNames()
        );
        $associationMetadata->removeAcceptableTargetClassName('targetClassName0');
        $associationMetadata->addAcceptableTargetClassName('targetClassName2');
        $this->assertSame(
            ['targetClassName1', 'targetClassName2'],
            $associationMetadata->getAcceptableTargetClassNames()
        );

        $this->assertFalse($associationMetadata->isCollection());
        $associationMetadata->setIsCollection(true);
        $this->assertTrue($associationMetadata->isCollection());

        $this->assertSame(
            [
                AssociationMetadata::TARGET_CLASS_NAME => 'targetClassName',
                AssociationMetadata::ACCEPTABLE_TARGET_CLASS_NAMES => ['targetClassName1', 'targetClassName2'],
                AssociationMetadata::COLLECTION => true,
                'targetMetadata' => [
                    'class' => 'entityClassName',
                    'inherited' => true,
                    'identifiers' => []
                ]
            ],
            $associationMetadata->toArray()
        );
    }
}
