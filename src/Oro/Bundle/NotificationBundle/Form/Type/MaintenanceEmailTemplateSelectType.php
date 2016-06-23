<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\SystemEmailTemplateSelectType;

class MaintenanceEmailTemplateSelectType extends SystemEmailTemplateSelectType
{
    /** @var  string */
    protected $defaultTemplate;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_notification_maintenance_template_list';
    }

    /**
     * @param string $templateName
     */
    public function setDefaultTemplate($templateName)
    {
        $this->defaultTemplate = $templateName;
    }

    /**
     * {@inheritdoc}
     */
    protected function getQueryBuilder()
    {
        $qb = parent::getQueryBuilder();
        if ($this->defaultTemplate) {
            $qb->orWhere('e.name = :default_template')->setParameter('default_template', $this->defaultTemplate);
        }
        
        return $qb;
    }
}
