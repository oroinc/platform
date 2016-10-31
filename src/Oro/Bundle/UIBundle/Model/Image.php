<?php

namespace Oro\Bundle\UIBundle\Model;

class Image implements \JsonSerializable
{
    const TYPE_ICON = 'icon';
    const TYPE_FILE = 'file';
    const TYPE_FILE_PATH = 'file-path';

    /** @var string */
    protected $type;

    /** @var mixed */
    protected $data;

    /**
     * @param string $type Type of the image based on which image will be rendered
     * @param mixed  $data Data of the image rendered based on type
     */
    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
        ];
    }
}
