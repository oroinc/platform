<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener\ORM;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Id\BigIntegerIdentityGenerator;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\EventListener\ORM\GeneratedValueStrategyListener;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;

class GeneratedValueStrategyListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $event;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $metadata;

    /**
     * @var GeneratedValueStrategyListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->event = $this
            ->getMockBuilder('Doctrine\ORM\Event\LoadClassMetadataEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadata = $this
            ->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new GeneratedValueStrategyListener(
            DatabaseDriverInterface::DRIVER_POSTGRESQL
        );
    }

    public function testPlatformNotMatch()
    {
        $listener = new GeneratedValueStrategyListener('not_postgres');

        $this->event
            ->expects($this->never())
            ->method('getClassMetadata');

        $listener->loadClassMetadata($this->event);
    }

    public function testNotIdGeneratorSequence()
    {
        $this->event
            ->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($this->metadata));

        $this->metadata
            ->expects($this->once())
            ->method('isIdGeneratorSequence')
            ->will($this->returnValue(false));

        $this->listener->loadClassMetadata($this->event);
    }

    /**
     * @param string              $field
     * @param string              $sequence
     * @param string              $type
     * @param AbstractIdGenerator $generator
     *
     * @dataProvider identityGeneratorProvider
     */
    public function testIdentityGenerator($field, $sequence, $type, AbstractIdGenerator $generator)
    {
        $this->event
            ->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($this->metadata));

        $this->metadata->sequenceGeneratorDefinition = ['sequenceName' => $sequence];

        $this->metadata
            ->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue($field));

        $this->metadata
            ->expects($this->once())
            ->method('isIdGeneratorSequence')
            ->will($this->returnValue(true));

        $this->metadata
            ->expects($this->once())
            ->method('getFieldMapping')
            ->with($this->equalTo($field))
            ->will($this->returnValue(['type' => $type]));

        $this->metadata
            ->expects($this->once())
            ->method('setIdGeneratorType')
            ->with($this->equalTo(ClassMetadata::GENERATOR_TYPE_IDENTITY));

        $this->metadata
            ->expects($this->once())
            ->method('setIdGenerator')
            ->with($this->equalTo($generator));

        $this->listener->loadClassMetadata($this->event);
    }

    /**
     * @return array
     */
    public function identityGeneratorProvider()
    {
        return [
            ['id', 'id_field_seq', Type::INTEGER, new IdentityGenerator('id_field_seq')],
            ['id', 'id_field_seq', Type::BIGINT, new BigIntegerIdentityGenerator('id_field_seq')],
        ];
    }
}
