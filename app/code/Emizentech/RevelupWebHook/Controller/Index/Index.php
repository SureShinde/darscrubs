<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Emizentech\RevelupWebHook\Controller\Index;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
class Index extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    protected $resultPageFactory;
    protected $jsonHelper;
    protected $request;
    private $searchCriteriaBuilder;
    private $sourceItemRepository;
    protected $helper;
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\InventoryApi\Api\SourceItemRepositoryInterface $sourceItemRepository,
        \Magento\InventoryCatalogAdminUi\Observer\ProcessSourceItemsObserver $sourceItemsProcessor,
        \Emizentech\RevelupWebHook\Helper\ReveupOrderSync $helper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->request = $request;
        $this->curlFactory = $curlFactory;
        $this->scopeConfig = $scopeConfig;
        $this->_productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceItemsProcessor = $sourceItemsProcessor;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {   
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/revelupwebhook.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('RevelupWebHook Hitting Checking');
        //$logger->info(var_export(file_get_contents('php://input'),true));
        $json = $this->request->getContent();
        $logger->info('RevelupWebHook Hitting Checking Second');
        try {
           
            $decode = json_decode($json,true);
            $this->helper->createOrder($decode);
            $logger->info('RevelupWebHook Hitting Checking Try');
            if(!empty($decode)){
            $logger->info('RevelupWebHook Hitting Checking Try if condition');    
                foreach($decode['items'] as $values)
                {   
                    $logger->info('RevelupWebHook Hitting Checking Try if condition '.
                        $values['product'].' '.$values['quantity']);
                    $this->getReveupItemQty($values['product'],$values['quantity']);
                }
            }

            return $this->jsonResponse('your response');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
     /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    public function getReveupItemQty($productUrl,$qty)
    {       
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/revelupwebhook.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info('RevelupWebHook Hitting Checking getReveupItemQty');

            $sourceCode = 'default';
            $api_url = $this->scopeConfig->getValue('relealup/genetal/api_endpoint',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $user = $this->scopeConfig->getValue('relealup/genetal/api_key',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $api_auth  = $user;
            $dynamicUrl     = $api_url.''.$productUrl;
            $httpAdapter    = $this->curlFactory->create();
            $httpAdapter->write(\Zend_Http_Client::GET, $dynamicUrl, '1.1', ["Content-Type:application/json","api-authentication:$api_auth"]);
            $result         = $httpAdapter->read();
            $body           = \Zend_Http_Response::extractBody($result);
            if($body)
            {   
                $logger->info('RevelupWebHook Hitting Checking getReveupItemQty under body');
                $producInfo  = $this->jsonHelper->jsonDecode($body,true); 
                   try {  
                          $logger->info('RevelupWebHook Hitting Checking getReveupItemQty under body sku'.$producInfo['sku'].' qty '.$qty);
                          $product = $this->_productRepository->get($producInfo['sku']);
                          if($product){
                            $sourceItems = $this->getSourcesItems($sourceCode,$product->getSku());
                            $stockQty = '';
                            foreach ($sourceItems as $sourceItem) {
                                $stockQty = $sourceItem->getQuantity();   
                              }
                            if($stockQty){
                            $logger->info('RevelupWebHook Hitting Checking getReveupItemQty under body last qty sku'.$producInfo['sku'].' qty '.$qty.' last qty '.$stockQty);    
                            $stockQty = $stockQty-$qty;    
                            //$this->InventorySourceUpdate($product->getSku(),$stockQty,'default');
                            }  
                          }
                       } catch (\Exception $e) {
                            $this->logger->info('Error '.$e);
                     }
                
            }

    }
    public function InventorySourceUpdate($sku,$qty,$sourceCode)
    {
        try {  
              $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/revelupwebhook.log');
              $logger = new \Zend\Log\Logger();
              $logger->addWriter($writer);
              $logger->info('RevelupWebHook Hitting InventorySourceUpdate');

              $source_data['source_code'] = $sourceCode;
              $source_data['quantity']    = $qty;
              $source_data['status']      = 1;
              $this->sourceItemsProcessor->process(
                    $sku,
                    $source_data
                );
              $logger->info('Magento inventory update from revelup');
              $logger->info(print_r($result,true));
             } catch (\Exception $e) {
                  $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/revelupwebhook.log');
                  $logger = new \Zend\Log\Logger();
                  $logger->addWriter($writer);
                  $logger->info('RevelupWebHook Hitting InventorySourceUpdate error');
                  $logger->info('Error '.$e);
                  $this->logger->info('Error '.$e);
             }
    }
    public function getSourcesItems($souceCode, $sku)
    {   
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('source_code', $souceCode)
            ->addFilter('sku', $sku)
            ->create();
        $sourceItemData = $this->sourceItemRepository->getList($searchCriteria);
        return $sourceItemData->getItems();
    }
}

