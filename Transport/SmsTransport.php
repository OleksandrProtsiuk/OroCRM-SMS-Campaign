<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Transport;

use Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign;
use Diglin\Bundle\SmsCampaignBundle\Form\Type\SmsTransportSettingsType;
use Diglin\Bundle\TwilioOroBundle\Entity\TwilioTransportSettings;
use Diglin\Bundle\TwilioOroBundle\Integration\Transport\TwilioTransport;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;

/**
 * Implements the transport to send campaigns SMS.
 */
class SmsTransport implements TransportInterface
{
    const NAME = 'sms';

    /**
     * @var TypesRegistry
     */
    protected $typeRegistry;
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param TypesRegistry $registry
     */
    public function setConnectorsTypeRegistry(TypesRegistry $registry)
    {
        $this->typeRegistry = $registry;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function send(SmsCampaign $campaign, $entity, array $to)
    {
        /** @var Channel $channel */
        $channel = $campaign->getTransportSettings()->getChannel();

        /** @var TwilioTransportSettings $transportSettings */
        $transportSettings = $channel->getTransport();

        /** @var TwilioTransport $transport */
        $transport = $this->typeRegistry->getTransportTypeBySettingEntity($transportSettings, $channel->getType());
        $transport->init($transportSettings);

        foreach ($to as $recipientNumber) {
            // @todo number transformation? e.g. change 0041 to +41?
            // @todo error handling - what if sending fails, e.g. wrong Twilio configuration
            $transport->sendSms($recipientNumber, $campaign->getText());
        }
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getLabel(): string
    {
        return 'diglin.campaign.smscampaign.transport.' . self::NAME;
    }

    public function getSettingsFormType(): string
    {
        return SmsTransportSettingsType::class;
    }

    public function getSettingsEntityFQCN(): string
    {
        return 'Diglin\Bundle\SmsCampaignBundle\Entity\SmsTransportSettings';
    }
}
