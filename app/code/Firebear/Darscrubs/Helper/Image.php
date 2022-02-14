<?php

namespace Firebear\Darscrubs\Helper;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\State;

class Image extends \Magento\Framework\App\Helper\AbstractHelper
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

    public function listSkuMissingImage($input)
    {
        $action = $input->getOption("action");
        $connection = $this->resourceConnection->getConnection();

        $path = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath()
            . "export" . DIRECTORY_SEPARATOR . "list_sku_missing_image_{$action}.csv";

        /*
            N: null, B: bad, G: good
            store0 store1
            N       N
            N       B
            N       G
            B       N
            B       B
            B       G
            G       N
            G       B
            G       G

            N N
            N B
            B N
            B B
            G B
         */

        $sql = "
            select e.sku,i.value as image,i1.value as image1, q.qty as qty from catalog_product_entity as e 
			LEFT JOIN catalog_product_entity_varchar as i ON i.store_id IN(0) AND i.attribute_id=87 AND i.row_id=e.entity_id
			LEFT JOIN catalog_product_entity_varchar as i1 ON i1.store_id IN(1) AND i1.attribute_id=87 AND i1.row_id=e.entity_id
			INNER JOIN catalog_product_entity_int as s ON  s.store_id IN(0) AND s.attribute_id=97 AND s.row_id=e.entity_id
			LEFT JOIN cataloginventory_stock_item as q ON q.product_id=e.entity_id
			WHERE s.value=1 AND 
			(
				(
					(i.value is null) 
					AND
				    (i1.value is null) 
				)
				OR
				(
					(i.value is null) 
					AND
				    (i1.value like '%e71b9ecee9480c918cc0be2c9cffa1a7df88911fc9f2b421a8c85e2259cc4fce.jpeg') 
				)
				OR
				(
					(i.value like '%e71b9ecee9480c918cc0be2c9cffa1a7df88911fc9f2b421a8c85e2259cc4fce.jpeg') 
					AND
				    (i1.value is null) 
				)
				OR
				(
					(i.value like '%e71b9ecee9480c918cc0be2c9cffa1a7df88911fc9f2b421a8c85e2259cc4fce.jpeg') 
					AND
				    (i1.value like '%e71b9ecee9480c918cc0be2c9cffa1a7df88911fc9f2b421a8c85e2259cc4fce.jpeg') 
				)
				OR
				(
					!(i.value is null or i.value like '%e71b9ecee9480c918cc0be2c9cffa1a7df88911fc9f2b421a8c85e2259cc4fce.jpeg')
					AND
				    (i1.value like '%e71b9ecee9480c918cc0be2c9cffa1a7df88911fc9f2b421a8c85e2259cc4fce.jpeg') 
				)
			)
			";

        if($action != '') {
            $sql = "{$sql} and e.type_id='{$action}'";
        }
        $sql = "{$sql} group by e.sku";

        $rows = $connection->fetchAll($sql);

        $result = [['sku', 'image', 'qty']];
        foreach($rows as $row) {
            $result[] = [
                $row['sku'],
                $row['image'],
                $row['qty'],
            ];
        }

        $this->csvProcessor
            ->setDelimiter(',')
            ->setEnclosure('"')
            ->saveData(
                $path,
                $result
            );

    }


    public function fixImage($input)
    {
        $action = $input->getOption("action");

        $connection = $this->resourceConnection->getConnection();

        $filter = explode(":", $action);
        $this->cmd = $filter[0];
        $type = $filter[1];
        if($type == 'all') {
            $type = '';
        }

        $csvPath = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath()
            . "export" . DIRECTORY_SEPARATOR . "list_image_error_{$type}.csv";
        $this->fLogger->setFileName("fix_image_{$type}");

        $collection = $this->productCollectionFactory->create();

        $collection = $collection->addAttributeToSelect('entity_id', 'sku')
            ->addAttributeToFilter('status', ['eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED ]);
        if($type != '') {
            $collection = $collection->addAttributeToFilter('type_id', ['eq' => $type]);
        }
        $collection->load();

        $stores = [0, 1];
        $csvResult = [['sku', 'qty']];
        foreach ($collection as $product) {
            $entityId = $product->getId();
            $sku = $product->getSku();
            
            //if($sku != 'barco-sk0112') continue;

            $this->stampLogger("{$sku}-{$entityId}-started");

            $listGallery = $this->getMediaGallery($entityId);
            $galleryFirstItem = null;
            if(count($listGallery) < 1) {
                $this->stampLogger("{$entityId}-missing-gallery");
            }else {
                $galleryFirstItem = $listGallery[0];
            }

            $fallbackImage = $this->getFallbackImage($entityId, $stores, $galleryFirstItem);
            foreach($stores as $storeId) {
                $image = $this->getImage($entityId, $storeId, 87);
                if($image != '') {
                    if(!$this->checkImageValidity($image)) {
                        $this->stampLogger("{$entityId}-base-noexist");
                        $this->removeImage($entityId, $storeId, 87);

                        $image = '';
                    }
                }

                $small = $this->getImage($entityId, $storeId, 88);
                if($small != '') {
                    if(!$this->checkImageValidity($small)) {
                        $this->stampLogger("{$entityId}-small-noexist");
                        $this->removeImage($entityId, $storeId, 88);

                        $small = '';
                    }
                }

                $thumbnail = $this->getImage($entityId, $storeId, 89);
                if($thumbnail != '') {
                    if(!$this->checkImageValidity($thumbnail)) {
                        $this->stampLogger("{$entityId}-small-noexist");
                        $this->removeImage($entityId, $storeId, 89);

                        $thumbnail = '';
                    }
                }

                $rImage = $image ?? $small ?? $thumbnail;
                /*if($rImage == '') {
                    $rImage = $fallbackImage;
                }*/

                if($image == '') {
                    $this->addImage($entityId, $storeId, 87, $rImage);
                }
                if($small == '') {
                    $this->addImage($entityId, $storeId, 88, $rImage);
                }
                if($thumbnail =='') {
                    $this->addImage($entityId, $storeId, 89, $rImage);
                }
            }

            if(count($listGallery) < 1) {
                if($fallbackImage != '') {
                    $this->addGallery($entityId, $fallbackImage, $stores);
                }else {
                    $this->stampLogger("{$sku}-without-image");
                    $csvResult[] = [$sku, $this->getQty($entityId)];
                }
            }
        }

        $this->csvProcessor
            ->setDelimiter(',')
            ->setEnclosure('"')
            ->saveData(
                $csvPath,
                $csvResult
            );

    }

    private function getFallbackImage($entityId, $stores, $galleryFirstItem)
    {
        foreach($stores as $storeId) {
            $image = $this->getImage($entityId, $storeId, 87);
            if ($image != '') {
                if (!$this->checkImageValidity($image)) {
                    $image = '';
                }
            }

            $small = $this->getImage($entityId, $storeId, 88);
            if ($small  != '') {
                if (!$this->checkImageValidity($small)) {
                    $small = '';
                }
            }

            $thumbnail = $this->getImage($entityId, $storeId, 89);
            if ($thumbnail != '') {
                if (!$this->checkImageValidity($thumbnail)) {
                    $thumbnail = '';
                }
            }

            $rImage = $image ?? $small ?? $thumbnail;
            if($rImage != '') {
                return $rImage;
            }
        }
        return $galleryFirstItem;
    }

    private function getQty($entityId)
    {
        $connection = $this->resourceConnection->getConnection();

        $sql = "SELECT qty FROM cataloginventory_stock_item WHERE product_id={$entityId}";
        $qty = $connection->fetchOne($sql);

        return $qty;
    }
    private function getImage($entityId, $storeId, $attrId)
    {
        /*
            attributeId
            87: base, 88: small, 89: thumbnail, 90: media_gallery
        */
        $connection = $this->resourceConnection->getConnection();

        $sql = "SELECT value from catalog_product_entity_varchar where attribute_id={$attrId} and row_id={$entityId} and store_id={$storeId}";
        $image = $connection->fetchOne($sql);

        return $image;
    }
    private function removeImage($entityId, $storeId, $attrId)
    {
        /*
            attributeId
            87: base, 88: small, 89: thumbnail, 90: media_gallery
        */
        if($this->cmd != "fix") return;

        $connection = $this->resourceConnection->getConnection();

        $sql = "DELETE FROM catalog_product_entity_varchar where attribute_id={$attrId} and row_id={$entityId} and store_id={$storeId}";
        $connection->query($sql);

        $this->stampLogger("{$entityId}-$attrId-$storeId-image-removed", true);
    }
    private function addImage($entityId, $storeId, $attrId, $value)
    {
        if($this->cmd != "fix") return;

        if($value == '') return;

        $connection = $this->resourceConnection->getConnection();

        $sql = "INSERT INTO catalog_product_entity_varchar (attribute_id, store_id, value, row_id) VALUES ($attrId, $storeId, '$value', $entityId)";
        $connection->query($sql);

        $this->stampLogger("{$entityId}-$attrId-$storeId-{$value}-image-added", true);
    }

    private function getMediaGallery($entityId)
    {
        $connection = $this->resourceConnection->getConnection();

        $sql = "SELECT value_id from catalog_product_entity_media_gallery_value_to_entity where row_id={$entityId}";
        $rows = $connection->fetchAll($sql);

        $result = [];
        foreach($rows as $row) {
            $valueId = $row['value_id'];

            $sql = "SELECT value from catalog_product_entity_media_gallery where value_id={$valueId}";
            $image = $connection->fetchOne($sql);
            if($image == '') {
                continue;
            }

            if(!$this->checkImageValidity($image)) {
                $this->stampLogger("{$entityId}-{$valueId}-media-noexist", true);
                $this->removeFromGallery($entityId ,$valueId);
                continue;
            }

            $result[] = ['value_id' => $valueId, 'image' => $image];
        }

        return $result;
    }
    private function removeFromGallery($entityId, $valueId)
    {
        if($this->cmd != "fix") return;

        $connection = $this->resourceConnection->getConnection();

        $sql = "DELETE FROM catalog_product_entity_media_gallery_value_to_entity WHERE value_id={$valueId}";
        $connection->query($sql);

        $sql = "DELETE FROM catalog_product_entity_media_gallery_value WHERE value_id={$valueId}";
        $connection->query($sql);

        $sql = "DELETE FROM catalog_product_entity_media_gallery WHERE value_id={$valueId}";
        $connection->query($sql);

        $this->stampLogger("{$entityId}-{$valueId}-media-removed", true);
    }

    private function addGallery($entityId, $value, $stores)
    {
        if($this->cmd != "fix") {
            $this->stampLogger("{$entityId}-{$value}-media-added", true);
            return;
        }

        $connection = $this->resourceConnection->getConnection();

        $sql = "INSERT INTO catalog_product_entity_media_gallery (attribute_id, value, media_type, disabled) VALUES (90, '{$value}', 'image', 0)";
        $connection->query($sql);
        $valueId = $connection->lastInsertId("catalog_product_entity_media_gallery");

        foreach($stores as $storeId) {
            $sql = "INSERT INTO catalog_product_entity_media_gallery_value (value_id, store_id, position, disabled, row_id)	VALUES ({$valueId}, {$storeId}, 1, 0, {$entityId})";
            $connection->query($sql);
        }

        $sql = "INSERT INTO catalog_product_entity_media_gallery_value_to_entity (value_id, row_id) VALUES ({$valueId}, {$entityId})";
        $connection->query($sql);

        $this->stampLogger("{$entityId}-{$value}-media-added", true);
    }

    private function checkImageValidity($image)
    {
        $path = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath()
            . "catalog" . DIRECTORY_SEPARATOR . "product" . $image;
        return $this->fileDriver->isExists($path);
    }
    private function stampLogger($message, $flogger = false)
    {
        echo $message . PHP_EOL;
        if($flogger) {
            $this->fLogger->debug($message);
        }
    }
}
