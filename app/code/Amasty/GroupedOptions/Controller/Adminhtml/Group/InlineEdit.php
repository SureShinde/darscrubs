<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Controller\Adminhtml\Group;

use Amasty\GroupedOptions\Model\Backend\Group\Registry as GroupRegistry;
use Amasty\GroupedOptions\Model\GroupAttr;
use InvalidArgumentException;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;

class InlineEdit extends \Amasty\GroupedOptions\Controller\Adminhtml\Group
{
    const ADMIN_RESOURCE = 'Amasty_GroupedOptions::group_options';

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var \Magento\Framework\Json\DecoderInterface
     */
    private $decoder;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        GroupRegistry $groupRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Amasty\GroupedOptions\Model\GroupAttrFactory $groupAttrFactory,
        \Amasty\GroupedOptions\Model\Repository\GroupAttrRepository $GroupAttrRepository,
        \Magento\Backend\Model\SessionFactory $sessionFactory,
        TypeListInterface $typeList,
        \Magento\Framework\Serialize\Serializer\Json $decoder,
        JsonFactory $jsonFactory
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->decoder = $decoder;
        parent::__construct(
            $context,
            $groupRegistry,
            $resultPageFactory,
            $groupAttrFactory,
            $GroupAttrRepository,
            $sessionFactory,
            $typeList
        );
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];
        $postItems = $this->getRequest()->getParam('items', []);

        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }
        foreach ($postItems as $item) {
            $id = $item['group_id'];
            try {
                $model = $this->groupAttrRepository->get($id);
            } catch (NoSuchEntityException $e) {
                return $resultJson->setData([
                    'messages' => [__('This group no longer exists.')],
                    'error' => true,
                ]);
            }
            try {
                $options = $item;
                if (isset($item['option']) && !is_array($item['option'])) {
                    $options['option'] = $this->decoder->unserialize($item['option']);
                }

                $model->addData($this->beforeSetData($model, $options));
                $this->groupAttrRepository->save($model);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $messages[] = $e->getMessage();
                $error = true;
            } catch (\RuntimeException $e) {
                $messages[] = $e->getMessage();
                $error = true;
            } catch (\Exception $e) {
                $messages[] = __('Something went wrong while saving the group.');
                $error = true;
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }

    private function beforeSetData(GroupAttr $model, array $data): array
    {
        if (isset($data['option'])) {
            $data['attribute_options'] = [];
            $data['attribute_values'] = [];
            foreach ($data['option'] as $value) {
                if (isset($value['checked']) && $value['checked']) {
                    $data['attribute_' . $value['type_group'] . 's'][] = $value['value'];
                }
            }
            unset($data['option']);
        }

        if ($model->getName()) {
            try {
                $groupNames = $this->decoder->unserialize($model->getName());
                if (!is_array($groupNames)) {
                    $groupNames = [$groupNames];
                }
            } catch (InvalidArgumentException $e) {
                $groupNames = [$model->getName()];
            }
        } else {
            $groupNames = [];
        }
        $groupNames[Store::DEFAULT_STORE_ID] = $data[GroupAttr::NAME] ?? '';
        $data[GroupAttr::NAME] = $this->decoder->serialize($groupNames);

        return $data;
    }
}
