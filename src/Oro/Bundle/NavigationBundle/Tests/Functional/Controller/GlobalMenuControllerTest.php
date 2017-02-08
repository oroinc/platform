<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Controller;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\LoadMenuUpdateData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class GlobalMenuControllerTest extends WebTestCase
{
    const MENU_NAME = 'application_menu';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            'Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\LoadMenuUpdateData'
        ]);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_navigation_global_menu_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testView()
    {
        $url = $this->getUrl('oro_navigation_global_menu_view', ['menuName' => self::MENU_NAME]);
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
        $url = $this->getUrl('oro_navigation_global_menu_create', ['menuName' => self::MENU_NAME]);
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
        $url = $this->getUrl('oro_navigation_global_menu_create', [
            'menuName' => self::MENU_NAME,
            'parentKey' => LoadMenuUpdateData::MENU_UPDATE_1
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
        $reference = $this->getReference(LoadMenuUpdateData::MENU_UPDATE_1);

        $url = $this->getUrl('oro_navigation_global_menu_update', [
            'menuName' => self::MENU_NAME,
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
        $url = $this->getUrl('oro_navigation_global_menu_update', [
            'menuName' => self::MENU_NAME,
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

    public function testMove()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_navigation_global_menu_move',
                ['menuName' => self::MENU_NAME]
            ),
            [
                'selected' => [
                    $this->getReference(LoadMenuUpdateData::MENU_UPDATE_1_1)->getKey()
                ],
                '_widgetContainer' => 'dialog',
            ],
            [],
            $this->generateWsseAuthHeader()
        );

        $form = $crawler->selectButton('Save')->form();
        $values = $form->getValues();

        $this->assertEquals(
            [$this->getReference(LoadMenuUpdateData::MENU_UPDATE_1_1)->getKey()],
            $values['tree_move[source]']
        );

        $form['tree_move[source]'] = [$this->getReference(LoadMenuUpdateData::MENU_UPDATE_2_1_1)->getKey()];
        $form['tree_move[target]'] = $this->getReference(LoadMenuUpdateData::MENU_UPDATE_1)->getKey();

        $this->client->followRedirects(true);

        /** TODO Change after BAP-1813 */
        $form->getFormNode()->setAttribute(
            'action',
            $form->getFormNode()->getAttribute('action') . '?_widgetContainer=dialog'
        );

        $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var MenuUpdateRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroNavigationBundle:MenuUpdate')
            ->getRepository('OroNavigationBundle:MenuUpdate');
        $menuUpdate = $repository->findOneBy(['key' => LoadMenuUpdateData::MENU_UPDATE_2_1_1]);
        $this->assertEquals($menuUpdate->getParentKey(), LoadMenuUpdateData::MENU_UPDATE_1);
    }
}
