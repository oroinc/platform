<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS;

/**
 * @Entity
 * @Table(name="cms_organization")
 */
class CmsOrganization
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    public function getId()
    {
        return $this->id;
    }
}
