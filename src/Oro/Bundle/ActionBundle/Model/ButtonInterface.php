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
     * @return array
     */
    public function getTemplateData();

    /**
     * @return ButtonContext
     */
    public function getButtonContext();
}
