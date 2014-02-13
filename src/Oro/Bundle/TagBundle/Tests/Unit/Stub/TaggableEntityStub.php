<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\TagBundle\Entity\Taggable;

class TaggableEntityStub implements Taggable
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var mixed
     */
    protected $tags;

    public function __construct($id = null)
    {
        $this->id = $id;
        $this->tags = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    public function getTaggableId()
    {
        return 'stub' . $this->id;
    }
}
