<?php

namespace Oro\Bundle\EntityExtendBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumExtension extends \Twig_Extension
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var array */
    protected $localCache = [];

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('trans_enum', [$this, 'transEnum']),
        ];
    }

    /**
     * Translates the given enum value
     *
     * @param string $enumValueId
     * @param string $enumValueEntityClassOrEnumCode
     *
     * @return string
     */
    public function transEnum($enumValueId, $enumValueEntityClassOrEnumCode)
    {
        if (strpos($enumValueEntityClassOrEnumCode, '\\') === false) {
            $enumValueEntityClassOrEnumCode = ExtendHelper::buildEnumValueClassName($enumValueEntityClassOrEnumCode);
        }

        if (!isset($this->localCache[$enumValueEntityClassOrEnumCode])) {
            $this->localCache[$enumValueEntityClassOrEnumCode] = [];
            /** @var AbstractEnumValue[] $values */
            $values = $this->doctrine->getRepository($enumValueEntityClassOrEnumCode)->findAll();
            foreach ($values as $value) {
                $this->localCache[$enumValueEntityClassOrEnumCode][$value->getId()] = $value->getName();
            }
        }

        return !empty($this->localCache[$enumValueEntityClassOrEnumCode][$enumValueId])
            ? $this->localCache[$enumValueEntityClassOrEnumCode][$enumValueId]
            : $enumValueId;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_enum';
    }
}
