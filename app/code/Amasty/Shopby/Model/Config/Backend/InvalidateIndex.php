<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Config\Backend;

use Magento\CatalogSearch\Model\Indexer\Fulltext\Processor;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class InvalidateIndex extends \Magento\Framework\App\Config\Value
{
    /**
     * @var Processor
     */
    private $indexProcessor;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Processor $indexProcessor,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->indexProcessor = $indexProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave()
    {
        $this->_getResource()->addCommitCallback([$this, 'processValue']);
        return parent::afterSave();
    }

    public function processValue(): void
    {
        if ($this->getValue() != $this->getOldValue()) {
            $this->indexProcessor->markIndexerAsInvalid();
        }
    }
}
