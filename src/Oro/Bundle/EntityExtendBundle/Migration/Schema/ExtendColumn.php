<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Schema;

use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Schema\Column;

class ExtendColumn extends Column
{
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
        if (isset($options[OroOptions::KEY])) {
            $oroOptions = $options[OroOptions::KEY];
            if ($oroOptions instanceof OroOptions) {
                $oroOptions = $oroOptions->toArray();
            }
            $this->extendOptionsManager->setColumnOptions($this->tableName, $this->getName(), $oroOptions);
            unset($options[OroOptions::KEY]);
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
                    OroOptions::KEY => [
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
            $this->setOptions([OroOptions::KEY => ['extend' => ['length' => $length]]]);
        }

        return parent::setLength($length);
    }

    /**
     * {@inheritdoc}
     */
    public function setPrecision($precision)
    {
        if ($this->constructed) {
            $this->setOptions([OroOptions::KEY => ['extend' => ['precision' => $precision]]]);
        }

        return parent::setPrecision($precision);
    }

    /**
     * {@inheritdoc}
     */
    public function setScale($scale)
    {
        if ($this->constructed) {
            $this->setOptions([OroOptions::KEY => ['extend' => ['scale' => $scale]]]);
        }

        return parent::setScale($scale);
    }
}
