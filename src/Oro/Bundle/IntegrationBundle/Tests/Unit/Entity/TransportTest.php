<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Transport;

class TransportTest extends \PHPUnit\Framework\TestCase
{
    public function testEntityMethods()
    {
        $entity = $this->getMockForAbstractClass(Transport::class);
        $this->assertNull($entity->getId());
        $this->assertNull($entity->getChannel());
    }
}
