<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\NotificationBundle\Provider\ContactInformationEmailsProvider;
use Symfony\Component\Translation\TranslatorInterface;

class ContactInformationEmailsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContactInformationEmailsProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $provider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new ContactInformationEmailsProvider(
            $this->configManager,
            $this->translator
        );
    }

    protected function tearDown()
    {
        unset($this->provider);
        unset($this->configManager);
        unset($this->translator);
    }

    public function testGetRecipients()
    {
        $entity = new \stdClass();
        $entity->name = get_class($entity);
        $entity->fieldNames = ['id', 'email'];

        $configField = new Config(new FieldConfigId('entity', \stdClass::class, 'id', 'int'));
        $configField2 = new Config(new FieldConfigId('entity', \stdClass::class, 'name', 'string'));
        $configField3 = new Config(new FieldConfigId('entity', \stdClass::class, 'email', 'string'));
        $configField4 = new Config(new FieldConfigId('entity', \stdClass::class, 'email2', 'string'));

        $configField->set('state', 'Active');
        $configField2->set('state', 'Active');
        $configField3->set('state', 'Active');
        $configField3->set('label', 'Translated label');
        $configField3->set('contact_information', 'email');
        $configField4->set('state', 'Deleted');
        $configField4->set('label', 'Translated label2');
        $configField4->set('contact_information', 'email');

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('entity', $entity)
            ->willReturn([$configField, $configField2, $configField3, $configField4]);

        $this->configManager->expects($this->exactly(4))
            ->method('hasConfig')
            ->willReturn(true);

        $this->configManager->expects($this->exactly(4))
            ->method('getFieldConfig')
            ->willReturnOnConsecutiveCalls($configField, $configField2, $configField3, $configField4);

        $recipients = $this->provider->getRecipients($entity);

        $this->assertEquals(['Translated label' => 'email'], $recipients);
    }
}
