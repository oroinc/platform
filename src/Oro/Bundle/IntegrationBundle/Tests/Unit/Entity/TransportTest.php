<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use PHPUnit\Framework\TestCase;

class TransportTest extends TestCase
{
    public function testEntityMethods(): void
    {
        $entity = $this->getMockForAbstractClass(Transport::class);
        $this->assertNull($entity->getId());
        $this->assertNull($entity->getChannel());
    }
}
