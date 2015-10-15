<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class EmailNotification
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="RecipientList", cascade={"all"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="recipient_list_id", referencedColumnName="id")
     */
    protected $recipientList;
}
