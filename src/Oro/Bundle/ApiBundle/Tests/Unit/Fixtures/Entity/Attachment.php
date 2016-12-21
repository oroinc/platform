<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="attachment_table")
 */
class Attachment
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    protected $account;

    public function __construct()
    {
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function setAccount($value)
    {
        $this->account = $value;
        return $this;
    }

    public function getTarget()
    {
        if (null !== $this->account) {
            return $this->account;
        }
        return null;
    }

    public function setTarget($target)
    {
        if (null === $target) {
            $this->resetTargets();
            return $this;
        }
        $className = ClassUtils::getClass($target);
        // This entity can be associated with only one another entity
        if ($className === 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account') {
            $this->resetTargets();
            $this->account = $target;
            return $this;
        }
        throw new \RuntimeException(sprintf('The association with "%s" entity was not configured.', $className));
    }

    public function supportTarget($targetClass)
    {
        $className = ClassUtils::getRealClass($targetClass);
        if ($className === 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account') {
            return true;
        }
        return false;
    }

    private function resetTargets()
    {
        $this->account = null;
    }
}
