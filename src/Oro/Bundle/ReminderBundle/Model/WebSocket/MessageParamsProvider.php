<?php

namespace Oro\Bundle\ReminderBundle\Model\WebSocket;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\UrlProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Realized functionality to get message parameters
 */
class MessageParamsProvider
{
    const DEFAULT_IDENTIFIER = 'default';

    /**
     * @var DateTimeFormatterInterface
     */
    protected $dateTimeFormatter;

    /**
     * @var TranslatorInterface
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

    public function __construct(
        TranslatorInterface $translator,
        DateTimeFormatterInterface $dateTimeFormatter,
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
        $now = new \DateTime();
        $expiredDate = $this->dateTimeFormatter->formatDate($reminder->getExpireAt(), \IntlDateFormatter::SHORT);
        $nowDate = $this->dateTimeFormatter->formatDate($now, \IntlDateFormatter::SHORT);

        if ($expiredDate === $nowDate) {
            $expireAt = $this->dateTimeFormatter->formatTime($reminder->getExpireAt());
        } else {
            $expireAt = $this->dateTimeFormatter->format($reminder->getExpireAt());
        }

        return array(
            'templateId'   => $this->getTemplateId($reminder),
            'expireAt'     => $expireAt,
            'subject'      => $reminder->getSubject(),
            'url'          => $this->urlProvider->getUrl($reminder),
            'id'           => $reminder->getId(),
            'uniqueId'     => md5($reminder->getRelatedEntityClassName() . $reminder->getRelatedEntityId())
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
