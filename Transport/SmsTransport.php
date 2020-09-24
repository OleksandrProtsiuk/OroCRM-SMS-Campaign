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
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

/**
 * Implements the transport to send campaigns SMS.
 */
class SmsTransport implements SmsTransportInterface
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
        $transportSettings = $channel->getTransport();

        /** @var TransportInterface $transport */
        $transport = $this->typeRegistry->getTransportTypeBySettingEntity($transportSettings, $channel->getType());
        $transport->init($transportSettings);

        if ($transport instanceof SendSmsInterface || method_exists($transport, 'sendSms')) {
            foreach ($to as $recipientNumber) {
                $transport->sendSms($recipientNumber, $campaign->getText());
            }
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
