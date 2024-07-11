<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Form\Type;

use Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign;
use Diglin\Bundle\SmsCampaignBundle\Provider\SmsTransportProvider;
use Oro\Bundle\CampaignBundle\Form\Type\CampaignSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\MarketingListBundle\Form\Type\MarketingListSelectType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SmsCampaignType extends AbstractType
{
    /**
     * @var EventSubscriberInterface[]
     */
    protected $subscribers = [];

    /**
     * @var SmsTransportProvider
     */
    protected $smsTransportProvider;

    /**
     * @param SmsTransportProvider $smsTransportProvider
     */
    public function __construct(SmsTransportProvider $smsTransportProvider)
    {
        $this->smsTransportProvider = $smsTransportProvider;
    }

    /**
     * @param EventSubscriberInterface $subscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->subscribers[] = $subscriber;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->subscribers as $subscriber) {
            $builder->addEventSubscriber($subscriber);
        }

        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'oro.campaign.emailcampaign.name.label',
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'schedule',
                ChoiceType::class,
                [
                    'choices' => [
                        'oro.campaign.emailcampaign.schedule.manual'   => SmsCampaign::SCHEDULE_MANUAL,
                        'oro.campaign.emailcampaign.schedule.deferred' => SmsCampaign::SCHEDULE_DEFERRED,
                    ],
                    'label'   => 'oro.campaign.emailcampaign.schedule.label',
                ]
            )
            ->add(
                'scheduledFor',
                OroDateTimeType::class,
                [
                    'label'    => 'oro.campaign.emailcampaign.scheduled_for.label',
                    'required' => false,
                ]
            )
            ->add(
                'campaign',
                CampaignSelectType::class,
                [
                    'label' => 'oro.campaign.emailcampaign.campaign.label',
                ]
            )
            ->add(
                'marketingList',
                MarketingListSelectType::class,
                [
                    'label'    => 'oro.campaign.emailcampaign.marketing_list.label',
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'text',
                TextareaType::class,
                [
                    'label'    => 'diglin.campaign.smscampaign.text.label',
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ]
            );

        $this->addTransport($builder);
    }

    protected function addTransport(FormBuilderInterface $builder)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $options = [
                    'label'    => 'oro.campaign.emailcampaign.transport.label',
                    'required' => true,
                    'mapped'   => false,
                    'constraints' => [new NotBlank()],
                ];

                /** @var SmsCampaign $data */
                $data = $event->getData();
                if ($data) {
                    $choices = $this->smsTransportProvider->getVisibleTransportChoices();
                    $currentTransportName = $data->getTransport();
                    if (!array_key_exists($currentTransportName, $choices)) {
                        $currentTransport = $this->smsTransportProvider
                            ->getTransportByName($currentTransportName);
                        $choices[$currentTransport->getLabel()] = $currentTransport->getName();
                        $options['choices'] = $choices;
                    }
                }

                $form = $event->getForm();
                $form->add('transport', SmsTransportSelectType::class, $options);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign',
            ]
        );
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'diglin_sms_campaign';
    }
}
