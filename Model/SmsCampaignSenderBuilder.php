<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Model;

use Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign;

class SmsCampaignSenderBuilder
{
    /**
     * @var SmsCampaignSender
     */
    protected $campaignSender;

    /**
     * @param SmsCampaignSender $campaignSender
     */
    public function __construct(SmsCampaignSender $campaignSender)
    {
        $this->campaignSender = $campaignSender;
    }

    public function getSender(SmsCampaign $smsCampaign)
    {
        $this->campaignSender->setSmsCampaign($smsCampaign);
        return $this->campaignSender;
    }
}
