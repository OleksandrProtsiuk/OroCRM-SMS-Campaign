<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\EventListener\MixinListener;
use Oro\Bundle\MarketingListBundle\Model\MarketingListHelper;

/**
 * Adds mixins for sent/unsent SMS campaign datagrids.
 */
class CampaignStatisticDatagridListener
{
    const MIXIN_SENT_NAME   = 'diglin-sms-campaign-marketing-list-sent-items-mixin';
    const MIXIN_UNSENT_NAME = 'diglin-sms-campaign-marketing-list-unsent-items-mixin';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var MarketingListHelper
     */
    protected $marketingListHelper;

    /**
     * @param MarketingListHelper $marketingListHelper
     * @param ManagerRegistry     $registry
     */
    public function __construct(MarketingListHelper $marketingListHelper, ManagerRegistry $registry)
    {
        $this->marketingListHelper = $marketingListHelper;
        $this->registry = $registry;
    }

    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();
        $parameters = $event->getParameters();

        if (!$this->isApplicable($config->getName(), $parameters)) {
            return;
        }

        $smsCampaignId = $parameters->get('smsCampaign');
        $smsCampaign = $this->registry->getRepository('SmsCampaignBundle:SmsCampaign')
            ->find($smsCampaignId);

        if ($smsCampaign->isSent()) {
            $config->getOrmQuery()->resetWhere();
            // $mixin = self::MIXIN_SENT_NAME;
            $mixin = self::MIXIN_UNSENT_NAME; // @todo change to the above one and make sure statistics are shown
        } else {
            $mixin = self::MIXIN_UNSENT_NAME;
        }

        $parameters->set(MixinListener::GRID_MIXIN, $mixin);
    }

    /**
     * This listener is applicable for marketing list grids that has emailCampaign parameter set.
     */
    public function isApplicable(string $gridName, ParameterBag $parameterBag): bool
    {
        if (!$parameterBag->has('smsCampaign')) {
            return false;
        }

        return (bool)$this->marketingListHelper->getMarketingListIdByGridName($gridName);
    }
}
