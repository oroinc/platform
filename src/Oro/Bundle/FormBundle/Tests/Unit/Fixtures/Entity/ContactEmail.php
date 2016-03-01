<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\FormBundle\Entity\PrimaryItem;

/**
 * @ORM\Entity()
 */
class ContactEmail implements PrimaryItem
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     */
    protected $email;

    /**
     * @ORM\Column(name="is_primary", type="boolean", nullable=true)
     */
    protected $primary;

    /**
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="emails")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $owner;

    /**
     * {@inheritdoc}
     */
    public function isPrimary()
    {
        return $this->primary;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrimary($value)
    {
        $this->primary = $value;
    }
}
