<?php

namespace Oro\Bundle\ConfigBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Symfony\Component\Translation\TranslatorInterface;

class FieldSearchProvider implements SearchProviderInterface
{
    /** @var ConfigBag */
    private $configBag;

    /** @var TranslatorInterface */
    private $translator;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigBag $configBag
     * @param TranslatorInterface $translator
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigBag $configBag, TranslatorInterface $translator, ConfigManager $configManager)
    {
        $this->configBag = $configBag;
        $this->translator = $translator;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        return $this->configBag->getFieldsRoot($name) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($name)
    {
        $field = $this->configBag->getFieldsRoot($name);
        if ($field === false) {
            throw new ItemNotFoundException(sprintf('Field "%s" is not defined.', $name));
        }

        $searchData = [];
        if (isset($field['options']['label'])) {
            $searchData[] = $this->translator->trans($field['options']['label']);
        }

        if (isset($field['options']['tooltip'])) {
            $searchData[] = $this->translator->trans($field['options']['tooltip']);
        }

        if (isset($field['search_type'])) {
            $searchData = array_merge($searchData, $this->getBySearchType($name, $field));
        }

        return $searchData;
    }

    /**
     * @param string $name
     * @param array $field
     *
     * @return array
     */
    private function getBySearchType($name, array $field)
    {
        if ($field['search_type'] === 'text') {
            return [$this->configManager->get($name)];
        }

        if ($field['search_type'] === 'choice') {
            if (!isset($field['options']['choices'])) {
                throw new \LogicException(
                    'The choices option should be defined, when search type "choice" is used.'
                );
            }

            $data = [];
            foreach ($field['options']['choices'] as $choiceLabel => $choiceValue) {
                $data[] = $this->translator->trans($choiceLabel);
            }

            return $data;
        }

        return [];
    }
}
