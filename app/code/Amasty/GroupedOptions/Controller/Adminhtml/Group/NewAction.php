<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Controller\Adminhtml\Group;

use Amasty\GroupedOptions\Model\Backend\Group\Registry as GroupRegistry;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Controller\Result\Forward;

class NewAction extends \Amasty\GroupedOptions\Controller\Adminhtml\Group
{
    const ADMIN_RESOURCE = 'Amasty_GroupedOptions::group_options';

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        GroupRegistry $groupRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Amasty\GroupedOptions\Model\GroupAttrFactory $groupAttrFactory,
        \Amasty\GroupedOptions\Api\Data\GroupAttrRepositoryInterface $groupAttrRepository,
        \Magento\Backend\Model\SessionFactory $sessionFactory,
        TypeListInterface $typeList,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct(
            $context,
            $groupRegistry,
            $resultPageFactory,
            $groupAttrFactory,
            $groupAttrRepository,
            $sessionFactory,
            $typeList
        );
    }

    /**
     * @return Forward
     */
    public function execute()
    {
        /** @var Forward $resultForward */
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    }
}
