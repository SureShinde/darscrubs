<?php
namespace Emizentech\Revelup\Model;
use Magento\Inventory\Model\ResourceModel\Source as SourceResourceModel;
class DataProvider extends  \Magento\Ui\Component\Form\Element\Input
{
    
    public function prepare()
    {
      parent::prepare();
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();  
      $request = $objectManager->get('Magento\Framework\App\Request\Http');  
      $sourceCode = $request->getParam('source_code');
      if($sourceCode){  
          $connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION'); 
          $result = $connection->fetchRow("SELECT establishment,dearsystem_location_name,dearsystem_location_id FROM `inventory_source` WHERE 
            `source_code` LIKE '".$sourceCode."'");
          if(isset($result['establishment'])){
              $config = $this->getData('config');
              if(isset($config['dataScope']) && $config['dataScope']=='establishment'){
                $config['default']= $result['establishment'];
                $this->setData('config', (array)$config);
              }
           }
           if(isset($result['dearsystem_location_name'])){
              $config = $this->getData('config');
              if(isset($config['dataScope']) && $config['dataScope']=='dearsystem_location_name'){
                $config['default']= $result['dearsystem_location_name'];
                $this->setData('config', (array)$config);
              }
           }
           if(isset($result['dearsystem_location_id'])){
              $config = $this->getData('config');
              if(isset($config['dataScope']) && $config['dataScope']=='dearsystem_location_id'){
                $config['default']= $result['dearsystem_location_id'];
                $this->setData('config', (array)$config);
              }
           }
        }    

    }
}