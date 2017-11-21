<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\NotificationBundle\Provider\ContactInformationEmailsProvider;

class ContactInformationEmailsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContactInformationEmailsProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $translator;

    protected function setUp()
    {
        $this->registry = $this->createMock(Registry::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new ContactInformationEmailsProvider(
            $this->registry,
            $this->configProvider,
            $this->translator
        );
    }

    protected function tearDown()
    {
        unset($this->provider);
        unset($this->registry);
        unset($this->configProvider);
        unset($this->translator);
    }

    public function testGetRecipients()
    {
        $entity = new \stdClass();
        $entity->name = get_class($entity);
        $entity->fieldNames = ['id', 'email'];

        $entityManager = $this->createMock(EntityManager::class);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($entity)
            ->willReturn($entityManager);

        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($entity);

        $config = new Config(new EntityConfigId('entity', \stdClass::class));
        $config->set('label', 'email');
        $config->set('contact_information', 'email');

        $this->configProvider->expects($this->exactly(2))
            ->method('hasConfig')
            ->withConsecutive([\stdClass::class, 'id'], [\stdClass::class, 'email'])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(\stdClass::class, 'email')
            ->willReturn($config);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('email')
            ->willReturn('Translated label');

        $recipients = $this->provider->getRecipients($entity);

        $this->assertEquals(['email' => 'Translated label'], $recipients);
    }
}
