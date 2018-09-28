<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\Email;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ReminderBundle\Model\Email\TemplateEmailNotification;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;

class TemplateEmailNotificationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManager;

    /**
     * @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configProvider;

    /**
     * @var EntityNameResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityNameResolver;

    /**
     * @var TemplateEmailNotification
     */
    private $templateEmailNotification;

    protected function setUp()
    {
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->templateEmailNotification = new TemplateEmailNotification(
            $this->objectManager,
            $this->configProvider,
            $this->entityNameResolver
        );
    }

    public function testGetRecipients()
    {
        $recipient = new User();
        /** @var Reminder $reminder */
        $reminder = $this->getEntity(Reminder::class, [
            'recipient' => $recipient,
        ]);

        $this->templateEmailNotification->setReminder($reminder);
        self::assertEquals([$recipient], $this->templateEmailNotification->getRecipients());
    }

    public function testGetRecipientsWhenNoReminder()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reminder was not set');
        $this->templateEmailNotification->getRecipients();
    }

    public function testGetTemplateCriteria()
    {
        $entityClassName = \stdClass::class;
        /** @var Reminder $reminder */
        $reminder = $this->getEntity(Reminder::class, [
            'relatedEntityClassName' => $entityClassName,
        ]);
        $config = $this->createMock(ConfigInterface::class);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClassName)
            ->willReturn($config);
        $templateName = 'some_template';
        $config->expects($this->once())
            ->method('get')
            ->with(TemplateEmailNotification::CONFIG_FIELD, true)
            ->willReturn($templateName);

        $this->templateEmailNotification->setReminder($reminder);
        self::assertEquals(
            new EmailTemplateCriteria($templateName, $entityClassName),
            $this->templateEmailNotification->getTemplateCriteria()
        );
    }

    public function testGetTemplateCriteriaWhenNoReminder()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reminder was not set');
        $this->templateEmailNotification->getTemplateCriteria();
    }
}
