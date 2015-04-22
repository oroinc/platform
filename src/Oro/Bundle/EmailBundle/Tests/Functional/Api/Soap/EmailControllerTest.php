<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Soap;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->initSoapClient();
        $this->loadFixtures(['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData']);
    }

    public function testPostAssociation()
    {
        $emailId = (int)$this->getReference('email_1')->getId();
        $userId = $this->getReference('simple_user2')->getId();
        $this->soapClient->deleteAssociation($emailId, 'Oro_Bundle_UserBundle_Entity_User', $userId);
        $result = $this->soapClient->postAssociation($emailId, 'Oro_Bundle_UserBundle_Entity_User', $userId);

        $this->assertTrue(true, $result);
    }

    public function testDeleteAssociation()
    {
        $emailId = (int)$this->getReference('email_1')->getId();
        $userId = $this->getReference('simple_user2')->getId();
        $result = $this->soapClient->deleteAssociation($emailId, 'Oro_Bundle_UserBundle_Entity_User', $userId);

        $this->assertTrue($result);
    }
}
