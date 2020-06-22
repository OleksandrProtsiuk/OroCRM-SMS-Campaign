<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Entity\Repository;

use Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign;
use Doctrine\ORM\EntityRepository;

class SmsCampaignStatisticsRepository extends EntityRepository
{
    public function getSmsCampaignStats(SmsCampaign $smsCampaign): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select(
                [
                    'SUM(ecs.bounceCount) as bounce',
                ]
            )
            ->from('SmsCampaignBundle:SmsCampaignStatistics', 'ecs')
            ->where($qb->expr()->eq('ecs.smsCampaign', ':smsCampaign'))
            ->setParameter('smsCampaign', $smsCampaign);

        return $qb->getQuery()->getSingleResult();
    }
}
