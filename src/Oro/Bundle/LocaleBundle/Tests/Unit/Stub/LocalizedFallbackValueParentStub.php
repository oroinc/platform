<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class LocalizedFallbackValueParentStub
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Collection|LocalizedFallbackValue[]
     */
    protected $localizedFields;

    public function __construct()
    {
        $this->localizedFields = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
