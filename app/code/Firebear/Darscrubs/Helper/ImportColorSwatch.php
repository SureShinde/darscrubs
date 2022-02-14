<?php

namespace Firebear\Darscrubs\Helper;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\State;
use Firebear\Darscrubs\Helper\Image;

class ImportColorSwatch extends \Magento\Framework\App\Helper\AbstractHelper
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
    protected $imgHelper;
    protected $configHelper;

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
        Image $imgHelper,
        Configurable $configHelper,
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
        $this->imgHelper = $imgHelper;
        $this->configHelper = $configHelper;
    }

    public function addOption($optionTitle)
    {
        $magentoAttribute = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'color');

        $attributeCode = $magentoAttribute->getAttributeCode();
        $magentoAttributeOptions = $this->attributeOptionManagement->getItems(
            'catalog_product',
            $attributeCode
        );
        $attributeOptions = ['Test2', 'Test3'];
        $existingMagentoAttributeOptions = [];
        $newOptions = [];
        $counter = 0;

        /*
            $option['value'] <=> eav_attribute_option_value.option_id
            $option['label'] <=> eav_attribute_option_value.value
         */

        foreach($magentoAttributeOptions as $option) {
            if (!$option->getValue()) {
                continue;
            }
            if($option->getLabel() instanceof \Magento\Framework\Phrase) {
                $label = $option->getText();
            } else {
                $label = $option->getLabel();
            }

            if($label == '') {
                continue;
            }

            $existingMagentoAttributeOptions[] = $label;
            $newOptions['value'][$option->getValue()] = [$label, $label];
            $counter++;
        }

        foreach ($attributeOptions as $option) {
            if($option == '') {
                continue;
            }

            if(!in_array($option, $existingMagentoAttributeOptions)) {
                $newOptions['value']['option_'.$counter] = [$option, $option];
            }

            $counter++;
        }

        if(count($newOptions)) {
            $magentoAttribute->setOption($newOptions)->save();
        }
    }

    public function reMappingColors()
    {
        $swatchMapPath = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath()
            . "import" . DIRECTORY_SEPARATOR . "swatch" . DIRECTORY_SEPARATOR . "colors_map.csv";

        $rows = $this->csvProcessor->getData($swatchMapPath);


        foreach ($rows as $rowIndex => $dataRow) {
            if($rowIndex == 0) continue;

            $new = $dataRow[0];
            $old = $dataRow[1];
            if(empty($old) || empty($new)) continue;
        }

        $this->addOption('TEST COLOR');
    }

    public function updateOptions()
    {
        $conn = $this->resourceConnection->getConnection('dms');
        $recordId = $conn->fetchOne("SELECT id FROM ");
        $conn->query("INSERT INTO");
    }

    public function removeUnUsed()
    {
        $magentoAttribute = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'color');
        $attributeCode = $magentoAttribute->getAttributeCode();
        $attributeId = $magentoAttribute->getAttributeId();

        $connection = $this->resourceConnection->getConnection();

        $sql = "select m.option_id, ov.store_id, ov.value_id as op_value_id, ov.value as ov_title, os.type as os_type, os.value as os_value,p.row_id as product_id
        from (SELECT * FROM eav_attribute_option WHERE attribute_id={$attributeId}) as m
        left join eav_attribute_option_value as ov ON ov.option_id=m.option_id
        left join eav_attribute_option_swatch as os ON os.option_id=m.option_id
        left join (
            SELECT row_id, value FROM catalog_product_entity_int 
            WHERE attribute_id={$attributeId} and value is not null and value > 0 group by value
        ) as p ON p.value=m.option_id
        where p.row_id is null
        group by option_id";

        $rows = $connection->fetchAll($sql);

        foreach ($rows as $option) {
            $optId = $option['option_id'];

            $connection->query("delete FROM eav_attribute_option WHERE attribute_id={$attributeId} and option_id={$optId}");
            $connection->query("delete FROM eav_attribute_option_value WHERE option_id={$optId}");
            $connection->query("delete FROM eav_attribute_option_swatch WHERE option_id={$optId}");
        }
    }

    public function removeDuplicates()
    {

    }

    public function fixTypeLength()
    {
        $path = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath()
            . "import" . DIRECTORY_SEPARATOR . "fix_type_length.csv";

        $rows = $this->csvProcessor->getData($path);

        $magentoAttribute = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'length');
        $attrId = $magentoAttribute->getAttributeId();

        $conn = $this->resourceConnection->getConnection();

        foreach ($rows as $rowIndex => $dataRow) {
            if($rowIndex == 0) continue;

            $sku = $dataRow[0];
            $entityId = $conn->fetchOne("SELECT entity_id FROM catalog_product_entity where sku=\"{$sku}\" ");

            $delSql = "DELETE FROM catalog_product_entity_int where attribute_id={$attrId} AND row_id='{$entityId}'";
            $conn->query($delSql);
        }

    }

    private function cleanString($string) {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }

    public function fixConfigProdSku()
    {
        $path = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath()
            . "import" . DIRECTORY_SEPARATOR . "fix_config_prod_sku.csv";

        $rows = $this->csvProcessor->getData($path);

        $conn = $this->resourceConnection->getConnection();

        foreach ($rows as $rowIndex => $dataRow) {
            if($rowIndex == 0) continue;

            $sku = $dataRow[0];
            $newSku = $sku;

            $manufacturer = $dataRow[1];
            if(empty($manufacturer)) {
                continue;
            }

            $entityId = $conn->fetchOne("SELECT entity_id FROM catalog_product_entity where sku=\"{$sku}\" ");
            if(empty($entityId)) {
                continue;
            }

            $tmp = strtolower($manufacturer);
            $tmp = explode(" ", $tmp);
            $manufacturer = $tmp[0];
            $prefix = $manufacturer . "-";

            $newSku  = $this->cleanString($newSku);
            $newSku = strtolower($newSku);
            if(strpos($newSku, $prefix) !== 0) {
                $newSku = $prefix . $newSku;
            }

            if($sku == $newSku) {
                continue;
            }

            $updateSql = "UPDATE catalog_product_entity SET sku=\"{$newSku}\" where entity_id='{$entityId}'";

            try {
                $conn->query($updateSql);
            } catch(Exception $e) {
                echo "[$sku]-[$newSku]-[$entityId] : " . $e->getMessage() . PHP_EOL;
                continue;
            }
            echo "[$sku]-[$newSku]-[$entityId] : Done" . PHP_EOL;
        }
    }

    public function fixConfigProdUrlKey($input)
    {

    }

    public function fixDuplicatesSku($input)
    {
        $action = $input->getOption("action");
        $log = $input->getOption("log");
        $test = $input->getOption("test");

        $path = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath()
            . "import" . DIRECTORY_SEPARATOR . "fix_duplicates_sku.csv";

        $rows = $this->csvProcessor->getData($path);

        $conn = $this->resourceConnection->getConnection();

        $this->state->setAreaCode('adminhtml');

        foreach ($rows as $rowIndex => $dataRow) {
            if($rowIndex == 0) continue;

            $sku = $dataRow[0];

            $sql = "SELECT entity_id FROM catalog_product_entity WHERE sku=\"{$sku}\"";
            $items = $conn->fetchAll($sql);
            $children = [];
            foreach($items as $item) {
                $prodId = $item['entity_id'];

                $product = $this->productRepository->getById($prodId);

                if($product->getTypeId() == 'configurable') {
                    $children = array_merge(
                        $children,
                        $product->getTypeInstance()->getUsedProductIds($product)
                    );
                }

                try {
                    if($test != 'test') {
                        $this->productRepository->delete($product);
                    }
                    echo "[$sku]-[$prodId] : Removed" . PHP_EOL;
                } catch(Exception $e) {
                    echo "[$sku]-[$prodId] : Removed Failed => " . $e->getMessage() . PHP_EOL;
                    continue;
                }

            }
            if($action == 'no-child') {
                continue;
            }
            $children = array_unique($children);
            foreach ($children as $child) {
                /// check parents ///
                if($action == 'check-parent') {
                    $sql = "SELECT parent_id FROM catalog_product_relation WHERE child_id={$child}";
                    $result = $conn->fetchAll($sql);
                    if(count($result) > 0) {
                        if($log == 'log') {
                            //$parents = implode("--", $result);
                            $parents = count($result);
                            echo "Child-[$child] has parents : {$parents}" . PHP_EOL;
                        }
                        continue;
                    }
                }
                /////////////////////

                $sql = "SELECT sku FROM catalog_product_entity WHERE entity_id=\"{$child}\"";
                $cSku = $conn->fetchOne($sql);

                try {
                    if($test != 'test') {
                        $this->productRepository->deleteById($cSku);
                    }
                    echo "Child-[$cSku]-[$child] : Removed" . PHP_EOL;
                } catch(Exception $e) {
                    echo "Child-[$cSku]-[$child] : Removed =>" . $e->getMessage() . PHP_EOL;
                    continue;
                }
            }
        }
    }

    public function fixConfigProdValidation()
    {
        $connection = $this->resourceConnection->getConnection();

        // get all configurable products //
        $this->state->setAreaCode('adminhtml');

        $attr = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'color');
        $attrColor = $attr->getAttributeId();
        $attr = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'length');
        $attrLength = $attr->getAttributeId();
        $attr = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'size');
        $attrSize = $attr->getAttributeId();

        $collection = $this->productCollectionFactory->create();

        $collection = $collection->addAttributeToSelect('entity_id')
            ->addAttributeToFilter('type_id', ['eq' => 'configurable'])
            ->load();
        foreach ($collection as $product) {
            $entityId = $product->getId();

            $delSql = "DELETE FROM catalog_product_entity_int where attribute_id={$attrColor} AND row_id='{$entityId}'";
            $connection->query($delSql);
            $delSql = "DELETE FROM catalog_product_entity_int where attribute_id={$attrLength} AND row_id='{$entityId}'";
            $connection->query($delSql);
            $delSql = "DELETE FROM catalog_product_entity_int where attribute_id={$attrSize} AND row_id='{$entityId}'";
            $connection->query($delSql);

            echo "[$entityId] : processed" . PHP_EOL;
        }
    }

    public function checkConfigProdChild($input)
    {
        $action = $input->getOption("action");

        $connection = $this->resourceConnection->getConnection();

        // get all configurable products //
        $this->state->setAreaCode('adminhtml');

        $collection = $this->productCollectionFactory->create();
        $collection = $collection->addAttributeToSelect('entity_id')
            ->addAttributeToFilter('type_id', ['eq' => 'configurable']);
        if(!empty($action)) {
            $collection->addAttributeToFilter('manufacturer', ['eq' => $action]);
        }
        $collection = $collection->load();
        foreach ($collection as $product) {
            $entityId = $product->getId();
            $sku = $product->getSku();
            $sql = "SELECT child_id FROM catalog_product_relation WHERE parent_id={$entityId}";
            $result = $connection->fetchAll($sql);

            if(count($result) == 0) {
                echo "[$entityId]--[$sku]-- has no child" . PHP_EOL;
            }
        }
    }

    public function setLengthRegularForChild($input)
    {
        $action = $input->getOption("action");
        $connection = $this->resourceConnection->getConnection();

        /*
            1601: BOTTOMS from type
			236: attr id for type
			1619: REGULAR from length
            237: length's attr id
         */
        $sql = "SELECT r.child_id as product_id FROM catalog_product_relation as r
			LEFT JOIN catalog_product_entity_int as len ON len.attribute_id=237 and len.row_id=r.child_id
			LEFT JOIN catalog_product_entity_int as type ON type.attribute_id=236 and type.row_id=r.child_id
			where len.value is null and type.value=1601";

        $result = $connection->fetchAll($sql);
        foreach($result as $row) {
            $productId = $row['product_id'];

            echo "[$productId] : start" . PHP_EOL;
            if($action == "check") {
                continue;
            }

            try {
                $insertSql = "INSERT INTO catalog_product_entity_int (attribute_id, store_id, value, row_id) VALUES(237, 0, 1619, {$productId})";
                $connection->query($insertSql);

                echo "[$productId] : processed" . PHP_EOL;
            } catch(Exception $e) {
                echo "[$productId] : error" . PHP_EOL;
                echo $e->getMessage() . PHP_EOL;
            }
        }
    }
    public function addLengthAttrForBottomConfig($input)
    {
        $action = $input->getOption("action");
        $connection = $this->resourceConnection->getConnection();
        $this->state->setAreaCode('adminhtml');

        $collection = $this->productCollectionFactory->create();
        /*
            1601: BOTTOMS from type
			236: attr id for type
			1619: REGULAR from length
            237: length's attr id
         */
        $attr = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'length');
        $attrLength = $attr->getAttributeId();

        $collection = $collection->addAttributeToSelect('entity_id')
            ->addAttributeToFilter('type_id', ['eq' => 'configurable'])
            ->addAttributeToFilter('type', ['eq' => '1601'])
            ->load();

        foreach ($collection as $product) {
            $entityId = $product->getId();

            $sql = "SELECT * FROM catalog_product_super_attribute WHERE attribute_id={$attrLength} AND product_id={$entityId}";
            $result = $connection->fetchAll($sql);
            if(count($result) == 0) {
                echo "[$entityId] : start" . PHP_EOL;
                if($action == "check") {
                    continue;
                }
                try {
                    $insertSql = "INSERT INTO catalog_product_super_attribute (product_id, attribute_id, position) VALUES({$entityId}, {$attrLength}, 2)";
                    $connection->query($insertSql);

                    echo "[$entityId] : processed" . PHP_EOL;
                } catch(Exception $e) {
                    echo "[$entityId] : error" . PHP_EOL;
                    echo $e->getMessage() . PHP_EOL;
                }
            }
        }
    }

    public function fixConfigAttrValidation($input)
    {
        $action = $input->getOption("action");
        $connection = $this->resourceConnection->getConnection();

        // get all configurable products //
        $this->state->setAreaCode('adminhtml');

        $attr = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'color');
        $attrColor = $attr->getAttributeId();
        $attr = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'length');
        $attrLength = $attr->getAttributeId();
        $attr = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'size');
        $attrSize = $attr->getAttributeId();

        $collection = $this->productCollectionFactory->create();

        $collection = $collection->addAttributeToSelect('entity_id')
            ->addAttributeToFilter('type_id', ['eq' => 'configurable'])
            ->load();
        foreach ($collection as $product) {
            $entityId = $product->getId();

            /// get all super attributes ///
            $sql = "SELECT attribute_id FROM catalog_product_super_attribute WHERE product_id = '{$entityId}'";
            $listSuperAttr = $connection->fetchAll($sql);
            foreach ($listSuperAttr as $item) {
                $sAttr = $item['attribute_id'];
                /// check validity that this config has any child products connected to this attribute ///
                $sql = "
                    SELECT `entity`.`sku`, `product_entity`.`entity_id` AS `product_id`, `attribute`.`attribute_code`, `entity_value`.`value` AS `value_index`, `attribute_label`.`value` AS `super_attribute_label` FROM `catalog_product_super_attribute` AS `super_attribute`
				 INNER JOIN `catalog_product_entity` AS `product_entity` ON product_entity.row_id = super_attribute.product_id AND (product_entity.created_in <= 1 AND product_entity.updated_in > 1)
				 INNER JOIN `catalog_product_super_link` AS `product_link` ON product_link.parent_id = super_attribute.product_id
				 INNER JOIN `eav_attribute` AS `attribute` ON attribute.attribute_id = super_attribute.attribute_id
				 INNER JOIN `catalog_product_entity` AS `entity` ON entity.entity_id = product_link.product_id AND (entity.created_in <= 1 AND entity.updated_in > 1)
				 INNER JOIN `catalog_product_entity_int` AS `entity_value` ON entity_value.attribute_id = super_attribute.attribute_id AND entity_value.store_id = 0 AND entity_value.row_id = entity.row_id
				 LEFT JOIN `catalog_product_super_attribute_label` AS `attribute_label` ON super_attribute.product_super_attribute_id = attribute_label.product_super_attribute_id AND attribute_label.store_id = 0
				 LEFT JOIN `eav_attribute_option` AS `attribute_option` ON attribute_option.option_id = entity_value.value WHERE (super_attribute.product_id = {$entityId}) AND (attribute.attribute_id = '{$sAttr}');
                ";
                $result = $connection->fetchAll($sql);

                if(count($result) == 0) {
                    echo "[$entityId] - [$sAttr]: Invalid" . PHP_EOL;

                    if($action == "fix") {
                        $sql = "DELETE FROM catalog_product_super_attribute WHERE product_id = {$entityId} and attribute_id={$sAttr}";
                        $connection->query($sql);
                    }
                }
            }
            ////////////////////////////////

            //echo "[$entityId] : processed" . PHP_EOL;
        }
    }

    public function fixGender($input)
    {
        $action = $input->getOption("action");
        $connection = $this->resourceConnection->getConnection();

        $attr = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'gender');
        $attrGender = $attr->getAttributeId();

        $optIdDefault = 3355;
        $optIdFemale = 1598;
        $optIdMale = 1597;
        $optIdUnisex = 1599;

        /// get all products that have gender of Default ///
        $sql = "select row_id from catalog_product_entity_int where attribute_id={$attrGender} AND value={$optIdDefault} group by row_id";
        $rows = $connection->fetchAll($sql);

        foreach($rows as $row) {
            $entityId = $row['row_id'];
            $product = $this->productRepository->getById($entityId);
            $name = strtolower($product->getName());
            $urlKey = strtolower($product->getUrlKey());

            $newGender = '';
            if(
                strpos($name, 'female') !== false || strpos($urlKey, 'female') !== false ||
                strpos($name, 'women') !== false || strpos($urlKey, 'women') !== false ||
                strpos($name, 'woman') !== false || strpos($urlKey, 'woman') !== false
            ) {
                $newGender = $optIdFemale;
            } else if(
                strpos($name, 'male') !== false || strpos($urlKey, 'male') !== false ||
                strpos($name, 'men') !== false || strpos($urlKey, 'men') !== false ||
                strpos($name, 'man') !== false || strpos($urlKey, 'man') !== false
            ) {
                $newGender = $optIdMale;
            } else if(strpos($name, 'unisex') !== false || strpos($urlKey, 'unisex') !== false) {
                $newGender = $optIdUnisex;
            }

            if($newGender != '') {
                if($action == 'fix') {
                    $sql = "update catalog_product_entity_int set value={$newGender} where attribute_id={$attrGender} AND row_id={$entityId}";
                    $connection->query($sql);
                    echo "[$entityId] : processed" . PHP_EOL;
                }
            } else {
                echo "[$entityId] : has-no-gender" . PHP_EOL;
            }
        }

    }

    public function listSkuMissingImage($input)
    {
        $this->imgHelper->listSkuMissingImage($input);
    }

    public function fixImage($input)
    {
        $this->imgHelper->fixImage($input);
    }
    public function linkChildToConfigurable($input)
    {
        $this->configHelper->linkChildToConfigurable($input);
    }

    public function removeOrphans($input)
    {
        $action = $input->getOption("action");
        $connection = $this->resourceConnection->getConnection();

        $this->state->setAreaCode('adminhtml');

        $collection = $this->productCollectionFactory->create()->setStoreId(0);

        $collection = $collection->addAttributeToSelect('entity_id')
            ->addAttributeToFilter('type_id', ['eq' => 'simple'])
            ->setVisibility([\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE])
            ->load();
        $total = 0;
        foreach($collection as $product) {
            $entityId = $product->getId();
            $sku = $product->getSku();

            $sql = "SELECT parent_id FROM catalog_product_relation WHERE child_id={$entityId}";
            $parent = $connection->fetchAll($sql);
            if(count($parent) == 0) {
                echo "[$entityId]-[$sku] : orphans : no-parent" . PHP_EOL;
                if($action !== 'check') {
                    try {
                        $this->productRepository->delete($product);
                        echo "[$entityId]-[$sku] : orphans : removed" . PHP_EOL;
                    } catch(Exception $e) {
                        echo $e->getMessage() . PHP_EOL;
                    }
                }
                $total++;
            }
        }

        echo "[$total] : orphans : removed" . PHP_EOL;
    }

    public function checkUrlWithoutSku($input)
    {
        $action = $input->getOption("action");
        $connection = $this->resourceConnection->getConnection();

        $this->state->setAreaCode('adminhtml');


        $filter = explode(":", $action);
        $cmd = $filter[0];
        $type = $filter[1];
        if($type == 'all') {
            $type = '';
        }

        /////////////////////////
        $sql = "SELECT attribute_id FROM eav_attribute WHERE attribute_code='url_key' and entity_type_id=4";
        $attrIdUrlKey = $connection->fetchOne($sql);
        /////////////////////////

        $collection = $this->productCollectionFactory->create()->setStoreId(0);

        $collection = $collection->addAttributeToSelect(['entity_id', 'url_key'])
            ->addAttributeToFilter('type_id', ['eq' => 'simple'])
            ->setVisibility([\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE]);

        if($type != '') {
            $collection->addAttributeToFilter('manufacturer', ['eq' => $type]);
        }

        $collection = $collection->load();
        $total = 0;
        foreach($collection as $product) {
            $entityId = $product->getId();
            $sku = $product->getSku();
            $urlKey = $product->getUrlKey();
            if(strpos($urlKey, $sku) !== false) {
                continue;
            }
            echo "[$entityId]-[$sku] : $urlKey" . PHP_EOL;
            $newUrlKey = "$urlKey-$sku";
            if($cmd != 'fix') {
                continue;
            }
            try {
                //$product->setUrlKey($newUrlKey);
                //$product->save();
                $sql = "UPDATE catalog_product_entity_varchar SET value=\"$newUrlKey\" WHERE row_id=$entityId AND attribute_id=$attrIdUrlKey";
                $connection->query($sql);

                $this->updateProductUrlRewrite($entityId, $urlKey . ".html", $newUrlKey . ".html");
                $total++;
            } catch (Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        }

        echo "[$total] : processed" . PHP_EOL;
    }

    private function updateProductUrlKey($entityId, $oldPath, $newPath)
    {
        $connection = $this->resourceConnection->getConnection();

        $sql = "UPDATE url_rewrite SET request_path=\"{$newPath}\" WHERE request_path like \"%$oldPath\" AND target_path like \"%$entityId\"";
        $connection->query($sql);
    }

    private function updateProductUrlRewrite($entityId, $oldPath, $newPath)
    {
        $connection = $this->resourceConnection->getConnection();

        $sql = "UPDATE url_rewrite SET request_path=\"{$newPath}\" WHERE request_path like \"%$oldPath\" AND target_path like \"%$entityId\"";
        $connection->query($sql);
    }

}
