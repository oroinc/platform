<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'mailbox_table')]
class Mailbox
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 50)]
    protected ?string $name = null;

    #[ORM\OneToOne(inversedBy: 'mailbox', targetEntity: Origin::class)]
    #[ORM\JoinColumn(name: 'origin_id', referencedColumnName: 'id', nullable: true)]
    protected ?Origin $origin = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Origin|null
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    public function setOrigin(Origin $origin = null)
    {
        $this->origin = $origin;
    }
}
