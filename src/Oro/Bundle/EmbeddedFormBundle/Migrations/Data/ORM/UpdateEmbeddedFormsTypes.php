<?php

namespace Oro\Bundle\EmbeddedForm\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Query\QueryBuilder;
use Oro\Bundle\ContactUsBundle\Form\Type\ContactRequestType as ContactUsContactRequestType;
use Oro\Bundle\MagentoContactUsBundle\Form\Type\ContactRequestType as MagentoContactRequestType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * This fixtures aims to update forms' types for oro_contact_us.embedded_form and oro_magento_contact_us.embedded_form
 * embedded forms.
 */
class UpdateEmbeddedFormsTypes extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->updateFormType('oro_contact_us.embedded_form', ContactUsContactRequestType::class);
        $this->updateFormType('oro_magento_contact_us.embedded_form', MagentoContactRequestType::class);
    }

    /**
     * @param string $formAlias
     * @param string $formType
     * @return int
     */
    private function updateFormType(string $formAlias, string $formType): int
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->container->get('doctrine')->getConnection()->createQueryBuilder();

        $queryBuilder
            ->update('oro_embedded_form', 'form')
            ->set('form_type', ':type')
            ->where($queryBuilder->expr()->eq('form_type', ':alias'))
            ->setParameter('type', $formType)
            ->setParameter('alias', $formAlias);

        return $queryBuilder->execute();
    }
}
