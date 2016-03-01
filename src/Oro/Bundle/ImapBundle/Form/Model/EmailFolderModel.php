<?php

namespace Oro\Bundle\ImapBundle\Form\Model;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class EmailFolderModel
{
    /**
     * @var string
     */
    protected $uidValidity;

    /**
     * @var EmailFolder
     */
    protected $emailFolder;

    /**
     * @var EmailFolderModel
     */
    protected $parentFolderModel;

    /**
     * @var EmailFolderModel[]|Collection
     */
    protected $subFolderModels;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->subFolderModels = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getUidValidity()
    {
        return $this->uidValidity;
    }

    /**
     * @param string $uid
     *
     * @return $this
     */
    public function setUidValidity($uid)
    {
        $this->uidValidity = $uid;

        return $this;
    }

    /**
     * @return EmailFolder
     */
    public function getEmailFolder()
    {
        return $this->emailFolder;
    }

    /**
     * @return bool
     */
    public function hasEmailFolder()
    {
        return $this->emailFolder !== null;
    }

    /**
     * @param EmailFolder $emailFolder
     *
     * @return $this
     */
    public function setEmailFolder($emailFolder)
    {
        $this->emailFolder = $emailFolder;

        if ($this->hasParentFolderModel()) {
            $this->emailFolder->setParentFolder($this->getParentFolderModel()->getEmailFolder());
        }

        return $this;
    }

    /**
     * @return EmailFolderModel
     */
    public function getParentFolderModel()
    {
        return $this->parentFolderModel;
    }

    /**
     * @return bool
     */
    public function hasParentFolderModel()
    {
        return $this->parentFolderModel !== null;
    }

    /**
     * @param EmailFolderModel $parentFolderModel
     *
     * @return $this;
     */
    public function setParentFolderModel($parentFolderModel)
    {
        $this->parentFolderModel = $parentFolderModel;

        if ($this->hasEmailFolder()) {
            $this->getEmailFolder()->setParentFolder($this->parentFolderModel->getEmailFolder());
        }

        return $this;
    }

    /**
     * @return ArrayCollection|EmailFolderModel[]
     */
    public function getSubFolderModels()
    {
        return $this->subFolderModels;
    }

    /**
     * @return bool
     */
    public function hasSubFolderModels()
    {
        return !$this->subFolderModels->isEmpty();
    }

    /**
     * @param EmailFolderModel[]|ArrayCollection $emailFolderModels
     *
     * @return $this
     */
    public function setSubFolderModels($emailFolderModels)
    {
        foreach ($emailFolderModels as $emailFolderModel) {
            $this->addSubFolderModel($emailFolderModel);
        }

        return $this;
    }

    /**
     * @param EmailFolderModel $emailFolderModel
     *
     * @return $this
     */
    public function addSubFolderModel(EmailFolderModel $emailFolderModel)
    {
        if (!$this->subFolderModels->contains($emailFolderModel)) {
            $this->subFolderModels->add($emailFolderModel);
            $emailFolderModel->setParentFolderModel($this);
        }

        return $this;
    }

    /**
     * @param EmailFolderModel $emailFolderModel
     *
     * @return $this
     */
    public function removeSubFolderModel(EmailFolderModel $emailFolderModel)
    {
        if ($this->subFolderModels->contains($emailFolderModel)) {
            $this->subFolderModels->removeElement($emailFolderModel);
            $emailFolderModel->setParentFolderModel(null);
        }

        return $this;
    }
}
