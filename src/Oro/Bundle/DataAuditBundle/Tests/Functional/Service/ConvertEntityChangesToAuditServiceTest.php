<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Service;

use Oro\Bundle\DataAuditBundle\Service\ConvertEntityChangesToAuditService;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ConvertEntityChangesToAuditServiceTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient([], [], true);
        $this->startTransaction();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->rollbackTransaction();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        /** @var ConvertEntityChangesToAuditService $service */
        $service = $this->getContainer()->get('oro_dataaudit.convert_entity_changes_to_audit');

        $this->assertInstanceOf(ConvertEntityChangesToAuditService::class, $service);
    }
}
