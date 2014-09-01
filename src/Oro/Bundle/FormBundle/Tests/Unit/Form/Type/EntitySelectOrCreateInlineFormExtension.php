<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Symfony\Component\Form\AbstractExtension;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;

class EntitySelectOrCreateInlineFormExtension extends AbstractExtension
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var SearchRegistry
     */
    protected $searchRegistry;

    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @param EntityManager           $em
     * @param SearchRegistry          $searchRegistry
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(
        EntityManager $em,
        SearchRegistry $searchRegistry,
        ConfigProviderInterface $configProvider
    ) {
        $this->em             = $em;
        $this->searchRegistry = $searchRegistry;
        $this->configProvider = $configProvider;
    }

    /**
     * @return array|\Symfony\Component\Form\FormTypeInterface[]
     */
    protected function loadTypes()
    {
        return [
            new OroJquerySelect2HiddenType($this->em, $this->searchRegistry, $this->configProvider),
            new Select2Type('hidden')
        ];
    }
}
