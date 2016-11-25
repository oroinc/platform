<?php

namespace Oro\Bundle\ActionBundle\Model;

interface ButtonInterface
{
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
}
