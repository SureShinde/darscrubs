<?php

namespace Firebear\Darscrubs\Helper;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\State;

class Configurable extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $eavAttributeFactory;
    protected $attributeOptionManagement;
    protected $resourceConnection;
    protected $fileSystem;
    protected $fileDriver;
    protected $csvProcessor;
    protected $productRepository;
    protected $productCollectionFactory;
    protected $state;
    protected $fLogger;
    protected $cmd;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Eav\Model\Entity\AttributeFactory $eavAttributeFactory,
        \Magento\Eav\Api\AttributeOptionManagementInterface $attributeOptionManagement,
        ResourceConnection $resourceConnection,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Firebear\ImportExport\Logger\Logger $fLogger,
        \Magento\Framework\File\Csv $csvProcessor,
        ProductRepository $productRepository,
        CollectionFactory $productCollectionFactory,
        State $state
    ) {
        parent::__construct($context);

        $this->eavAttributeFactory = $eavAttributeFactory;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->resourceConnection = $resourceConnection;
        $this->fileSystem = $fileSystem;
        $this->fileDriver = $fileDriver;
        $this->csvProcessor = $csvProcessor;
        $this->productRepository = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->state = $state;
        $this->fLogger = $fLogger;
    }


    public function linkChildToConfigurable($input)
    {
        $action = $input->getOption("action");

        $connection = $this->resourceConnection->getConnection();

        $filePath = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath()
            . "import" . DIRECTORY_SEPARATOR . "fix" . DIRECTORY_SEPARATOR . "child_config_map.csv";

        $rows = $this->csvProcessor->getData($filePath);


        foreach ($rows as $rowIndex => $dataRow) {
            if($rowIndex == 0) continue;

            $new = $dataRow[0];
            $old = $dataRow[1];
            if(empty($old) || empty($new)) continue;
        }

    }

}
