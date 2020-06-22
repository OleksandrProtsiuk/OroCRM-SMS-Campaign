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
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Serve CRUD of SmsCampaign entity.
 *
 * @Route("/campaign/sms")
 */
class SmsCampaignController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
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

    /**
     * @Route("/", name="diglin_sms_campaign_index")
     * @AclAncestor("diglin_sms_campaign_view")
     * @Template
     */
    public function indexAction()
    {
        return [
            'entity_class' => SmsCampaign::class,
        ];
    }

    /**
     * Create SMS campaign
     *
     * @Route("/create", name="diglin_sms_campaign_create")
     * @Template("SmsCampaignBundle:SmsCampaign:update.html.twig")
     * @Acl(
     *      id="diglin_sms_campaign_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="SmsCampaignBundle:SmsCampaign"
     * )
     */
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
        $factory = $this->get(FormFactoryInterface::class);
        $form = $factory->createNamed('diglin_sms_campaign', SmsCampaignType::class);

        $requestStack = $this->get(RequestStack::class);
        $handler = new SmsCampaignHandler($requestStack, $form, $this->getDoctrine());

        if ($handler->process($entity)) {
            $this->get(SessionInterface::class)->getFlashBag()->add(
                'success',
                $this->get(TranslatorInterface::class)->trans('diglin.campaign.smscampaign.controller.saved.message')
            );

            return $this->get(Router::class)->redirect($entity);
        }

        $isUpdateOnly = $requestStack->getCurrentRequest()->get(SmsCampaignHandler::UPDATE_MARKER, false);

        // substitute submitted form with new not submitted instance to ignore validation errors
        // on form after transport field was changed
        if ($isUpdateOnly) {
            $form = $factory->createNamed('diglin_sms_campaign', SmsCampaignType::class, $form->getData());
        }

        return [
            'entity' => $entity,
            'form'   => $form->createView(),
        ];
    }

    /**
     * Edit SMS campaign
     *
     * @Route("/update/{id}", name="diglin_sms_campaign_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template
     * @Acl(
     *      id="diglin_sms_campaign_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="SmsCampaignBundle:SmsCampaign"
     * )
     *
     * @param SmsCampaign $entity
     *
     * @return array
     */
    public function updateAction(SmsCampaign $entity)
    {
        return $this->update($entity);
    }

    /**
     * View SMS campaign
     *
     * @Route("/view/{id}", name="diglin_sms_campaign_view", requirements={"id"="\d+"})
     * @Acl(
     *      id="diglin_sms_campaign_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="SmsCampaignBundle:SmsCampaign"
     * )
     * @Template
     *
     * @param SmsCampaign $entity
     *
     * @return array
     */
    public function viewAction(SmsCampaign $entity)
    {
        $stats = $this->getDoctrine()
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
                $validator = $this->get(ValidatorInterface::class);
                $errors = $validator->validate($transportSettings);
                $sendAllowed = count($errors) === 0;
            }
        }

        return $sendAllowed;
    }

    /**
     * @Route("/send/{id}", name="diglin_sms_campaign_send", requirements={"id"="\d+"})
     * @Acl(
     *      id="diglin_sms_campaign_send",
     *      type="action",
     *      label="oro.campaign.acl.send_emails.label",
     *      description="oro.campaign.acl.send_emails.description",
     *      group_name="",
     *      category="marketing"
     * )
     *
     * @param SmsCampaign $entity
     *
     * @return RedirectResponse
     */
    public function sendAction(SmsCampaign $entity)
    {
        if ($this->isManualSendAllowed($entity)) {
            $senderFactory = $this->get(SmsCampaignSenderBuilder::class);
            $sender = $senderFactory->getSender($entity);
            $sender->send();

            $this->get(SessionInterface::class)->getFlashBag()->add(
                'success',
                $this->get(TranslatorInterface::class)->trans('diglin.campaign.smscampaign.controller.sent')
            );
        } else {
            $this->get(SessionInterface::class)->getFlashBag()->add(
                'error',
                $this->get(TranslatorInterface::class)->trans('diglin.campaign.smscampaign.controller.send_disallowed')
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
