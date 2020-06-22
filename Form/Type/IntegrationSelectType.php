<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationSelectType extends AbstractType
{
    const NAME             = 'diglin_sms_integration_select';
    const ENTITY           = 'Oro\Bundle\IntegrationBundle\Entity\Channel';
    const SMS_CHANNEL_TYPE = 'sms';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param ManagerRegistry $registry
     * @param AclHelper       $aclHelper
     */
    public function __construct(ManagerRegistry $registry, AclHelper $aclHelper)
    {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $integrations = $this->getSmsIntegrations();
        $options = [
            'class'        => self::ENTITY,
            'choice_label' => 'name',
            'choices'      => $integrations,
        ];

        if (count($integrations) != 1) {
            $options['placeholder'] = 'diglin.campaign.smscampaign.integration.select.placeholder';
        }

        $resolver->setDefaults($options);
    }

    /**
     * Get SMS integrations
     */
    protected function getSmsIntegrations(): array
    {
        $qb = $this->registry->getRepository(self::ENTITY)
            ->createQueryBuilder('c')
            ->andWhere('c.type = :channelType')
            ->andWhere('c.enabled = :enabled')
            ->setParameter('enabled', true)
            ->setParameter('channelType', self::SMS_CHANNEL_TYPE)
            ->orderBy('c.name', 'ASC');
        $query = $this->aclHelper->apply($qb);

        return $query->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
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
        return self::NAME;
    }
}
