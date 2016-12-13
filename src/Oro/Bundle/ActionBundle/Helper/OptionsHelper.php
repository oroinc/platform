<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;

class OptionsHelper
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var Router */
    protected $router;

    /**
     * @param Router $router
     * @param TranslatorInterface $translator
     */
    public function __construct(Router $router, TranslatorInterface $translator)
    {
        $this->router = $router;
        $this->translator = $translator;
    }

    /**
     * @param ButtonInterface $button
     * @param ActionData $actionData
     *
     * @return array
     */
    public function getFrontendOptions(ButtonInterface $button)
    {
        if (null === $actionData) {
            $actionData = $this->contextHelper->getActionData($actionContext);
        }

        return [
            'options' => $this->createOptions($button),
            'data' => $this->createData($button)
        ];
    }

    /**
     * @param array $options
     * @param array $source
     * @param string $sourceKey
     */
    protected function addOption(array &$options, array $source, $sourceKey)
    {
        $optionsKey = str_replace('_', '-', $sourceKey);

        if (!empty($source[$sourceKey])) {
            $options[$optionsKey] = $source[$sourceKey];
        }
    }

    /**
     * @param ButtonInterface $button
     * @return array
     */
    protected function createOptions(ButtonInterface $button)
    {
        $data = $button->getTemplateData();
        $executionUrl = $this->router->generate($data['executionRoute'], $data['routeParams']);
        $dialogUrl = $this->router->generate($data['dialogRoute'], $data['routeParams']);

        $frontendOptions = $data['frontendOptions'];

        $options = [
            'hasDialog' => $data['hasForm'],
            'showDialog' => !empty($data['showDialog']),
            'dialogOptions' => [
                'title' => $this->getTitle(
                    $button,
                    $frontendOptions
                ),
                'dialogOptions' => !empty($frontendOptions['options']) ? $frontendOptions['options'] : []
            ],
            'executionUrl' => $executionUrl,
            'dialogUrl' => $dialogUrl,
            'url' => $data['hasForm'] ? $dialogUrl : $executionUrl,
        ];

        $this->addOption($options, $frontendOptions, 'confirmation');

        return $options;
    }

    /**
     * @param ButtonInterface $button
     * @param array $frontendOptions
     * @return string
     */
    protected function getTitle(ButtonInterface $button, array $frontendOptions)
    {
        $title = isset($frontendOptions['title']) ? $frontendOptions['title'] : $button->getLabel();
        $titleParams = isset($frontendOptions['title_parameters']) ? $frontendOptions['title_parameters'] : [];

        return $this->translator->trans($title, $titleParams, $button->getTranslationDomain());
    }

    /**
     * @param ButtonInterface $button
     * @return array
     */
    protected function createData(ButtonInterface $button)
    {
        $data = [];
        $templateData = $button->getTemplateData();
        $buttonOptions = isset($templateData['buttonOptions']) ? $templateData['buttonOptions'] : [];
        $this->addOption($data, $buttonOptions, 'page_component_module');
        $this->addOption($data, $buttonOptions, 'page_component_options');

        if (!empty($buttonOptions['data'])) {
            $data = array_merge($data, $buttonOptions['data']);
        }

        return $data;
    }
}
