<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Form\Type;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadAllRolesData;

class MailboxTypeTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadUserData::class, LoadAllRolesData::class]);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_email_mailbox_create'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var User $user1 */
        $user1 = $this->getReference('simple_user');
        /** @var User $user2 */
        $user2 = $this->getReference('simple_user2');

        /** @var Role $role1 */
        $role1 = $this->getReference('role.role_administrator');
        /** @var Role $role2 */
        $role2 = $this->getReference('role.role_user');

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_email_mailbox[label]'] = 'Test Mailbox';
        $form['oro_email_mailbox[email]'] = 'test@example.org';
        $form['oro_email_mailbox[authorizedUsers]'] = implode(',', [$user1->getId(), $user2->getId()]);
        $form['oro_email_mailbox[authorizedRoles]'] = implode(',', [$role1->getId(), $role2->getId()]);

        $this->client->submit($form);

        $crawler = $this->client->followRedirect();

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Test Mailbox has been saved', $crawler->html());

        /** @var Mailbox $mailbox */
        $mailbox = $this->getRepository()->findOneBy(['label' => 'Test Mailbox']);

        $this->assertNotNull($mailbox);
        $this->assertEquals('test@example.org', $mailbox->getEmail());
        $this->assertEntityFieldContains($mailbox->getAuthorizedUsers(), User::class, [$user1, $user2]);
        $this->assertEntityFieldContains($mailbox->getAuthorizedRoles(), Role::class, [$role1, $role2]);
    }

    /**
     * @return MailboxRepository
     */
    private function getRepository()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Mailbox::class)
            ->getRepository(Mailbox::class);
    }

    /**
     * @param Collection $data
     * @param string $className
     * @param array $expected
     */
    private function assertEntityFieldContains(Collection $data, string $className, array $expected)
    {
        $expectedIds = array_map(
            function ($entity) {
                return $entity->getId();
            },
            $expected
        );

        foreach ($data as $item) {
            $this->assertInstanceOf($className, $item);
            $this->assertContains($item->getId(), $expectedIds);
        }
    }
}
