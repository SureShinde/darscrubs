<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Emizentech\RevelupWebHook\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Service\OrderService;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
class ReveupOrderSync extends AbstractHelper
{
   /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
 
    /**
     * @var ProductFactory
     */
    private $productFactory;
 
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
 
    /**
     * @var CustomerInterfaceFactory
     */
    private $customerInterfaceFactory;
 
    /**
     * @var CartManagementInterface
     */
    private $cartManagementInterface;
 
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepositoryInterface;
 
    /**
     * @var OrderService
     */
    private $orderService;
 
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    protected $jsonHelper;
    protected $_checkoutSession;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactory $productFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param CartManagementInterface $cartManagementInterface
     * @param CartRepositoryInterface $cartRepositoryInterface
     * @param OrderService $orderService
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory,
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerInterfaceFactory,
        CartManagementInterface $cartManagementInterface,
        CartRepositoryInterface $cartRepositoryInterface,
        OrderService $orderService,
        StoreManagerInterface $storeManager,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
         AttributeRepositoryInterface $attributeRepository
    ) {
    	$this->productRepository        = $productRepository;
        $this->productFactory           = $productFactory;
        $this->customerRepository       = $customerRepository;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->cartManagementInterface  = $cartManagementInterface;
        $this->cartRepositoryInterface  = $cartRepositoryInterface;
        $this->orderService             = $orderService;
        $this->storeManager             = $storeManager;
        $this->curlFactory              = $curlFactory;
        $this->scopeConfig              = $scopeConfig;
        $this->jsonHelper               = $jsonHelper;
        $this->attributeRepository      = $attributeRepository;
        $this->_checkoutSession         = $checkoutSession;
        parent::__construct($context);
    }
    public function createOrder($decode)
    {  
      try {	
      	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
      	$itemsInfo = [];
      	$email = '';
      	$customerAddress  = [];
      	$orderIdFromRevel = basename($decode['orderInfo']['resource_uri']);
      	$establishment = basename($decode['orderInfo']['created_at']);
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/revelupwebhook.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('RevelupWebHook Hitting Checking order reveup id '.$orderIdFromRevel);

      	$this->_checkoutSession->setReveupOrderId($establishment."-".$orderIdFromRevel);
      	if(isset($decode['items']))
      	{
      	   foreach($decode['items'] as $_item)
      	   {  
      	   	  $product = $this->getProduct($_item['product']);
              if($product)
              {
                $itemsInfo[] = $this->getRequestProductInfo($product,$_item['quantity']);
              }
      	   }	
      	}

        $establishment = $this->getReuseResource($decode['orderInfo']['establishment']);

        if($establishment['address'])
        {
         $addressInfo  = $this->getReuseResource($establishment['address']);
         $customerInfo = $this->getReuseResource($decode['orderInfo']['customer']); 
         $email        = $customerInfo['phone_number'].'@darscrubs.com';
         $customerAddress['firstname'] = $customerInfo['first_name'];
         $customerAddress['lastname']  = $customerInfo['last_name'];
         $customerAddress['street']    = $addressInfo['line_1'].' '.$addressInfo['line_2'];
         $customerAddress['city']      = $addressInfo['city_name'];
         $customerAddress['country_id']   = $addressInfo['country'];
         $customerAddress['region']    = '';
         $customerAddress['postcode']  = $addressInfo['zipcode'];
         $customerAddress['telephone'] = $customerInfo['phone_number'];
         $customerAddress['save_in_address_book'] = 1;
          		
        }
        $currencySymbol = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $orderData = [
            'currency_id'  => $currencySymbol,
            'email'        => $email,
            'guest_order'  => true,
            'shipping_address' =>$customerAddress,
            'items'=> $itemsInfo
        ];
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $cartId = $this->cartManagementInterface->createEmptyCart();
        $cart = $this->cartRepositoryInterface->get($cartId);
        $cart->setStore($store);
        $cart->setCurrency();
        $cart->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
        $cart->getBillingAddress()->setEmail($orderData['email']);
        // Add items to cart
        
        foreach ($orderData['items'] as $item) { 
        	   if(isset($item['product'])){
	        	$parentdId = $item['product'];
	        	$parentProduct = $objectManager->create('Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable')->getParentIdsByChild($item['product']);
			   if(isset($parentProduct[0])){
			         $parentdId = $parentProduct[0];
			    }
	            $product = $this->productRepository->getById($parentdId);
	            $product->setIsSalable(true);
	            $product->setData('salable',true);
                $request = new \Magento\Framework\DataObject();
	            $request->setData($item);
                try
                {
    	            $cart->addProduct(
    	                $product,
    	                $request,
                        \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_LITE
    	            );
                }
                catch(\Exception $e){
                      $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/revelupwebhook.log');
                      $logger = new \Zend\Log\Logger();
                      $logger->addWriter($writer);
                      $logger->info('RevelupWebHook Hitting Checking error add to cart '.$e->getMessage());
                }
            }
        }
        $cart->save();
        $shippingAddress = $cart->getShippingAddress();
        // Set billing and shipping addresses
        $cart->getBillingAddress()->addData($orderData['shipping_address']);
        $cart->getShippingAddress()->addData($orderData['shipping_address']);
        // Set shipping method
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('freeshipping_freeshipping');
        // Set payment method
        $cart->setPaymentMethod('cashondelivery');
        $cart->getPayment()->importData(['method' => 'cashondelivery']);
 
        $cart->collectTotals();
        $cart->save();
        
        // Place the order
        if(count($itemsInfo)>0){
	      $cart = $this->cartRepositoryInterface->get($cart->getId());
	      $orderId = $this->cartManagementInterface->placeOrder($cart->getId()); 
	      return $orderId;
	        }else{
          return;  
	      }
        } catch (\Exception $e) {
          $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/revelupwebhook.log');
          $logger = new \Zend\Log\Logger();
          $logger->addWriter($writer);
          $logger->info('RevelupWebHook Hitting Checking error add to cart '.$e->getMessage());
          $logger->info(print_r($decode,true));
       }    
    }

    public function getProduct($productUrl)
    {       

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
                $producInfo  = $this->jsonHelper->jsonDecode($body,true);

                   try {  
                   	      if($producInfo['sku']){
                          $product = $this->productRepository->get($producInfo['sku']);
                          return $product; 
                          }
                       } catch (\Exception $e) {
                          return '';
                     }
                
            }

    }
    public function getRequestProductInfo($_product,$qty)
    {
    	try {   
    		    $colorattrid     = '';
	            $colorvalueindex = '';
	            $sizeattrid      = '';
	            $sizevalueindex  = '';
    		    if($_product->getColor())
    		    {	
    		    $colorattrid = $this->attributeRepository->get(Product::ENTITY, 'color')->getAttributeId();
    		    $colorvalueindex = $_product->getColor();    	
    		    }
	    	    if($_product->getSize())
    		    {
    		    $sizeattrid = $this->attributeRepository->get(Product::ENTITY, 'size')->getAttributeId();
    		    $sizevalueindex = $_product->getSize(); 	
    		    }

	    	    $parentdId = '';
	    	    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	            $FormKey       = $objectManager->get('Magento\Framework\Data\Form\FormKey');
	            $productTypeInstance = $objectManager->get('Magento\ConfigurableProduct\Model\Product\Type\Configurable');
	            $parentProduct = $objectManager->create('Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable')->getParentIdsByChild($_product->getId());
				     if(isset($parentProduct[0])){
				         $parentdId = $parentProduct[0];
				}
                if($parentdId)
                {
                $_product = $this->productRepository->getById($parentdId);
                }

	    		$requestinfo = ''; 
	            $listBlock = $objectManager->get('\Magento\Catalog\Block\Product\ListProduct');
	            $addToCartUrl =  $listBlock->getAddToCartPostParams($_product);
	            if($_product->getTypeId()=='configurable'){
	              if($colorvalueindex&&!$sizevalueindex)
	              {
	                 $requestinfo   = [
	                               'uenc'=>$addToCartUrl['data']['uenc'],
	                               'product'=>$_product->getId(),
	                               'form_key'=>$FormKey->getFormKey(),
	                               'qty'=>$qty,
	                               'super_attribute' => array(
	                                        $colorattrid => $colorvalueindex
	                                    )
	                            ];
	              }elseif(!$colorvalueindex&&$sizevalueindex){
	                $requestinfo   = [
	                               'uenc'=>$addToCartUrl['data']['uenc'],
	                               'product'=>$_product->getId(),
	                               'form_key'=>$FormKey->getFormKey(),
	                               'qty'=>$qty,
	                               'super_attribute' => array(
	                                        $sizeattrid  => $sizevalueindex
	                                    )
	                            ];
	              }elseif($colorvalueindex&&$sizevalueindex){
	                $requestinfo   = [
	                               'uenc'=>$addToCartUrl['data']['uenc'],
	                               'product'=>$_product->getId(),
	                               'form_key'=>$FormKey->getFormKey(),
	                               'qty'=>$qty,
	                               'super_attribute' => array(
	                                        $colorattrid => $colorvalueindex,
	                                        $sizeattrid  => $sizevalueindex
	                                    )
	                            ];
	              }  
	            
	            }else{
	            $requestinfo   = [
	                               'uenc'=>$addToCartUrl['data']['uenc'],
	                               'product'=>$_product->getId(),
	                               'form_key'=>$FormKey->getFormKey(),
	                               'qty'=>$qty
	                            ];    
	          }
	            return $requestinfo; 
    	      } catch (\Exception $e) {
                          return '';
           }
    }
    public function getReuseResource($reuseUrl)
    {
        
        try {
            $api_url = $this->scopeConfig->getValue('relealup/genetal/api_endpoint',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $user = $this->scopeConfig->getValue('relealup/genetal/api_key',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $api_auth  = $user;
            $dynamicUrl     = $api_url.''.$reuseUrl;
            $httpAdapter    = $this->curlFactory->create();
            $httpAdapter->write(\Zend_Http_Client::GET, $dynamicUrl, '1.1', ["Content-Type:application/json","api-authentication:$api_auth"]);
            $result         = $httpAdapter->read();
            $body           = \Zend_Http_Response::extractBody($result);
            if($body)
            {   
                $reuseInfo  = $this->jsonHelper->jsonDecode($body,true);

                   try {  
                          return $reuseInfo;
                       } catch (\Exception $e) {
                          return '';
                     }
                
            }

	        } catch (\Exception $e) {
	      } 
    }
}
