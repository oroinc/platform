<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="mailbox_table")
 */
class Mailbox
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=50)
     */
    protected $name;

    /**
     * @ORM\OneToOne(targetEntity="Origin", inversedBy="mailbox")
     * @ORM\JoinColumn(name="origin_id", referencedColumnName="id", nullable=true)
     */
    protected $origin;

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

    /**
     * @param Origin|null $origin
     */
    public function setOrigin(Origin $origin = null)
    {
        $this->origin = $origin;
    }
}
