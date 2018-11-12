<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailAttachmentRepository;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

/**
 * The manager responsibles to works with the email REST API resources.
 */
class EmailApiEntityManager extends ApiEntityManager
{
    /** @var EmailQueryFactory */
    private $emailQueryFactory;

    /**
     * @param string            $class
     * @param ObjectManager     $om
     * @param EmailQueryFactory $emailQueryFactory
     */
    public function __construct(
        $class,
        ObjectManager $om,
        EmailQueryFactory $emailQueryFactory
    ) {
        $this->emailQueryFactory = $emailQueryFactory;
        parent::__construct($class, $om);
    }

    /**
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $qb = parent::getListQueryBuilder($limit, $page, $criteria, $orderBy, $joins);
        $qb->join('e.emailUsers', 'eu');
        // email API list query should return the same result as my emails page.
        $this->emailQueryFactory->applyAcl($qb);

        return $qb;
    }

    /**
     * Gets email attachment entity by identifier.
     *
     * @param integer $id
     *
     * @return EmailAttachment
     */
    public function findEmailAttachment($id)
    {
        return $this->getEmailAttachmentRepository()->find($id);
    }

    /**
     * Gets email attachment repository
     *
     * @return EmailAttachmentRepository
     */
    public function getEmailAttachmentRepository()
    {
        return $this->getObjectManager()->getRepository('Oro\Bundle\EmailBundle\Entity\EmailAttachment');
    }

    /**
     * {@inheritdoc}
     */
    protected function getSerializationConfig()
    {
        $config = [
            'fields'         => [
                'fromEmailAddress' => ['exclude' => true],
                'createdAt'        => ['property_path' => 'created'],
                'importance'       => ['data_transformer' => 'oro_email.email_importance_transformer'],
                'from'             => ['property_path' => 'fromName'],
                'recipients'       => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name' => null,
                        'type' => null
                    ]
                ],
                'emailBody'        => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'body'     => ['property_path' => 'bodyContent'],
                        'bodyType' => [
                            'data_transformer' => 'oro_email.email_body_type_transformer',
                            'property_path'    => 'bodyIsText'
                        ]
                    ]
                ],
                'emailUsers' => [
                    'exclusion_policy' => 'all',
                    'hints'            => ['HINT_FILTER_BY_CURRENT_USER'],
                    'fields'           => [
                        'id'       => null,
                        'seen'       => null,
                        'receivedAt' => null,
                        'origin'     => [
                            'exclusion_policy' => 'all',
                            'fields' =>
                                ['id' => null]
                        ],
                        'folders'     => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'id'       => null,
                                'fullName' => null,
                                'name'     => null,
                                'type'     => null
                            ]
                        ]
                    ]
                ]
            ],
            'post_serialize'  => function (array $result) {
                return $this->postSerializeEmail($result);
            }
        ];

        return $config;
    }

    /**
     * @param array $result
     *
     * @return array
     */
    protected function postSerializeEmail(array $result): array
    {
        $result['to']  = [];
        $result['cc']  = [];
        $result['bcc'] = [];
        foreach ($result['recipients'] as $recipient) {
            $result[$recipient['type']][] = $recipient['name'];
        }
        unset($result['recipients']);

        if (empty($result['emailBody'])) {
            $result['body']     = null;
            $result['bodyType'] = null;
        } else {
            $result['body']     = $result['emailBody']['body'];
            $result['bodyType'] = $result['emailBody']['bodyType'];
        }
        unset($result['emailBody']);

        if (empty($result['emailUsers'])) {
            $result['seen']       = false;
            $result['receivedAt'] = null;
            $result['folders']    = [];
        } else {
            $emailUser            = reset($result['emailUsers']);
            $result['seen']       = $emailUser['seen'];
            $result['receivedAt'] = $emailUser['receivedAt'];
            $result['folders'] = [$emailUser['folders']];
        }
        unset($result['emailUsers']);

        return $result;
    }
}
