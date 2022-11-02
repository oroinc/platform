<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;

/**
 * Replaces spaceless filter with apply in all the loaded email templates to support Twig 3
 */
class Twig3EmailTemplatesMigration extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $qb = $manager->getRepository(EmailTemplate::class)
            ->createQueryBuilder('emailTemplate');
        $qb->where($qb->expr()->like('emailTemplate.content', ':content'))
            ->setParameter('content', '%spaceless%');

        $emailTemplates = $qb->getQuery()->getResult();
        /** @var EmailTemplate $emailTemplate */
        foreach ($emailTemplates as $emailTemplate) {
            $content = $emailTemplate->getContent();
            $content = str_replace(
                array('{% spaceless %}', '{% endspaceless %}'),
                array('{% apply spaceless %}', '{% endapply %}'),
                $content
            );
            $emailTemplate->setContent($content);
            $manager->persist($emailTemplate);
        }

        $manager->flush();
        $manager->clear();
    }
}
