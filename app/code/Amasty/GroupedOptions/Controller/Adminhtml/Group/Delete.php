<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Controller\Adminhtml\Group;

use Magento\Framework\Exception\NoSuchEntityException;

class Delete extends \Amasty\GroupedOptions\Controller\Adminhtml\Group
{
    const ADMIN_RESOURCE = 'Amasty_GroupedOptions::group_options';

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('group_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->groupAttrRepository->get($id);
                $this->groupAttrRepository->delete($model);
                $this->messageManager->addSuccessMessage(__('You deleted the group.'));
                return $resultRedirect->setPath('*/*/');
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('We can\'t find a group to delete.'));
                return $resultRedirect->setPath('*/*/edit', ['group_id' => $id]);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['group_id' => $id]);
            }
        }
        $this->messageManager->addErrorMessage(__('We can\'t find a group to delete.'));

        return $resultRedirect->setPath('*/*/');
    }
}
