<?php

namespace Oro\Bundle\FormBundle\Form\DataMapper;

use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;

class UnidirectionalPropertyMapper extends PropertyPathMapper
{
    const TO_FORM = 'to_form';
    const TO_DATA = 'to_data';

    /** @var string */
    protected $directionAllowed;

    /**
     * @param string $directionAllowed
     */
    public function __construct($directionAllowed = self::TO_DATA)
    {
        $this->directionAllowed = $directionAllowed;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($data, $forms)
    {
        if (self::TO_FORM === $this->directionAllowed) {
            parent::mapDataToForms($data, $forms);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData($forms, &$data)
    {
        if (self::TO_DATA === $this->directionAllowed) {
            parent::mapFormsToData($forms, $data);
        }
    }
}
