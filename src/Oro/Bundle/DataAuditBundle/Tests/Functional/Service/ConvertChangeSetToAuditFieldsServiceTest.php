<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Service;

use Oro\Bundle\DataAuditBundle\Service\ConvertChangeSetToAuditFieldsService;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ConvertChangeSetToAuditFieldsServiceTest extends WebTestCase
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
        /** @var ConvertChangeSetToAuditFieldsService $service */
        $service = $this->getContainer()->get('oro_dataaudit.convert_change_set_to_audit_fields');

        $this->assertInstanceOf(ConvertChangeSetToAuditFieldsService::class, $service);
    }
}
