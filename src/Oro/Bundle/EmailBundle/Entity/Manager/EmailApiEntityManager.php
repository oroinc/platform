<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailAttachmentRepository;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class EmailApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ConfigManager */
    protected $configManager;

    /** @var RouterInterface */
    protected $router;

    /**
     * @param string          $class
     * @param ObjectManager   $om
     * @param ActivityManager $activityManager
     * @param ConfigManager   $configManager
     * @param SecurityFacade  $securityFacade
     * @param RouterInterface $router
     */
    public function __construct(
        $class,
        ObjectManager $om,
        ActivityManager $activityManager,
        ConfigManager $configManager,
        SecurityFacade $securityFacade,
        RouterInterface $router
    ) {
        parent::__construct($class, $om);
        $this->activityManager = $activityManager;
        $this->configManager   = $configManager;
        $this->securityFacade  = $securityFacade;
        $this->router          = $router;
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
     * Returns the context for the given email
     *
     * @param Email $email
     *
     * @return array
     */
    public function getEmailContext(Email $email)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('id', $email->getId()));

        $qb = $this->activityManager->getActivityTargetsQueryBuilder($this->class, $criteria);
        if (null === $qb) {
            return [];
        }

        $result = $qb->getQuery()->getResult();
        if (empty($result)) {
            return $result;
        }

        $currentUser      = $this->securityFacade->getLoggedUser();
        $currentUserClass = ClassUtils::getClass($currentUser);
        $currentUserId    = $currentUser->getId();
        $result           = array_values(
            array_filter(
                $result,
                function ($item) use ($currentUserClass, $currentUserId) {
                    return !($item['entity'] === $currentUserClass && $item['id'] == $currentUserId);
                }
            )
        );

        foreach ($result as &$item) {
            $route = $this->configManager->getEntityMetadata($item['entity'])->getRoute();

            $item['entityId']        = $email->getId();
            $item['targetId']        = $item['id'];
            $item['targetClassName'] = $this->entityClassNameHelper->getUrlSafeClassName($item['entity']);
            $item['icon']            = $this->configManager->getProvider('entity')->getConfig($item['entity'])
                ->get('icon');
            $item['link']            = $route
                ? $this->router->generate($route, ['id' => $item['id']])
                : null;

            unset($item['id'], $item['entity']);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSerializationConfig()
    {
        $config = [
            'excluded_fields' => ['fromEmailAddress'],
            'fields'          => [
                'created'    => [
                    'result_name' => 'createdAt'
                ],
                'importance' => [
                    'data_transformer' => 'oro_email.email_importance_transformer'
                ],
                'fromName'   => [
                    'result_name' => 'from'
                ],
                'recipients' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name' => null,
                        'type' => null
                    ]
                ],
                'emailBody'  => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'bodyContent' => [
                            'result_name' => 'body'
                        ],
                        'bodyIsText'  => [
                            'data_transformer' => 'oro_email.email_body_type_transformer',
                            'result_name'      => 'bodyType'
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
            'post_serialize'  => function (array &$result) {
                $this->postSerializeEmail($result);
            }
        ];

        return $config;
    }

    /**
     * @param array $result
     */
    protected function postSerializeEmail(array &$result)
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
    }
}
