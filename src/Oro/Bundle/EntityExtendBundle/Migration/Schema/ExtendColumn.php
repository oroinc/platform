<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Schema;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\MigrationBundle\Migration\Schema\Column;

class ExtendColumn extends Column
{
    const ORO_OPTIONS_NAME = 'oro_options';

    /**
     * @var ExtendOptionsManager
     */
    protected $extendOptionsManager;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var bool
     */
    protected $constructed = false;

    /**
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->extendOptionsManager = $args['extendOptionsManager'];
        $this->tableName            = $args['tableName'];

        parent::__construct($args);

        $this->constructed = true;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        if (isset($options[ExtendColumn::ORO_OPTIONS_NAME])) {
            $this->extendOptionsManager->setColumnOptions(
                $this->tableName,
                $this->getName(),
                $options[ExtendColumn::ORO_OPTIONS_NAME]
            );
            unset($options[ExtendColumn::ORO_OPTIONS_NAME]);
        }

        if (!empty($options)) {
            parent::setOptions($options);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setType(Type $type)
    {
        if ($this->constructed) {
            $this->setOptions(
                [
                    ExtendColumn::ORO_OPTIONS_NAME => [
                        ExtendOptionsManager::TYPE_OPTION => $type->getName()
                    ]
                ]
            );
        }

        return parent::setType($type);
    }

    /**
     * {@inheritdoc}
     */
    public function setLength($length)
    {
        if ($this->constructed) {
            $this->setOptions([ExtendColumn::ORO_OPTIONS_NAME => ['extend' => ['length' => $length]]]);
        }

        return parent::setLength($length);
    }

    /**
     * {@inheritdoc}
     */
    public function setPrecision($precision)
    {
        if ($this->constructed) {
            $this->setOptions([ExtendColumn::ORO_OPTIONS_NAME => ['extend' => ['precision' => $precision]]]);
        }

        return parent::setPrecision($precision);
    }

    /**
     * {@inheritdoc}
     */
    public function setScale($scale)
    {
        if ($this->constructed) {
            $this->setOptions([ExtendColumn::ORO_OPTIONS_NAME => ['extend' => ['scale' => $scale]]]);
        }

        return parent::setScale($scale);
    }
}
