<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional\Serializer;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * Tests that JMS Serializer service is configured properly when JMSSerializerBundle is installed.
 */
class JmsSerializerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        if (!self::getContainer()->has('jms_serializer')) {
            $this->markTestSkipped('The JMSSerializerBundle is not installed.');
        }
    }

    public function testSerialize(): void
    {
        $data = new BusinessUnit();
        $data->setName('Test BU');

        $serializedData = self::getContainer()->get('jms_serializer')->serialize($data, 'json');
        self::assertEquals('{"name":"Test BU"}', $serializedData);
    }
}
