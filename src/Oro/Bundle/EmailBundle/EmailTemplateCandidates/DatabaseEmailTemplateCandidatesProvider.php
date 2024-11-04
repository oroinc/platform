<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\EmailTemplateCandidates;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Provides an email template name that includes the passed context parameters.
 *
 * Example of the resulting template name:
 * @db:entityName=Acme\Bundle\Entity\SampleEntity/sample_template_name
 * @db:entityName=Acme\Bundle\Entity\SampleEntity&localization=42/sample_template_name
 */
class DatabaseEmailTemplateCandidatesProvider implements EmailTemplateCandidatesProviderInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function getCandidatesNames(EmailTemplateCriteria $emailTemplateCriteria, array $templateContext = []): array
    {
        if (str_starts_with($emailTemplateCriteria->getName(), '@')) {
            return [];
        }

        foreach ($templateContext as $key => $value) {
            if (is_object($value) && $this->doctrineHelper->isManageableEntity($value)) {
                $id = $this->doctrineHelper->getSingleEntityIdentifier($value);
                $templateContext[$key] = $id;
            }
        }

        $entityNameParam = [];
        if ($emailTemplateCriteria->getEntityName()) {
            $entityNameParam = ['entityName' => $emailTemplateCriteria->getEntityName()];
        }

        return [
            '@db:' . http_build_query($entityNameParam + $templateContext) . '/' . $emailTemplateCriteria->getName(),
        ];
    }
}
