<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener\ORM;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Id\BigIntegerIdentityGenerator;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\EventListener\ORM\GeneratedValueStrategyListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GeneratedValueStrategyListenerTest extends TestCase
{
    private LoadClassMetadataEventArgs&MockObject $event;
    private ClassMetadataInfo&MockObject $metadata;
    private GeneratedValueStrategyListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->event = $this->createMock(LoadClassMetadataEventArgs::class);
        $this->metadata = $this->createMock(ClassMetadataInfo::class);

        $this->listener = new GeneratedValueStrategyListener();
    }

    public function testNotIdGeneratorSequence(): void
    {
        $this->event->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($this->metadata);

        $this->metadata->expects($this->once())
            ->method('isIdGeneratorSequence')
            ->willReturn(false);

        $this->listener->loadClassMetadata($this->event);
    }

    /**
     * @dataProvider identityGeneratorProvider
     */
    public function testIdentityGenerator(
        string $field,
        string $sequence,
        string $type,
        AbstractIdGenerator $generator
    ): void {
        $this->event->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($this->metadata);

        $this->metadata->sequenceGeneratorDefinition = ['sequenceName' => $sequence];

        $this->metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn($field);
        $this->metadata->expects($this->once())
            ->method('isIdGeneratorSequence')
            ->willReturn(true);
        $this->metadata->expects($this->once())
            ->method('getFieldMapping')
            ->with($field)
            ->willReturn(['type' => $type]);
        $this->metadata->expects($this->once())
            ->method('setIdGeneratorType')
            ->with(ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $this->metadata->expects($this->once())
            ->method('setIdGenerator')
            ->with($generator);

        $this->listener->loadClassMetadata($this->event);
    }

    public function identityGeneratorProvider(): array
    {
        return [
            ['id', 'id_field_seq', Types::INTEGER, new IdentityGenerator('id_field_seq')],
            ['id', 'id_field_seq', Types::BIGINT, new BigIntegerIdentityGenerator('id_field_seq')],
        ];
    }
}
