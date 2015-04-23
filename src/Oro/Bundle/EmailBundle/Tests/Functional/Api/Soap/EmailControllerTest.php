<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Soap;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Rhumsaa\Uuid\Console\Exception;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailControllerTest extends WebTestCase
{
    const INCORRECT_ID = 1111;

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

        $this->setExpectedException('\SoapFault', printf('Email with id "%s" can not be found', self::INCORRECT_ID));
        $this->soapClient->postAssociation(self::INCORRECT_ID, 'Oro_Bundle_UserBundle_Entity_User', $userId);

        $this->soapClient->deleteAssociation($emailId, 'Oro_Bundle_UserBundle_Entity_User', $userId);
        $result = $this->soapClient->postAssociation($emailId, 'Oro_Bundle_UserBundle_Entity_User', $userId);

        $this->assertTrue(true, $result);
    }

    public function testDeleteAssociation()
    {
        $userId = $this->getReference('simple_user2')->getId();

        $this->setExpectedException('\SoapFault', printf('Email with id "%s" can not be found', self::INCORRECT_ID));
        $this->soapClient->deleteAssociation(self::INCORRECT_ID, 'Oro_Bundle_UserBundle_Entity_User', $userId);

        $emailId = (int)$this->getReference('email_1')->getId();
        $result = $this->soapClient->deleteAssociation($emailId, 'Oro_Bundle_UserBundle_Entity_User', $userId);

        $this->assertTrue($result);
    }
}
