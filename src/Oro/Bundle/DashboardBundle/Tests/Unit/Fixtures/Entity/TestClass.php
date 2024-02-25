<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class TestClass
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    protected ?string $existing = null;

    public function getExisting()
    {
        return $this->existing;
    }

    public function setExisting($existing)
    {
        $this->existing = $existing;
    }
}
