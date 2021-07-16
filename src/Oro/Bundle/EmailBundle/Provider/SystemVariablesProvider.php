<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Twig\Sandbox\SystemVariablesProviderInterface;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides the following system variables for email templates:
 * * appURL
 * * currentDateTime
 * * currentDate
 * * currentTime
 */
class SystemVariablesProvider implements SystemVariablesProviderInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var ConfigManager */
    private $configManager;

    /** @var DateTimeFormatterInterface */
    private $dateTimeFormatter;

    public function __construct(
        TranslatorInterface $translator,
        ConfigManager $configManager,
        DateTimeFormatterInterface $dateTimeFormatter
    ) {
        $this->translator = $translator;
        $this->configManager = $configManager;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableDefinitions(): array
    {
        return $this->getVariables(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableValues(): array
    {
        return $this->getVariables(true);
    }

    /**
     * @param bool $addValue FALSE for variable definitions; TRUE for variable values
     *
     * @return array
     */
    private function getVariables(bool $addValue): array
    {
        $result = [];

        $this->addApplicationUrl($result, $addValue);
        $this->addCurrentDateAndTime($result, $addValue);

        return $result;
    }

    private function addApplicationUrl(array &$result, bool $addValue): void
    {
        if ($addValue) {
            $val = $this->configManager->get('oro_ui.application_url');
        } else {
            $val = [
                'type'  => 'string',
                'label' => $this->translator->trans('oro.email.emailtemplate.app_url')
            ];
        }
        $result['appURL'] = $val;
    }

    private function addCurrentDateAndTime(array &$result, bool $addValue): void
    {
        if ($addValue) {
            $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

            $dateTimeVal = $this->dateTimeFormatter->format($dateTime);
            $dateVal     = $this->dateTimeFormatter->formatDate($dateTime);
            $timeVal     = $this->dateTimeFormatter->formatTime($dateTime);
        } else {
            $dateTimeVal = [
                'type'  => 'string',
                'label' => $this->translator->trans('oro.email.emailtemplate.current_datetime')
            ];
            $dateVal     = [
                'type'  => 'string',
                'label' => $this->translator->trans('oro.email.emailtemplate.current_date')
            ];
            $timeVal     = [
                'type'  => 'string',
                'label' => $this->translator->trans('oro.email.emailtemplate.current_time')
            ];
        }

        $result['currentDateTime'] = $dateTimeVal;
        $result['currentDate'] = $dateVal;
        $result['currentTime'] = $timeVal;
    }
}
