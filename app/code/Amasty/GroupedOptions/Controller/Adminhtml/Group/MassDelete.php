<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Controller\Adminhtml\Group;

use Amasty\GroupedOptions\Api\Data\GroupAttrRepositoryInterface;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\CollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Amasty_GroupedOptions::group_options';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var GroupAttrRepositoryInterface
     */
    protected $groupAttrRepository;

    public function __construct(
        Context $context,
        Filter $filter,
        GroupAttrRepositoryInterface $groupAttrRepository,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->groupAttrRepository = $groupAttrRepository;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Execute action
     *
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect;
        }
        $collectionSize = $collection->getSize();

        foreach ($collection as $group) {
            $this->groupAttrRepository->delete($group);
        }
        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $collectionSize));

        return $resultRedirect;
    }
}
