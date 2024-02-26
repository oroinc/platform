<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'test_target')]
class Target
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        $this->id = $id;
    }
}
