<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional;

use Oro\Bundle\EntityExtendBundle\Tests\Functional\AbstractConfigControllerTest;
use Oro\Bundle\UIBundle\Route\Router;

/**
 * @dbIsolation
 */
class AttributeFamilyControllerTest extends AbstractConfigControllerTest
{
    public function testCreate()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_attribute_family_create', ['alias' => $this->getTestEntityAlias()])
        );
        $saveButton = $crawler->selectButton('Save and New');

        $form = $saveButton->form();
        $form['oro_attribute_family[code]'] = 'AttributeFamilyCode';
        $form['oro_attribute_family[labels][values][default]'] = 'AttributeFamilyCode';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form, [Router::ACTION_PARAMETER => $saveButton->attr('data-action')]);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Attribute family was successfully saved', $crawler->html());
    }
}
