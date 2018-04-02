<?php

namespace Oro\Bundle\ConfigBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Symfony\Component\Translation\TranslatorInterface;

class GroupSearchProvider implements SearchProviderInterface
{
    /** @var ConfigBag */
    private $configBag;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param ConfigBag $configBag
     * @param TranslatorInterface $translator
     */
    public function __construct(ConfigBag $configBag, TranslatorInterface $translator)
    {
        $this->configBag = $configBag;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        return $this->configBag->getGroupsNode($name) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($name)
    {
        $group = $this->configBag->getGroupsNode($name);
        if ($group === false) {
            throw new ItemNotFoundException(sprintf('Group "%s" is not defined.', $name));
        }

        $searchData = [];
        if (isset($group['title'])) {
            $searchData[] = $this->translator->trans($group['title']);
        }

        return $searchData;
    }
}
