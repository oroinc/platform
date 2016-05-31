<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;

class LocalizationParentSelectType extends AbstractType
{
    const NAME = 'oro_localization_parent_select';

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => $this->entityClass,
            'required' => false,
            'localization' => null,
        ])
            ->setNormalizer(
                'choices',
                function (Options $options, $value) {
                    if (null !== $value) {
                        return $value;
                    }

                    return $this->getAvailableParents($options['localization']);
                }
            );
    }

    /**
     * Returns list of available parents for localization instance
     *
     * @param Localization $localization
     *
     * @return array
     */
    protected function getAvailableParents(Localization $localization = null)
    {
        $localizations = $this->doctrineHelper->getEntityRepositoryForClass($this->entityClass)->findAll();

        if (!($localization instanceof Localization) || (!$localization->getId())) {
            return $localizations;
        }

        $collection = new ArrayCollection();

        /** @var Localization $localizationItem */
        foreach ($localizations as $localizationItem) {
            if (!$collection->contains($localizationItem)) {
                if ($localizationItem->getId() !== $localization->getId()) {
                    $collection->add($localizationItem);
                }
            }
        }

        $collection = $this->removeChildLocalizationsRecursive($localization, $collection);

        return $collection->toArray();
    }

    /**
     * Removes all child localization at any level of hierarchy
     *
     * @param Localization $localization
     * @param ArrayCollection $collection
     * @return ArrayCollection
     */
    private function removeChildLocalizationsRecursive(Localization $localization, ArrayCollection $collection)
    {
        $childLocalizations = $localization->getChildLocalizations();
        if (count($childLocalizations)) {
            foreach ($childLocalizations as $childLocalization) {
                if ($childLocalization->getChildLocalizations()) {
                    $collection = $this->removeChildLocalizationsRecursive($childLocalization, $collection);
                }
                $collection->removeElement($childLocalization);
            }
        }

        return $collection;
    }
}
