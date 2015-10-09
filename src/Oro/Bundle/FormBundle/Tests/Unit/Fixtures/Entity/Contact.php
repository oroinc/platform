<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Contact
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="ContactEmail", mappedBy="owner", cascade={"all"}, orphanRemoval=true)
     */
    protected $emails;
}
