<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'test_activity')]
class Activity
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var Collection<int, Target>
     */
    #[ORM\ManyToMany(targetEntity: Target::class)]
    #[ORM\JoinTable(name: 'test_activity_to_target')]
    protected ?Collection $target_bcaa0d48 = null;
}
