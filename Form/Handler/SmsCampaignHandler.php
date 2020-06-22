<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Form\Handler;

use Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles SmsCampaign form type.
 */
class SmsCampaignHandler
{
    use RequestHandlerTrait;

    const UPDATE_MARKER = 'formUpdateMarker';

    /** @var RequestStack */
    protected $requestStack;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var FormInterface */
    protected $form;

    /**
     * @param RequestStack    $requestStack
     * @param FormInterface   $form
     * @param ManagerRegistry $registry
     */
    public function __construct(
        RequestStack $requestStack,
        FormInterface $form,
        ManagerRegistry $registry
    ) {
        $this->requestStack = $requestStack;
        $this->form = $form;
        $this->registry = $registry;
    }

    /**
     * Process form
     */
    public function process(SmsCampaign $entity): bool
    {
        $this->form->setData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($this->form, $request);
            if (!$request->get(self::UPDATE_MARKER, false) && $this->form->isValid()) {
                $em = $this->registry->getManagerForClass('SmsCampaignBundle:SmsCampaign');
                $em->persist($entity);
                $em->flush();

                return true;
            }
        }

        return false;
    }
}
