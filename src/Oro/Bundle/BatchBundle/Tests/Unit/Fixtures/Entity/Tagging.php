<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Tagging
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tag::class, inversedBy: 'tagging')]
    #[ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id')]
    protected ?Tag $tag = null;

    #[ORM\Column(name: 'entity_name', type: Types::STRING, length: 100)]
    protected ?string $entityName = null;

    #[ORM\Column(name: 'record_id', type: Types::INTEGER)]
    protected ?int $recordId = null;
}
