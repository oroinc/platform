<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Oro\Bundle\EntityBundle\Provider\EntityClassProviderInterface;

class OrmEntityClassProvider implements EntityClassProviderInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ManagerBagInterface */
    protected $managerBag;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param ManagerBagInterface $managerBag
     */
    public function __construct(DoctrineHelper $doctrineHelper, ManagerBagInterface $managerBag)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->managerBag = $managerBag;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassNames()
    {
        $result = [];
        $managers = $this->managerBag->getManagers();
        foreach ($managers as $om) {
            $allMetadata = $this->doctrineHelper->getAllShortMetadata($om, false);
            foreach ($allMetadata as $metadata) {
                if (!$metadata->isMappedSuperclass) {
                    $result[] = $metadata->name;
                }
            }
        }

        return $result;
    }
}
