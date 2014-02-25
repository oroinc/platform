<?php

namespace Oro\Bundle\FormBundle\Guesser;

class FormBuildData
{
    /**
     * @var string
     */
    protected $formType;

    /**
     * @var array
     */
    protected $formOptions;

    /**
     * @param string $formType
     * @param array $formOptions
     */
    public function __construct($formType, array $formOptions = array())
    {
        $this->formType = $formType;
        $this->formOptions = $formOptions;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * @return array
     */
    public function getFormOptions()
    {
        return $this->formOptions;
    }
}
