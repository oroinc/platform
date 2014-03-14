<?php

namespace Oro\Bundle\ReminderBundle\Model\WebSocket;

use Symfony\Component\Translation\Translator;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\UrlProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class MessageParamsProvider
{
    const DEFAULT_IDENTIFIER = 'default';

    /**
     * @var DateTimeFormatter
     */
    protected $dateTimeFormatter;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var UrlProvider
     */
    protected $urlProvider;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @param Translator        $translator
     * @param DateTimeFormatter $dateTimeFormatter
     * @param UrlProvider       $urlProvider
     * @param ConfigProvider    $provider
     */
    public function __construct(
        Translator $translator,
        DateTimeFormatter $dateTimeFormatter,
        UrlProvider $urlProvider,
        ConfigProvider $provider
    ) {
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->translator        = $translator;
        $this->urlProvider       = $urlProvider;
        $this->configProvider    = $provider;
    }

    /**
     * @param Reminder $reminder
     * @return array
     */
    public function getMessageParams(Reminder $reminder)
    {
        return array(
            'templateId'   => $this->getTemplateId($reminder),
            'expireAt'     => $this->dateTimeFormatter->format($reminder->getExpireAt()),
            'subject'      => $reminder->getSubject(),
            'url'          => $this->urlProvider->getUrl($reminder),
            'id'           => $reminder->getId(),
            'uniqueId'     => md5($reminder->getRelatedEntityClassName().$reminder->getRelatedEntityId())
        );
    }

    /**
     * @param array $reminders
     * @return array
     */
    public function getMessageParamsForReminders(array $reminders)
    {
        $remindersList = array();

        foreach ($reminders as $reminder) {
            $remindersList[] = $this->getMessageParams($reminder);
        }

        return $remindersList;
    }

    /**
     * @param Reminder $reminder
     * @return string
     */
    protected function getTemplateId(Reminder $reminder)
    {
        $className  = $reminder->getRelatedEntityClassName();
        $identifier = $this->configProvider
            ->getConfig($className)
            ->get('reminder_flash_template_identifier');

        return $identifier ?: self::DEFAULT_IDENTIFIER;
    }
}
