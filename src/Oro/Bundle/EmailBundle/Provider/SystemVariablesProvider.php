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
        $this->addApplicationUrl($result, $addValue);
        $this->addCurrentDateAndTime($result, $addValue);

        return $result;
    }

    /**
     * @deprecated since 1.4 Avoid usage of "{{ system.appShortName }}" in email templates
     *
     * @param array $result
     * @param bool  $addValue
     */
    protected function addApplicationShortName(array &$result, $addValue)
    {
        if ($addValue) {
            $result['appShortName'] = '';
        }
    }

    /**
     * @deprecated since 1.4 Avoid usage of "{{ system.appFullName }}" in email templates
     *
     * @param array $result
     * @param bool  $addValue
     */
    protected function addApplicationFullName(array &$result, $addValue)
    {
        if ($addValue) {
            $result['appFullName'] = '';
        }
    }

    /**
     * @param array $result
     * @param bool  $addValue
     */
    protected function addApplicationUrl(array &$result, $addValue)
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
                'type'  => 'datetime',
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
        // @todo: the datetime object cannot be added due __toString of DateTime is not allowed error
        //        this code can be uncommented after validation and formatting are fixed
        //$result['currentDateTime'] = $dateTimeVal;
        $result['currentDate'] = $dateVal;
        $result['currentTime'] = $timeVal;
    }
}
