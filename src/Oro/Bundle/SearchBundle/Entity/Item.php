<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

/**
 * Search index items that correspond to specific entity record
 *
 * @ORM\Table(
 *  name="oro_search_item",
 *  uniqueConstraints={@ORM\UniqueConstraint(name="IDX_ENTITY", columns={"entity", "record_id"})},
 *  indexes={@ORM\Index(name="IDX_ALIAS", columns={"alias"}), @ORM\Index(name="IDX_ENTITIES", columns={"entity"})}
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Item extends BaseItem
{
    /**
     * @ORM\OneToMany(targetEntity="IndexText", mappedBy="item", cascade={"all"}, orphanRemoval=true)
     */
    protected $textFields;

    /**
     * @ORM\OneToMany(targetEntity="IndexInteger", mappedBy="item", cascade={"all"}, orphanRemoval=true)
     */
    protected $integerFields;

    /**
     * @ORM\OneToMany(targetEntity="IndexDecimal", mappedBy="item", cascade={"all"}, orphanRemoval=true)
     */
    protected $decimalFields;

    /**
     * @ORM\OneToMany(targetEntity="IndexDatetime", mappedBy="item", cascade={"all"}, orphanRemoval=true)
     */
    protected $datetimeFields;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->textFields = new ArrayCollection();
        $this->integerFields = new ArrayCollection();
        $this->decimalFields = new ArrayCollection();
        $this->datetimeFields = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getIntegerFields()
    {
        return $this->integerFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getDecimalFields()
    {
        return $this->decimalFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatetimeFields()
    {
        return $this->datetimeFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextFields()
    {
        return $this->textFields;
    }

    /**
     * {@inheritdoc}
     */
    public function saveItemData($objectData)
    {
        $this->saveData($objectData, $this->textFields, new IndexText(), SearchQuery::TYPE_TEXT);
        $this->saveData($objectData, $this->integerFields, new IndexInteger(), SearchQuery::TYPE_INTEGER);
        $this->saveData($objectData, $this->datetimeFields, new IndexDatetime(), SearchQuery::TYPE_DATETIME);
        $this->saveData($objectData, $this->decimalFields, new IndexDecimal(), SearchQuery::TYPE_DECIMAL);

        return $this;
    }
}
