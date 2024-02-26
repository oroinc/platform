<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;

#[ORM\Entity(repositoryClass: ChannelRepository::class)]
#[ORM\Table(name: 'oro_integration_channel')]
class Channel
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::SMALLINT)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 255)]
    protected ?string $type = null;
}
