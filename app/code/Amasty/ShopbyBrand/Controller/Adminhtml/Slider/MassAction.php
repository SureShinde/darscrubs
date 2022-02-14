<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Controller\Adminhtml\Slider;

use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Amasty\ShopbyBase\Model\OptionSettingRepository;
use Amasty\ShopbyBase\Model\ResourceModel\OptionSetting\Collection;
use Amasty\ShopbyBase\Model\ResourceModel\OptionSetting\CollectionFactory as OptionSettingCollectionFactory;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Backend\App\Action;

class MassAction extends Action
{
    /**
     * @var OptionSettingCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var OptionSettingRepository
     */
    private $optionSettingRepository;

    public function __construct(
        Context $context,
        Filter $filter,
        OptionSettingCollectionFactory $collectionFactory,
        OptionSettingRepository $optionSettingRepository
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->optionSettingRepository = $optionSettingRepository;
    }

    /**
     * Execute action
     *
     * @return Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $value = (bool) $this->getRequest()->getParam('value');
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        if ($collectionSize) {
            foreach ($collection as $item) {
                $item->setData(OptionSettingInterface::IS_SHOW_IN_SLIDER, $value);
                $this->optionSettingRepository->save($item);
            }
            if ($value) {
                $message = __('A total of %1 brand(s) have been added to slider.', $collectionSize);
            } else {
                $message = __('A total of %1 brand(s) have been removed from slider.', $collectionSize);
            }

            $this->messageManager->addSuccessMessage($message);
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }

    /*
     * Check permission via ACL resource
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_ShopbyBrand::slider');
    }
}
