<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\OperationButton;
use Oro\Bundle\ActionBundle\Operation\Execution\FormProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Manages options for "action buttons"
 */
class OptionsHelper
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var UrlGeneratorInterface */
    protected $urlGenerator;

    /** @var FormProvider */
    protected $formProvider;

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        FormProvider $formProvider,
        HtmlTagHelper $htmlTagHelper
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->formProvider = $formProvider;
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * @param ButtonInterface $button
     *
     * @return array
     */
    public function getFrontendOptions(ButtonInterface $button)
    {
        return [
            'options' => $this->createOptions($button),
            'data'    => $this->createData($button)
        ];
    }

    /**
     * @param array  $options
     * @param array  $source
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
     *
     * @return array
     */
    protected function createOptions(ButtonInterface $button)
    {
        $data = $this->normalizeTemplateData($button->getTemplateData());
        $executionUrl = $this->urlGenerator->generate($data['executionRoute'], $data['routeParams']);

        $frontendOptions = $data['frontendOptions'];

        $options = [
            'hasDialog'      => $data['hasForm'],
            'showDialog'     => !empty($data['showDialog']),
            'executionUrl'   => $executionUrl,
            'url'            => $executionUrl,
            'jsDialogWidget' => $data['jsDialogWidget'],
        ];
        if (isset($data['requestMethod'])) {
            $options['requestMethod'] = $data['requestMethod'];
        }
        if ($button instanceof OperationButton) {
            $options['executionTokenData'] =
                $this->formProvider->createTokenData($button->getOperation(), $button->getData());
        }
        if ($data['hasForm']) {
            $dialogUrl = $this->urlGenerator->generate($data['dialogRoute'], $data['routeParams']);

            $options = array_merge(
                $options,
                [
                    'dialogOptions' => [
                        'title'         => $this->getTitle($button, $frontendOptions),
                        'dialogOptions' => !empty($frontendOptions['options']) ? $frontendOptions['options'] : []
                    ],
                    'dialogUrl'     => $dialogUrl,
                    'url'           => $dialogUrl,
                ]
            );
        } elseif (null !== ($message = $this->getMessage($button, $frontendOptions))) {
            $this->addOption($options, $frontendOptions, 'message');
            $options['message']['content'] = $message;
        }

        $this->addOption($options, $frontendOptions, 'confirmation');

        return $options;
    }

    /**
     * @param ButtonInterface $button
     * @param array           $frontendOptions
     *
     * @return string
     */
    protected function getTitle(ButtonInterface $button, array $frontendOptions)
    {
        return $this->translator->trans(
            (string) ($frontendOptions['title'] ?? $button->getLabel()),
            $frontendOptions['title_parameters'] ?? [],
            $button->getTranslationDomain()
        );
    }

    /**
     * @param ButtonInterface $button
     * @param array           $frontendOptions
     *
     * @return string|null
     */
    protected function getMessage(ButtonInterface $button, array $frontendOptions)
    {
        $content = null;
        if (isset($frontendOptions['message']['content'])) {
            $message = $frontendOptions['message'];
            $parameters = $message['message_parameters'] ?? [];

            $content = $this->translator->trans($message['content'], $parameters, $button->getTranslationDomain());
            $content = $content !== $message['content'] ? $content : null;
        }

        return $content;
    }

    /**
     * @param ButtonInterface $button
     *
     * @return array
     */
    protected function createData(ButtonInterface $button)
    {
        $data = [];
        $templateData = $this->normalizeTemplateData($button->getTemplateData());
        $this->addOption($data, $templateData['buttonOptions'], 'page_component_module');
        $this->addOption($data, $templateData['buttonOptions'], 'page_component_options');

        if (!empty($templateData['buttonOptions']['data'])) {
            $data = array_merge($data, $templateData['buttonOptions']['data']);
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function normalizeTemplateData(array $data)
    {
        $normalizedData = array_merge(
            [
                'hasForm'         => null,
                'showDialog'      => null,
                'executionRoute'  => '',
                'dialogRoute'     => '',
                'routeParams'     => [],
                'frontendOptions' => [],
                'buttonOptions'   => [],
                'jsDialogWidget'  => ButtonInterface::DEFAULT_JS_DIALOG_WIDGET,
            ],
            $data
        );

        $normalizedData['executionRoute'] = (string) $normalizedData['executionRoute'];
        $normalizedData['dialogRoute'] = (string) $normalizedData['dialogRoute'];

        return $normalizedData;
    }
}
