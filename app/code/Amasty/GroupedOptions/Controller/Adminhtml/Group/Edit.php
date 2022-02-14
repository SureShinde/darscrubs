<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Controller\Adminhtml\Group;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;

class Edit extends \Amasty\GroupedOptions\Controller\Adminhtml\Group
{
    const ADMIN_RESOURCE = 'Amasty_GroupedOptions::group_options';

    /**
     * @return Redirect|Page
     */
    public function execute()
    {
        if ($id = $this->getRequest()->getParam('group_id')) {
            try {
                $model = $this->groupAttrRepository->get($id);
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This group no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        } else {
            $model = $this->groupAttrFactory->create();
        }
        $data = $this->sessionFactory->create()->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->getGroupRegistry()->setGroup($model);

        $resultPage = $this->resultPageFactory->create();

        // 5. Build edit form
        $resultPage->setActiveMenu('Amasty_GroupedOptions::group_options')
            ->addBreadcrumb(__('Manage Grouped Options'), __('Manage Grouped Options'))
            ->addBreadcrumb(
                $id ? __('Edit Group') : __('New Group'),
                $id ? __('Edit Group') : __('New Group')
            );
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Grouped Options'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getTitle() : __('New Group'));

        return $resultPage;
    }
}
