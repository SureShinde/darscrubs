<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Controller\Adminhtml\Group;

use Amasty\GroupedOptions\Model\Product\Attribute\BuildOptionsArray;
use Amasty\GroupedOptions\Model\Product\Attribute\GetUsedForGroups;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Controller\ResultFactory;

class LoadOptions extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Amasty_GroupedOptions::group_options';
    const PARAM_NAME = 'attribute_id';

    /**
     * @var GetUsedForGroups
     */
    private $getUsedForGroups;

    /**
     * @var BuildOptionsArray
     */
    private $buildOptionsArray;

    public function __construct(
        GetUsedForGroups $getUsedForGroups,
        BuildOptionsArray $buildOptionsArray,
        Context $context
    ) {
        parent::__construct($context);
        $this->getUsedForGroups = $getUsedForGroups;
        $this->buildOptionsArray = $buildOptionsArray;
    }

    /**
     * @return ResultJson
     */
    public function execute()
    {
        $attributeId = (int) $this->getRequest()->getParam(self::PARAM_NAME);
        $availableAttributes = $this->getUsedForGroups->execute([$attributeId]);
        $availableOptions = $this->buildOptionsArray->execute($availableAttributes);

        /** @var ResultJson $resultRedirect */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($availableOptions);

        return $resultJson;
    }
}
