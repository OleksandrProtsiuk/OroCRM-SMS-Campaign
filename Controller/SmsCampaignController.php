<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Controller;

use Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign;
use Diglin\Bundle\SmsCampaignBundle\Form\Handler\SmsCampaignHandler;
use Diglin\Bundle\SmsCampaignBundle\Form\Type\SmsCampaignType;
use Diglin\Bundle\SmsCampaignBundle\Model\SmsCampaignSenderBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Serve CRUD of SmsCampaign entity.
 */
#[\Symfony\Component\Routing\Attribute\Route(path: '/campaign/sms')]
class SmsCampaignController extends AbstractController
{
    private FormFactoryInterface $formFactory;
    private RequestStack $requestStack;
    private TranslatorInterface $translator;
    private Router $router;
    private ManagerRegistry $managerRegistry;
    private ValidatorInterface $validator;
    private SmsCampaignSenderBuilder $smsCampaignSenderBuilder;

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        Router $router,
        SmsCampaignSenderBuilder $smsCampaignSenderBuilder
    ) {
        $this->formFactory = $formFactory;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->router = $router;
        $this->smsCampaignSenderBuilder = $smsCampaignSenderBuilder;
    }

    public function setManagerRegistry(ManagerRegistry $registry)
    {
        $this->managerRegistry = $registry;
    }

    public function setValidator(ValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                FormFactoryInterface::class,
                SmsCampaignSenderBuilder::class,
                RequestStack::class,
                Router::class,
                SessionInterface::class,
                TranslatorInterface::class,
                ValidatorInterface::class,
            ]
        );
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/', name: 'diglin_sms_campaign_index')]
    #[AclAncestor('diglin_sms_campaign_view')]
    #[Template]
    public function indexAction()
    {
        return [
            'entity_class' => SmsCampaign::class,
        ];
    }

    /**
     * Create SMS campaign
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/create', name: 'diglin_sms_campaign_create')]
    #[Template('@SmsCampaign/SmsCampaign/update.html.twig')]
    #[Acl(id: 'diglin_sms_campaign_create', type: 'entity', permission: 'CREATE', class: 'SmsCampaignBundle:SmsCampaign')]
    public function createAction()
    {
        return $this->update(new SmsCampaign());
    }

    /**
     * Process save SMS campaign entity
     *
     * @param SmsCampaign $entity
     *
     * @return array|Response
     */
    protected function update(SmsCampaign $entity)
    {
        $form = $this->formFactory->createNamed('diglin_sms_campaign', SmsCampaignType::class);

        $handler = new SmsCampaignHandler($this->requestStack, $form, $this->managerRegistry);

        if ($handler->process($entity)) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',
                $this->translator->trans('diglin.campaign.smscampaign.controller.saved.message')
            );

            return $this->router->redirect($entity);
        }

        $isUpdateOnly = $this->requestStack->getCurrentRequest()->get(SmsCampaignHandler::UPDATE_MARKER, false);

        // substitute submitted form with new not submitted instance to ignore validation errors
        // on form after transport field was changed
        if ($isUpdateOnly) {
            $form = $this->formFactory->createNamed('diglin_sms_campaign', SmsCampaignType::class, $form->getData());
        }

        return [
            'entity' => $entity,
            'form'   => $form->createView(),
        ];
    }

    /**
     * Edit SMS campaign
     *
     *
     * @param SmsCampaign $entity
     * @return array
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/update/{id}', name: 'diglin_sms_campaign_update', requirements: ['id' => '\d+'], defaults: ['id' => 0])]
    #[Template]
    #[Acl(id: 'diglin_sms_campaign_update', type: 'entity', permission: 'EDIT', class: 'SmsCampaignBundle:SmsCampaign')]
    public function updateAction(SmsCampaign $entity)
    {
        return $this->update($entity);
    }

    /**
     * View SMS campaign
     *
     *
     * @param SmsCampaign $entity
     * @return array
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/view/{id}', name: 'diglin_sms_campaign_view', requirements: ['id' => '\d+'])]
    #[Acl(id: 'diglin_sms_campaign_view', type: 'entity', permission: 'VIEW', class: 'SmsCampaignBundle:SmsCampaign')]
    #[Template]
    public function viewAction(SmsCampaign $entity)
    {
        $stats = $this->managerRegistry
            ->getRepository("SmsCampaignBundle:SmsCampaignStatistics")
            ->getSmsCampaignStats($entity);

        return [
            'entity'       => $entity,
            'stats'        => $stats,
            'show_stats'   => (bool)array_sum($stats),
            'send_allowed' => $this->isManualSendAllowed($entity),
        ];
    }

    /**
     * @param SmsCampaign $entity
     *
     * @return bool
     */
    protected function isManualSendAllowed(SmsCampaign $entity)
    {
        $sendAllowed = $entity->getSchedule() === SmsCampaign::SCHEDULE_MANUAL
            && !$entity->isSent()
            && $this->isGranted('diglin_sms_campaign_send');

        if ($sendAllowed) {
            $transportSettings = $entity->getTransportSettings();
            if ($transportSettings) {
                $validator = $this->validator;
                $errors = $validator->validate($transportSettings);
                $sendAllowed = count($errors) === 0;
            }
        }

        return $sendAllowed;
    }

    /**
     *
     * @param SmsCampaign $entity
     * @return RedirectResponse
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/send/{id}', name: 'diglin_sms_campaign_send', requirements: ['id' => '\d+'])]
    #[Acl(id: 'diglin_sms_campaign_send', type: 'action', label: 'oro.campaign.acl.send_emails.label', description: 'oro.campaign.acl.send_emails.description', group_name: '', category: 'marketing')]
    public function sendAction(SmsCampaign $entity)
    {
        if ($this->isManualSendAllowed($entity)) {
            $senderFactory = $this->smsCampaignSenderBuilder;
            $sender = $senderFactory->getSender($entity);
            $sender->send();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',
                $this->translator->trans('diglin.campaign.smscampaign.controller.sent')
            );
        } else {
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',
                $this->translator->trans('diglin.campaign.smscampaign.controller.send_disallowed')
            );
        }

        return $this->redirect(
            $this->generateUrl(
                'diglin_sms_campaign_view',
                ['id' => $entity->getId()]
            )
        );
    }
}
