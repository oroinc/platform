<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

class FieldSecurityMetadata implements \Serializable
{
    /** @var string */
    protected $fieldName;

    /** @var string */
    protected $label;

    /**
     * @param string $fieldName
     * @param string $label
     */
    public function __construct(
        $fieldName = '',
        $label = ''
    ) {
        $this->fieldName = $fieldName;
        $this->label = $label;
    }

    /**
     * Returns field name
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Returns field label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->fieldName,
                $this->label
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->fieldName,
            $this->label
            ) = unserialize($serialized);
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     *
     * @return FieldSecurityMetadata A new instance of a FieldSecurityMetadata object
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        $result = new FieldSecurityMetadata();
        $result->fieldName = $data['fieldName'];
        $result->label = $data['label'];

        return $result;
    }
    // @codingStandardsIgnoreEnd
}
