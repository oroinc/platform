<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Controller;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @group segfault
 *
 * @dbIsolation
 */
class UserMenuControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            'Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData'
        ]);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_navigation_user_menu_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testView()
    {
        $url = $this->getUrl('oro_navigation_user_menu_view', ['menuName' => 'application_menu']);
        $crawler = $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(
            'Select existing menu item or create new.',
            $crawler->filter('.content .text-center')->html()
        );
    }

    public function testCreate()
    {
        $url = $this->getUrl('oro_navigation_user_menu_create', ['menuName' => 'application_menu']);
        $crawler = $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form();
        $form['oro_navigation_menu_update[titles][values][default]'] = 'menu_update.new.title.default';
        $form['oro_navigation_menu_update[descriptions][values][default]'] = 'menu_update.new.description.default';
        $form['oro_navigation_menu_update[uri]'] = '#menu_update.new';

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Menu item saved successfully.', $crawler->html());
    }

    public function testCreateChild()
    {
        $url = $this->getUrl('oro_navigation_user_menu_create', [
            'menuName' => 'application_menu',
            'parentKey' => 'menu_update.2'
        ]);
        $crawler = $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form();
        $form['oro_navigation_menu_update[titles][values][default]'] = 'menu_update.child.title.default';
        $form['oro_navigation_menu_update[descriptions][values][default]'] = 'menu_update.child.description.default';
        $form['oro_navigation_menu_update[uri]'] = '#menu_update.child';

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Menu item saved successfully.', $crawler->html());
    }

    public function testUpdateCustom()
    {
        /** @var MenuUpdate $reference */
        $reference = $this->getReference('menu_update.2');

        $url = $this->getUrl('oro_navigation_user_menu_update', [
            'menuName' => 'application_menu',
            'key' => $reference->getKey()
        ]);
        $crawler = $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form();
        $form['oro_navigation_menu_update[titles][values][default]'] = 'menu_update.changed.title.default';
        $form['oro_navigation_menu_update[descriptions][values][default]'] = 'menu_update.changed.description.default';
        $form['oro_navigation_menu_update[uri]'] = '#menu_update.changed';

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('Menu item saved successfully.', $html);
        $this->assertContains('menu_update.changed.title.default', $html);
    }

    public function testUpdateNotCustom()
    {
        $url = $this->getUrl('oro_navigation_user_menu_update', [
            'menuName' => 'application_menu',
            'key' => 'menu_list_default'
        ]);
        $crawler = $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(
            $this->getContainer()->get('translator')->trans('oro.navigation.menu.menu_list_default.label'),
            $crawler->html()
        );

        $form = $crawler->selectButton('Save')->form();

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('Menu item saved successfully.', $html);
        $this->assertContains('menu_update.changed.title.default', $html);
    }
}
