<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class EmailNotification
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\OneToOne(targetEntity: RecipientList::class, cascade: ['all'], orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'recipient_list_id', referencedColumnName: 'id')]
    protected ?RecipientList $recipientList = null;
}
