<?php

namespace Oro\Bundle\ActionBundle\Button;

interface ButtonInterface
{
    const DEFAULT_GROUP = '';
    const DEFAULT_JS_DIALOG_WIDGET = 'oro/dialog-widget';

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return string
     */
    public function getIcon();

    /**
     * @return int
     */
    public function getOrder();

    /**
     * Returns name of template that be used to render button
     *
     * @return string
     */
    public function getTemplate();

    /**
     * Returns all data required to render template
     *
     * @param array $customData
     *
     * @return array
     */
    public function getTemplateData(array $customData = []);

    /**
     * @return ButtonContext
     */
    public function getButtonContext();

    /**
     * @return string
     */
    public function getGroup();

    /**
     * @return string|null
     */
    public function getTranslationDomain();
}
