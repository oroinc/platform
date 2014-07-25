<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

class SystemVariablesProvider implements SystemVariablesProviderInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigManager */
    protected $configManager;

    /** @var DateTimeFormatter */
    protected $dateTimeFormatter;

    /**
     * @param TranslatorInterface $translator
     * @param ConfigManager       $configManager
     * @param DateTimeFormatter   $dateTimeFormatter
     */
    public function __construct(
        TranslatorInterface $translator,
        ConfigManager $configManager,
        DateTimeFormatter $dateTimeFormatter
    ) {
        $this->translator        = $translator;
        $this->configManager     = $configManager;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableDefinitions()
    {
        return $this->getVariables(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableValues()
    {
        return $this->getVariables(true);
    }

    /**
     * @param bool $addValue FALSE for variable definitions; TRUE for variable values
     *
     * @return array
     */
    protected function getVariables($addValue)
    {
        $result = [];

        $this->addApplicationShortName($result, $addValue);
        $this->addApplicationFullName($result, $addValue);
        $this->addCurrentDateAndTime($result, $addValue);

        return $result;
    }

    /**
     * @param array $result
     * @param bool  $addValue
     */
    protected function addApplicationShortName(array &$result, $addValue)
    {
        if ($addValue) {
            $val = $this->configManager->get('oro_ui.application_name');
        } else {
            $val = [
                'type' => 'string',
                'name' => $this->translator->trans('oro.email.emailtemplate.app_short_name')
            ];
        }
        $result['appShortName'] = $val;
    }

    /**
     * @param array $result
     * @param bool  $addValue
     */
    protected function addApplicationFullName(array &$result, $addValue)
    {
        if ($addValue) {
            $val = $this->configManager->get('oro_ui.application_title');
        } else {
            $val = [
                'type' => 'string',
                'name' => $this->translator->trans('oro.email.emailtemplate.app_full_name')
            ];
        }
        $result['appFullName'] = $val;
    }

    /**
     * @param array $result
     * @param bool  $addValue
     */
    protected function addCurrentDateAndTime(array &$result, $addValue)
    {
        if ($addValue) {
            $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

            $dateTimeVal = $dateTime;
            $dateVal     = $this->dateTimeFormatter->formatDate($dateTime);
            $timeVal     = $this->dateTimeFormatter->formatTime($dateTime);
        } else {
            $dateTimeVal = [
                'type' => 'datetime',
                'name' => $this->translator->trans('oro.email.emailtemplate.current_datetime')
            ];
            $dateVal     = [
                'type' => 'string',
                'name' => $this->translator->trans('oro.email.emailtemplate.current_date')
            ];
            $timeVal     = [
                'type' => 'string',
                'name' => $this->translator->trans('oro.email.emailtemplate.current_time')
            ];
        }
        $result['currentDateTime'] = $dateTimeVal;
        $result['currentDate']     = $dateVal;
        $result['currentTime']     = $timeVal;
    }
}
