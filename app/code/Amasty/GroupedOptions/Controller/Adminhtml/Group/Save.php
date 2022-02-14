<?php

namespace Amasty\GroupedOptions\Controller\Adminhtml\Group;

use Amasty\GroupedOptions\Api\Data\GroupAttrInterface;
use Amasty\GroupedOptions\Model\Backend\Group\Registry as GroupRegistry;
use Amasty\GroupedOptions\Model\GroupAttr\Query\IsGroupCodeExist;
use Amasty\GroupedOptions\Plugin\Swatches\Block\LayeredNavigation\RenderLayered\AddGroupOptions;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Save extends \Amasty\GroupedOptions\Controller\Adminhtml\Group
{
    const ADMIN_RESOURCE = 'Amasty_GroupedOptions::group_options';

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var IsGroupCodeExist
     */
    private $isGroupCodeExist;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        GroupRegistry $groupRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Amasty\GroupedOptions\Model\GroupAttrFactory $groupAttrFactory,
        \Amasty\GroupedOptions\Api\Data\GroupAttrRepositoryInterface $groupAttrRepository,
        \Magento\Backend\Model\SessionFactory $sessionFactory,
        TypeListInterface $typeList,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        IsGroupCodeExist $isGroupCodeExist
    ) {
        parent::__construct(
            $context,
            $groupRegistry,
            $resultPageFactory,
            $groupAttrFactory,
            $groupAttrRepository,
            $sessionFactory,
            $typeList
        );
        $this->serializer = $serializer;
        $this->isGroupCodeExist = $isGroupCodeExist;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if data sent
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $this->sessionFactory->create()->setData('amgroup_data', $data);
            $id = (int) $this->getRequest()->getParam('group_id');
            $code = $this->getRequest()->getParam('group_code');
            if ($id) {
                try {
                    $model = $this->groupAttrRepository->get($id);
                } catch (NoSuchEntityException $e) {
                    $this->messageManager->addErrorMessage(__('This group no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            } else {
                $model = $this->groupAttrFactory->create();
                unset($data['group_id']);
            }
            if (!$id || (($model->getId() && $id) && $model->getGroupCode() != $code)) {
                if ($this->isGroupCodeExist->execute($code)) {
                    $this->messageManager->addErrorMessage(__('This group code already exists.'));
                    return $resultRedirect->setPath('*/*/edit', ['group_id' => $id]);
                }
            }
            $model->setData($this->prepareData($data));
            try {
                $this->groupAttrRepository->save($model);
                $this->cacheTypeList->invalidate('amasty_shopby');
                $this->messageManager->addSuccessMessage(__('You saved the group.'));
                $this->sessionFactory->create()->setData('amgroup_data', false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['group_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['group_id' => $this->getRequest()->getParam('group_id')]);
            }
        }

        return $resultRedirect->setPath('*/*/');
    }

    private function prepareData(array $data): array
    {
        $data['name'] = is_array($data['name']) ? $this->serializer->serialize($data['name']) : $data['name'];
        
        $visual = $data[GroupAttrInterface::VISUAL] ?? null;
        if (preg_match('@#([a-f0-9]{3}){1,2}\b@i', $visual)) {
            $type = AddGroupOptions::COLOR_TYPE; // color
        } elseif ($visual) {
            $type = AddGroupOptions::IMAGE_TYPE; // file
        } else {
            $type = AddGroupOptions::TEXT_TYPE; // text
        }
        $data[GroupAttrInterface::TYPE] = $type;

        return $data;
    }
}
