<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Form\Type;

use Diglin\Bundle\SmsCampaignBundle\Provider\SmsTransportProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SmsTransportSelectType extends AbstractType
{
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
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => $this->smsTransportProvider->getVisibleTransportChoices(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'diglin_sms_campaign_sms_transport_select';
    }
}
