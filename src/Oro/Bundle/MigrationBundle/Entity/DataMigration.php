<?php

namespace Oro\Bundle\MigrationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Data Migration
*
*/
#[ORM\Entity]
#[ORM\Table('oro_migrations')]
#[ORM\Index(columns: ['bundle'], name: 'idx_oro_migrations')]
class DataMigration
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'bundle', type: Types::STRING, length: 250)]
    protected ?string $bundle = null;

    #[ORM\Column(name: 'version', type: Types::STRING, length: 250)]
    protected ?string $version = null;

    #[ORM\Column(name: 'loaded_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $loadedAt = null;
}
