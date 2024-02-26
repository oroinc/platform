<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Contact
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var Collection<int, ContactEmail>
     */
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: ContactEmail::class, cascade: ['all'], orphanRemoval: true)]
    protected ?Collection $emails = null;
}
