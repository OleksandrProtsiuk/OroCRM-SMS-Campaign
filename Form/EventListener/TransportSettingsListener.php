<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Form\EventListener;

use Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign;
use Diglin\Bundle\SmsCampaignBundle\Provider\SmsTransportProvider;
use Diglin\Bundle\SmsCampaignBundle\Transport\SmsTransportInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class TransportSettingsListener implements EventSubscriberInterface
{
    /**
     * @var SmsTransportProvider
     */
    protected $smsTransportProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param SmsTransportProvider $smsTransportProvider
     * @param DoctrineHelper       $doctrineHelper
     */
    public function __construct(SmsTransportProvider $smsTransportProvider, DoctrineHelper $doctrineHelper)
    {
        $this->smsTransportProvider = $smsTransportProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA  => 'preSet',
            FormEvents::POST_SET_DATA => 'postSet',
            FormEvents::PRE_SUBMIT    => 'preSubmit',
        ];
    }

    /**
     * Add Transport Settings form if any for existing entities.
     */
    public function preSet(FormEvent $event)
    {
        /** @var SmsCampaign $data */
        $data = $event->getData();
        if ($data === null) {
            return;
        }

        $selectedTransport = $this->getSelectedTransport($data->getTransport());
        if ($selectedTransport) {
            $this->addTransportSettingsForm($selectedTransport, $event->getForm());
            $data->setTransport($selectedTransport->getName());
        }
        $event->setData($data);
    }

    protected function getSelectedTransport(?string $selectedTransportName): ?SmsTransportInterface
    {
        if ($selectedTransportName) {
            $selectedTransport = $this->smsTransportProvider->getTransportByName($selectedTransportName);
        } else {
            $transportChoices = $this->smsTransportProvider->getTransports();
            $selectedTransport = reset($transportChoices);
        }

        return $selectedTransport;
    }

    protected function addTransportSettingsForm(SmsTransportInterface $selectedTransport, FormInterface $form)
    {
        if ($selectedTransport) {
            $transportSettingsFormType = $selectedTransport->getSettingsFormType();

            if ($transportSettingsFormType) {
                $form->add('transportSettings', $transportSettingsFormType, ['required' => true]);
            } else {
                if ($form->has('transportSettings')) {
                    $form->remove('transportSettings');
                }
            }
        }
    }

    /**
     * Set correct transport setting value.
     */
    public function postSet(FormEvent $event)
    {
        /** @var SmsCampaign $data */
        $data = $event->getData();

        if ($data === null) {
            return;
        }

        $form = $event->getForm();
        $form->get('transport')->setData($data->getTransport());
    }

    /**
     * Change transport settings subform to form matching transport passed in request.
     * Pass top level data to transportSettings.
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $formData = $form->getData();

        $transportName = isset($data['transport']) ? $data['transport'] : '';

        $selectedTransport = $this->getSelectedTransport($transportName);
        if ($selectedTransport->getName() != $formData->getTransport()) {
            $newSettings = $this->doctrineHelper
                ->createEntityInstance($selectedTransport->getSettingsEntityFQCN());
            $formData->setTransportSettings($newSettings);
        }

        if ($selectedTransport) {
            $this->addTransportSettingsForm($selectedTransport, $form);
            $formData->setTransport($selectedTransport->getName());
            $form->get('transport')->setData($selectedTransport->getName());
        }

        if ($form->has('transportSettings')) {
            $parentData = $data;
            unset($parentData['transportSettings']);
            $data['transportSettings']['parentData'] = $parentData;
        }

        $event->setData($data);
    }
}
