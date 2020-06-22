<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CampaignBundle\Entity\TransportSettings;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @ORM\Entity
 */
class SmsTransportSettings extends TransportSettings
{
    /**
     * @var Channel
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Channel")
     * @ORM\JoinColumn(name="sms_channel_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $channel;

    /**
     * {@inheritdoc}
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag([
                'channel' => $this->getChannel(),
            ]);
        }

        return $this->settings;
    }

    public function getChannel(): ?Channel
    {
        return $this->channel;
    }

    public function setChannel(Channel $channel = null)
    {
        $this->channel = $channel;
    }

    public function __toString(): string
    {
        return (string)$this->getId();
    }
}
