<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity
 * @Table(name="cms_base_addresses")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type")
 * @DiscriminatorMap({
 *      "address"="CmsAddress",
 *      "base_address"="CmsBaseAddress",
 * })
 */
class CmsBaseAddress
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    public function __construct()
    {
    }

    public function getId()
    {
        return $this->id;
    }
}
