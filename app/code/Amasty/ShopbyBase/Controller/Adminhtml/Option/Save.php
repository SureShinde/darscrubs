<?php

namespace Amasty\ShopbyBase\Controller\Adminhtml\Option;

use Amasty\ShopbyBase\Api\Data\OptionSettingRepositoryInterface;
use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Amasty\ShopbyBase\Model\Cache\Type;
use Magento\Backend\App\Action;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Save extends \Amasty\ShopbyBase\Controller\Adminhtml\Option
{
    /**
     * @var  TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OptionSettingRepositoryInterface
     */
    private $optionSettingRepository;

    public function __construct(
        Action\Context $context,
        TypeListInterface $typeList,
        LoggerInterface $logger,
        OptionSettingRepositoryInterface $optionSettingRepository
    ) {
        parent::__construct($context);
        $this->cacheTypeList = $typeList;
        $this->logger = $logger;
        $this->optionSettingRepository = $optionSettingRepository;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $filterCode = $this->getRequest()->getParam('filter_code');
        $optionId = (int)$this->getRequest()->getParam('option_id');
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        if ($data = $this->getRequest()->getPostValue()) {
            try {
                $issetUrlAlias = isset($data['url_alias']) && $data['url_alias'];
                if ($issetUrlAlias && !$this->isUniqueAlias($data['url_alias'], $optionId)) {
                    $this->messageManager->addErrorMessage(
                        __('A brand with the same URL alias already exists. Please enter a unique value.')
                    );
                    if ($this->getRequest()->isAjax()) {
                        $this->_redirectRefer();
                    } else {
                        $this->redirectBack($filterCode, $optionId, $storeId);
                    }

                    return;
                }
                /** @var \Amasty\ShopbyBase\Model\OptionSetting $model */
                $model = $this->_objectManager->create(\Amasty\ShopbyBase\Model\OptionSetting::class);
                $data = $this->filterData($data);

                $model->saveData($filterCode, $optionId, $storeId, $data);

                $this->cacheTypeList->invalidate(Type::TYPE_IDENTIFIER);
                $this->messageManager->addSuccessMessage(__('You saved the item.'));
                $this->_session->setPageData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->redirectBack($filterCode, $optionId, $storeId);
                    return;
                }
                $this->_redirectRefer();
                return;
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->_redirectRefer();
                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Something went wrong while saving the item data. Please review the error log.')
                );
                $this->logger->critical($e);
                $this->_session->setPageData($data);
                $this->_redirectRefer();
                return;
            }
        }
        $this->_redirectRefer();
    }

    private function redirectBack(string $filterCode, int $optionId, int $storeId): void
    {
        $this->_redirect(
            '*/*/edit',
            [
                'filter_code' => $filterCode,
                'option_id' => $optionId,
                'store' => $storeId
            ]
        );
    }

    protected function _redirectRefer()
    {
        $this->_forward('settings');
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    protected function filterData($data)
    {
        $inputFilter = new \Zend_Filter_Input(
            [],
            [],
            $data
        );
        $data = $inputFilter->getUnescaped();
        $data[OptionSettingInterface::TOP_CMS_BLOCK_ID]
            = ($data[OptionSettingInterface::TOP_CMS_BLOCK_ID] ?? '') ?: null;
        $data[OptionSettingInterface::BOTTOM_CMS_BLOCK_ID]
            = ($data[OptionSettingInterface::BOTTOM_CMS_BLOCK_ID] ?? '') ?: null;

        return $data;
    }

    private function isUniqueAlias(string $alias, int $optionId): bool
    {
        try {
            $option = $this->optionSettingRepository->get($alias, 'url_alias');
            $isUnique = $option->getValue() == $optionId;
        } catch (NoSuchEntityException $e) {
            $isUnique = true;
        }

        return $isUnique;
    }
}
