<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Symfony\Component\Form\AbstractExtension;

class EntitySelectOrCreateInlineFormExtension extends AbstractExtension
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Oro\Bundle\FormBundle\Autocomplete\SearchRegistry
     */
    protected $searchRegistry;

    public function __construct(EntityManager $em, SearchRegistry $searchRegistry)
    {
        $this->em = $em;
        $this->searchRegistry = $searchRegistry;
    }

    protected function loadTypes()
    {
        return array(
            new OroJquerySelect2HiddenType($this->em, $this->searchRegistry),
            new Select2Type('hidden')
        );
    }
}
