<?php 

namespace Onesaas\Connect\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\CatalogInventory\Api\StockStateInterface;

class Index extends Action
{
	private $pageNo;
	private $pageSize;
	private $requestId;
	private $lstUpdTm;
	private $ordCreatedTm;
	private $json='';
	
	protected $_appConfigScopeConfigInterface;
	protected $_paymentModelConfig;
	protected $_shippingConfig;
	protected $_stockStateInterface;
	protected $_stockRegistry;
	protected $_shipmentInterface;
	protected $_shipmentFactory;
	/** @var Magento\Sales\Model\Order\ShipmentRepository */
	protected $_shipmentRepository;
	/** @var Magento\Shipping\Model\ShipmentNotifier */
	protected $_shipmentNotifier;
	/** @var Magento\Sales\Model\Order\Shipment\TrackFactory */
	protected $_trackFactory;
	protected $_resultJsonFactory;
	protected $_taxItem;
	protected $timezone;
	
	public function __construct(
	\Magento\Framework\App\Action\Context $context,
	\Magento\Framework\App\Config\ScopeConfigInterface $appConfigScopeConfigInterface,
	\Magento\Payment\Model\Config $paymentModelConfig,
	\Magento\Shipping\Model\Config $shippingConfig,
	\Magento\CatalogInventory\Api\StockStateInterface $stockStateInterface,
	\Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
	\Magento\Sales\Api\Data\ShipmentInterface $shipmentInterface,
	\Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,	
    \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier, 
    \Magento\Sales\Model\Order\ShipmentRepository $shipmentRepository, 
    \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
	\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
	\Magento\Sales\Model\ResourceModel\Order\Tax\Item $taxItem,
	\Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone

	)
	{
	parent::__construct($context);
	$this->_appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
	$this->_paymentModelConfig = $paymentModelConfig;
	$this->_shippingConfig = $shippingConfig;
	$this->_stockStateInterface = $stockStateInterface;
	$this->_stockRegistry = $stockRegistry;
	$this->_shipmentInterface = $shipmentInterface;
	$this->_shipmentFactory = $shipmentFactory;	
    $this->_shipmentNotifier = $shipmentNotifier;
    $this->_shipmentRepository = $shipmentRepository;
    $this->_trackFactory = $trackFactory;
	$this->_resultJsonFactory = $resultJsonFactory;	
	$this->_taxItem = $taxItem;
	$this->timezone = $timezone;
	}
	
	public function execute()
	{		
		// Initialize JSON Response
		$resultJson = $this->_resultJsonFactory->create();
		$version = $this->getOneSaasConnectVersion();
		header('Content-Type: application/json');
		$this->json = array("OneSaas Version" => "$version");
		
		// Verify AccessKey
		if($this->verifyAccessKey($_GET['AccessKey'])) {

			// Parse parameters and initiliase variables
			$OrderCreatedTime = (isset($_GET['OrderCreatedTime']) && (strtotime($_GET['OrderCreatedTime'])>0)) ? Date('Y-m-d H:i:sP', strtotime($_GET['OrderCreatedTime'])) : '2010-01-19T00:00:00+00:00';
			$LastUpdatedTime = (isset($_GET['LastUpdatedTime']) && (strtotime($_GET['LastUpdatedTime'])>0)) ? Date('Y-m-d H:i:sP', strtotime($_GET['LastUpdatedTime'])) : '2010-01-19T00:00:00+00:00';
			$LUT = explode('T',max(trim($LastUpdatedTime), trim($OrderCreatedTime)));
			$OCT = explode('T',trim($OrderCreatedTime));
			$this->requestId = (isset($_GET['Id']) && (is_numeric($_GET['Id']))) ? (int) $_GET['Id'] : NULL;
			$this->pageSize = (isset($_GET['PageSize']) && (is_numeric($_GET['PageSize']))) ? (int) $_GET['PageSize'] : 50;  // Allow override of PageSize from URL
			$this->lstUpdTm = implode(" ",$LUT);
			$this->ordCreatedTm = implode(" ",$OCT);
			$this->pageNo = ((isset($_GET['Page']) && (is_numeric($_GET['Page']))) ? (int) $_GET['Page'] : 0) + 1;
			//Check action
			$action = (isset($_GET['Action']) ? $_GET['Action'] : '');
			$requestType = $_SERVER['REQUEST_METHOD'];

			switch ($action) {
				case 'Contacts':
					$this->contactsAction();
					break;	
				case 'ContactById':
					$this->contactByIdAction();
					break;			
				case 'PaymentGateways':
					$this->configurationsAction('PaymentGateways');
					break;
				case 'ShippingMethods':
					$this->configurationsAction('ShippingMethods');
					break;									
				case 'TaxCodes':
					$this->configurationsAction('TaxCodes');
					break;
				case 'OrderStatuses':
					$this->configurationsAction('OrderStatuses');
					break;	
				case 'PluginFeatures':
					$this->configurationsAction('PluginFeatures');
					break;
				case 'StoreInfo':
					$this->configurationsAction('StoreInfo');
					break;					
				case 'Products':
					$this->productsAction();
					break;
				case 'ProductById':
					$this->productByIdAction();
					break;					
				case 'Orders':
					$this->ordersAction();
					break;
				case 'ShippingTracking':
					$this->shippingtrackingAction();
					break;
				case 'UpdateStock':
					$this->updateStockAction();
					break;						
				default:
					$this->json = array('Error'=>'Invalid Action');
			}
		} else {
			$this->json = array('Error'=>'Invalid API key');
		}
		
		return $resultJson->setData($this->json);  
	}
	
	/*** ProductById API Functions Start ***/
	public function productByIdAction(){
		$this->json = $this->getProductById($this->requestId);
	}
	
	private function getProductById($id) {
		$prod = $this->getProductModel()->load($id);
		if($prod['entity_id'] != NULL)
		{
			return $this->getProductInfo($prod['entity_id'],$prod['updated_at']);
		}
		else
		{
			return array('ErrorCode'=>'Product.ProductNotFound','Message'=>'Product with Id:'.$id.' does not exist!');
		}		
	}	
	/*** ProductById API Functions End ***/	
	
	/*** Product API Functions Start ***/

	public function productsAction(){
		if(!$this->getPageNoIsValid('Product')) {
			$this->json = '';
		} else {
			$this->json = array_merge($this->json,$this->getProducts());
		}
	}
	
	private function getProductModel(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();     
		return $objectManager->create('Magento\Catalog\Model\Product');
	}

	private function getProductCollection($pageSize,$pageNo,$lstUpdTm){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
		$prod_data = $productCollection->create()
					->addFieldToFilter('updated_at', array('gt' => $lstUpdTm))
					->setPageSize($pageSize)
					->setCurPage($pageNo)
					->getData();
		return $prod_data;
	}	
	
	private function getProducts() {
		$content = array();
			$prodCol = $this->getProductCollection($this->pageSize,$this->pageNo,$this->lstUpdTm);
			foreach($prodCol as $prod){
				$content['Products'][] = $this->getProductInfo($prod['entity_id'],$prod['updated_at']);
		}	
		return $content;
	}
	
	private function getProductInfo($id,$LUD){
		$prod = $this->getProductModel()->load($id);
		$type = $prod->getTypeId();
		if($prod->getStatus() == 1)
			$prodStatus = "True";
		else
			$prodStatus = "False";
		$stockItem = $this->_stockRegistry->getStockItem($prod->getEntityId());
		$manageStock = $stockItem->getManageStock();
		if($manageStock == 1)
		{
			$isInventoried = "True";
			$quantity = $stockItem->getQty();
			$isInStock = $stockItem->getIsInStock();			
		}
		else
			$isInventoried = "False";
		$imageUrl = '';	
		if($prod->getImage() != NULL)
			{
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
				$imageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $prod->getImage();
			}
		$prod_info = array(
							'Id' => $id,
							'LastUpdatedDate' => $LUD, 
							'Code' => $prod->getSku(),
							'Name' => $prod->getName(),
							'Description' => strip_tags($prod->getDescription()),
							'IsActive' => $prodStatus,
							'PublicUrl' => $prod->getProductUrl(),
							'ImageUrl' => $imageUrl,
							'SalePrice' => $prod->getPrice(),
							'CostPrice' => $prod->getCost(),
							'IsInventoried' => $isInventoried,
							'IsInStock' => isset($isInStock) ? $isInStock : null,
							'Quantity' => isset($quantity) ? $quantity : null,							
							'Length'=>$prod->getData('ts_dimensions_length'),
							'Width'=>$prod->getData('ts_dimensions_width'),
							'Height'=>$prod->getData('ts_dimensions_height'),
							'Weight'=>$prod->getData('weight'),
							'Type'=>($type=='simple')?'Product':$type
						);	   					
		return $prod_info;
	}

	/*** Product API Functions End ***/		
	
	/*** CustomerById API Functions Start ***/
	public function contactByIdAction(){
		$this->json = $this->getContactById($this->requestId);
	}
	
	private function getContactById($id) {
		$contact = $this->getCustomerModel()->load($id);
		if($contact['entity_id'] != NULL)
		{
			return $this->getCustomerInfo($contact['entity_id'],$contact['updated_at']);
		}
		else
		{
			return array('ErrorCode'=>'Contact.ContactNotFound','Message'=>'Contact does not exist!');
		}		
	}		
	/*** CustomerById API Functions End ***/

	/*** Customer API Functions Start ***/

	public function contactsAction(){
		if(!$this->getPageNoIsValid('Contact')) {
			$this->json = '';
		} else {
			$this->json = array_merge($this->json,$this->getCustomers());
		}
	}

	private function getCustomerModel(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
		return $objectManager->create('Magento\Customer\Model\Customer');
	}

	private function getCustomerCollection($pageSize,$pageNo,$lstUpdTm){
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$customerCollection = $objectManager->create('Magento\Customer\Model\ResourceModel\Customer\CollectionFactory');
			$cust_data = $customerCollection->create()
						->addAttributeToSelect('*')
						->addFieldToFilter('updated_at', array('gt' => $this->lstUpdTm))
						->setPageSize($pageSize)
						->setCurPage($pageNo)
						->getData();
						
		return $cust_data;
	}

	private function getCustomers(){
		$content = array();
		$custCol = $this->getCustomerCollection($this->pageSize,$this->pageNo,$this->lstUpdTm);
		foreach($custCol as $cust) {
			$content['Contacts'][] = $this->getCustomerInfo($cust['entity_id'],$cust['updated_at']);
		}
		return $content;
	}

	private function getCustomerInfo($id,$LUD){
		$cust = $this->getCustomerModel()->load($id);
		if(!$salutation = $cust->getPrefix()){$salutation = '';}
		if(!$taxvat = $cust->getTaxvat()){$taxvat = '';}
		$addressManager = \Magento\Framework\App\ObjectManager::getInstance();
		$billingAddress = array();
		$shippingAddress = array();
		if($cust->getData('default_billing') != 0)
		{
			$custBillingAdd = $addressManager->create('Magento\Customer\Model\Address')->load($cust->getDefaultBilling());
			$streetBill = $custBillingAdd->getStreet();
			
			$billingAddress['BillingAddress'] = array(
					'FirstName' => $custBillingAdd->getFirstname(),
					'LastName' => $custBillingAdd->getLastname(),
					'OrganizationName' => $custBillingAdd->getCompany(),
					'WorkPhone' => $custBillingAdd->getTelephone(),									
					'Line1' => isset($streetBill[0]) ? $streetBill[0] : "",
					'Line2' => isset($streetBill[1]) ? $streetBill[1] : "",
					'Line3' => isset($streetBill[2]) ? $streetBill[2] : "",
					'City' => $custBillingAdd->getCity(),
					'PostCode' => $custBillingAdd->getPostcode(),
					'State' => $custBillingAdd->getRegion(),
					'CountryCode' => $custBillingAdd->getCountryId());									
		}
		if($cust->getData('default_shipping') != 0)
		{
			$custShippingAdd = $addressManager->create('Magento\Customer\Model\Address')->load($cust->getDefaultShipping());	
			$streetShip = $custShippingAdd->getStreet();			
			$shippingAddress['ShippingAddress'] = array(
					'FirstName' => $custShippingAdd->getFirstname(),
					'LastName' => $custShippingAdd->getLastname(),
					'OrganizationName' => $custShippingAdd->getCompany(),
					'WorkPhone' => $custShippingAdd->getTelephone(),									
					'Line1' => isset($streetShip[0])?$streetShip[0]:"",
					'Line2' => isset($streetShip[1])?$streetShip[1]:"",
					'Line3' => isset($streetShip[2])?$streetShip[2]:"",
					'City' => $custShippingAdd->getCity(),
					'PostCode' => $custShippingAdd->getPostcode(),
					'State' => $custShippingAdd->getRegion(),
					'CountryCode' => $custShippingAdd->getCountryId());									
		}		
		$cust_info = array(
							'Id' => $id,
							'LastUpdatedDate' => $LUD,
							'Salutation' =>  $salutation,
							'FirstName' =>  $cust->getFirstname(),
							'LastName' => $cust->getLastname(),
							'Email' => $cust->getEmail(),
							'OrganizationBusinessNumber' => $taxvat,
							'Addresses'=>array_merge($billingAddress,$shippingAddress)	
					);
		return $cust_info;
	}

	/*** Customer API Functions End ***/	

	/*** Order API Functions Start ***/

	public function ordersAction(){
		if(!$this->getPageNoIsValid('Order')) {
			$this->json = '';
		} else {
			$this->json = array_merge($this->json,$this->getOrders());
		}
	}

	private function getOrderModel(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
		return $objectManager->create('Magento\Sales\Model\Order');
	}

	private function getOrderCollection($pageSize,$pageNo,$ordCreatedTm,$lstUpdTm){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$orderCollection = $objectManager->create('Magento\Sales\Model\ResourceModel\Order\CollectionFactory');
		$ord_data = $orderCollection->create()
				->addFieldToFilter('updated_at', array('gt' => $this->lstUpdTm))
				->addFieldToFilter('created_at', array('gt' => $this->ordCreatedTm))
				->setPageSize($pageSize)
				->setCurPage($pageNo)
				->getData();		

		return $ord_data;
	}

	private function getOrders(){
		$content = array();
		$ordCol = $this->getOrderCollection($this->pageSize,$this->pageNo,$this->ordCreatedTm,$this->lstUpdTm);
		
		foreach($ordCol as $ord){
			
			// If it is an order that has been updated after submission, the order is cancelled and a new one is created

			$original_id = $ord['entity_id'];
			$content['Orders'][] = $this->getOrderInfo($ord['entity_id'],$original_id, $ord['updated_at']);
		}
		return $content;
	}
	
	// Try to match the tax rate ($rate) with the taxes applied to the order to get name
	private function getTaxCodeForItem($order,$rate) {
		$tax_items = $this->_taxItem->getTaxItemsByOrderId($order->getId());
		foreach($tax_items as $taxInfo) {
			if ($taxInfo['taxable_item_type'] === 'product' && $taxInfo['tax_percent'] == $rate) {
				return $taxInfo;
			}
		}
	}
	
	private function getTaxRateForShipping($order) {
		$tax_items = $this->_taxItem->getTaxItemsByOrderId($order->getId());
		foreach($tax_items as $taxInfo) {
			if ($taxInfo['taxable_item_type'] === 'shipping' ) {
				return $taxInfo;
			}
		}
	}	
	
	public function getOrderItemInfo($product_id,$product_code,$item,$order){
		if($product_id == null)
		{
			return array('ErrorCode'=>'Product.ProductNotFound','Message'=>'Ordered product could not be found!');
		}
		else
		{
			$item_arr_inner = array(
				'ProductId' => $product_id,
				'ProductCode' => $product_code,
				'ProductName' => $item->getName(),
				'Quantity' => $item->getQtyOrdered(),
				'Price' => $item->getBasePriceInclTax(),
				'UnitPriceExTax' => $item->getBasePrice());
				if ($item->getBaseDiscountAmount()>0) {$item_arr_inner['Discount'] = $item->getBaseDiscountAmount()/$item->getQtyOrdered();}
				if ($item->getBaseTaxAmount()>0) {
					$tax = $this->getTaxCodeForItem($order,$item->getTaxPercent());
					$item_arr_inner['Taxes'] = array(
						'TaxId'=>$tax['code'],
						'TaxCode'=>$tax['code'],
						'TaxName'=>$tax['title'],
						'TaxRate'=>(0.0 + $item->getTaxPercent())/100,
						'TaxAmount'=>$item->getBaseTaxAmount());
				}
			$item_arr_inner['LineTotalIncTax'] = $item->getBaseRowTotalInclTax();
			return $item_arr_inner;			
		}		
	}
	
	public function getOrderBundleItemInfo($orderItemId,$product_id,$order){
		if($product_id == null)
		{
			return array('ErrorCode'=>'Product.ProductNotFound','Message'=>'Ordered product could not be found!');
		}
		else
		{
			$items = $order->getAllItems();
			$items_arr_children = array();
			foreach($items as $item){
				$parentItemId = $item->getParentItemId();
				if($orderItemId == $parentItemId)
				{
					$item_arr_child = array(
						'ProductId' => $item->getProductId(),
						'ProductCode' => $item->getSku(),
						'ProductName' => $item->getName(),
						'Quantity' => $item->getQtyOrdered(),
						'Price' => $item->getBasePriceInclTax(),
						'UnitPriceExTax' => $item->getBasePrice());
						if ($item->getBaseDiscountAmount()>0) {$item_arr_child['Discount'] = $item->getBaseDiscountAmount()/$item->getQtyOrdered();}
						if ($item->getBaseTaxAmount()>0) {
							$tax = $this->getTaxCodeForItem($order,$item->getTaxPercent());
							$item_arr_child['Taxes'] = array(
								'TaxId'=>$tax['code'],
								'TaxCode'=>$tax['code'],
								'TaxName'=>$tax['title'],
								'TaxRate'=>(0.0 + $item->getTaxPercent())/100,
								'TaxAmount'=>$item->getBaseTaxAmount());
						}
						$item_arr_child['LineTotalIncTax'] = $item->getBaseRowTotalInclTax();
						$items_arr_children[]=$item_arr_child;					
				}	
			}
			return $items_arr_children;			
		}		
	}			
	
	private function getOrderInfo($id,$original_id, $LUD){
 		$order = $this->getOrderModel()->load($id);
 		$items = $order->getAllVisibleItems();
 		$items_arr = array();
		foreach($items as $item){
			$product_id = $item->getProductId();
			$order_item = $this->getProductModel()->load($product_id);			
			$type = $order_item->getTypeId();
			$product_code = $item->getSku();
			if($type === 'configurable')
 			{
				$product_id = $this->getProductModel()->getIdBySku($product_code);
				$item_arr_inner = $this->getOrderItemInfo($product_id,$product_code,$item,$order);
				$items_arr[]=$item_arr_inner;
 			}
			elseif($type === 'bundle')
			{
				$orderItemId = $item->getItemId();	
				$sku_type = $order_item->getSkuType();
				$price_type = $order_item->getPriceType();				
				if($sku_type != 0)
				{				
					if ($price_type == 0) // If Price is dynamically generated
					{	
						$item_arr_inner = $this->getOrderBundleItemInfo($orderItemId,$product_id,$order);
						$items_arr=array_merge($items_arr,$item_arr_inner);					
					}
					if ($price_type != 0) // If Price is not dynamically generated
					{
						$item_arr_inner = $this->getOrderItemInfo($product_id,$product_code,$item,$order);
						$items_arr[]=$item_arr_inner;
					}
				}
				else
				{
					if ($price_type == 0) // If Price is dynamically generated
					{	
						$item_arr_inner = $this->getOrderBundleItemInfo($orderItemId,$product_id,$order);
						$items_arr=array_merge($items_arr,$item_arr_inner);					
					}
					if ($price_type != 0) // If Price is not dynamically generated
					{
						$product_code = $order_item->getSku();
						$item_arr_inner = $this->getOrderItemInfo($product_id,$product_code,$item,$order);
						$items_arr[]=$item_arr_inner;
					}															
				}
									
			}
			else
			{
				$item_arr_inner = $this->getOrderItemInfo($product_id,$product_code,$item,$order);
				$items_arr[]=$item_arr_inner;
			}
		}
			
		$payments = $order->getAllPayments();
		$payments_array = array();
		foreach($payments as $key=>$a_payment) {
			try {
				$paymentMethodName = $a_payment->getMethodInstance()->getCode();
			} catch (Exception $e) {
				// Do nothing for now...
			}
			$payments_array['PaymentMethod'] = array(
				'MethodName' => $paymentMethodName,
				'Amount'=>$a_payment->getBaseAmountPaid()
				);
		}

		$shippings_array = array();
		$carrier_code = '';
		$shipping_carrier = $order->getShippingCarrier();
		if (!is_null($shipping_carrier) && is_object($shipping_carrier)) {
			$carrier_code = $shipping_carrier->getCarrierCode();
		}
		
		$shippings_array = array(
			'ShippingMethod'=>$order->getShippingDescription(),
			//'ShippingMethod'=>htmlspecialchars($carrier_code),
			'Amount'=>$order->getBaseShippingInclTax());
			if($order->getBaseShippingTaxAmount() > 0)
				{
					$tax = $this->getTaxRateForShipping($order);
					$shippings_array['Taxes']= array(
						'TaxId'=>$tax['code'],
						'TaxCode'=>$tax['code'],
						'TaxName'=>$tax['title'],
						'TaxRate'=>(0.0 + $tax['tax_percent'])/100,
						'TaxAmount'=>$order->getBaseShippingTaxAmount());				
				}
			else
				{
					//Use the Shipping Country Tax. This when items are WO tax, we don't have a tax rate to compare. 	
				}
			if($order->getData('base_shipping_discount_amount') > 0)
			{
				$shippings_array['Discount'] = $order->getBaseShippingDiscountAmount();
			}			
			
		$credits = $order->getCreditmemosCollection();
		$credits_array = array();

		foreach($credits as $a_credit) {
			$credit_array = $a_credit->getData();
			$items = $a_credit->getItems();
			$items_array = array();
			foreach($items as $item){
				$item_array = array(
					'ProductId' => $item->getProductId(),
					'ProductCode' =>  $item->getSku(),
					'ProductName' => $item->getName(),
					'Quantity' => $item->getQty(),
					'Price' => $item->getBasePriceInclTax(),
					'UnitPriceExTax' => $item->getBasePrice(),
					'TaxAmount' => $item->getBaseTaxAmount());
				if ($item->getBaseDiscountAmount()>0) {$item_array['Discount'] = $item->getBaseDiscountAmount()/$item->getQty();}
				$items_array[] = $item_array;
			}
			$credit_array['Items'] = $items_array;			
			$credits_array[] = $credit_array;
		}	
		
		// Contact Info - whether it is a registered user or a guest
		$custBillingAdd = $order->getBillingAddress();
		$custShippingAdd = $order->getShippingAddress();
		if (!$custShippingAdd && $custBillingAdd) {
			$custShippingAdd = $custBillingAdd;
		}
		if ($custShippingAdd && !$custBillingAdd) {
			$custBillingAdd = $custShippingAdd;
		}
		$contact_info = '';
		
		if ($custShippingAdd && $custBillingAdd) {
			$streetBill = $custBillingAdd->getStreet();
			$streetShip = $custShippingAdd->getStreet();
			if(!$salutation = $order->getData('customer_prefix')){$salutation = '';}
			if(!$taxvat = $order->getData('customer_taxvat')){$taxvat = '';}
			$customerFirstname = ($order->getCustomerFirstname() == '')?(($custBillingAdd->getFirstname()=='')?$custShippingAdd->getFirstname():$custBillingAdd->getFirstname()):$order->getCustomerFirstname();
			$customerLastname = ($order->getCustomerLastname() == '')?(($custBillingAdd->getLastname()=='')?$custShippingAdd->getLastname():$custBillingAdd->getLastname()):$order->getCustomerLastname();

			// Billing & Shipping Optional Addresses
			$addresses = array(
							'BillingAddress'=>array(
											'Salutation'=>($salutation = $custBillingAdd->getData('prefix'))?$salutation:'',
											'FirstName'=>$custBillingAdd->getFirstname(),
											'LastName'=>$custBillingAdd->getLastname(),
											'OrganizationName'=>($company = $custBillingAdd->getData('company'))?$company:'',
											'WorkPhone'=>$custBillingAdd->getTelephone(),
											'Line1'=>isset($streetBill[0])?$streetBill[0]:"",
											'Line2'=>isset($streetBill[1])?$streetBill[1]:"",
											'City'=>$custBillingAdd->getCity(),
											'PostCode'=>$custBillingAdd->getPostcode(),
											'State'=>$custBillingAdd->getRegion(),
											'CountryCode'=>$custBillingAdd->getCountryId()
											),
							'ShippingAddress'=>array(
											'Salutation'=>($salutation = $custShippingAdd->getData('prefix'))?$salutation:'',
											'FirstName'=>$custShippingAdd->getFirstname(),
											'LastName'=>$custShippingAdd->getLastname(),
											'OrganizationName'=>($company = $custShippingAdd->getData('company'))?$company:'',
											'WorkPhone'=>$custShippingAdd->getTelephone(),
											'Line1'=>isset($streetShip[0])?$streetShip[0]:"",
											'Line2'=>isset($streetShip[1])?$streetShip[1]:"",
											'City'=>$custShippingAdd->getCity(),
											'PostCode'=>$custShippingAdd->getPostcode(),
											'State'=>$custShippingAdd->getRegion(),
											'CountryCode'=>$custShippingAdd->getCountryId()
											)
						);
						
			$contact_info = array(
							'Id' => is_null($order->getCustomerId())?"0":$order->getCustomerId(),
							'Salutation' => $salutation,
							'FirstName' => $customerFirstname,
							'LastName' => $customerLastname,
							'WorkPhone'=> $custBillingAdd->getTelephone(),
							'Email'=> $order->getData('customer_email'),
							'OrganizationName'=> $custBillingAdd->getCompany(),
							'OrganizationBusinessNumber'=> $taxvat,
							'Addresses'=>$addresses
						);
		} else {
			// If no contact info, then exclude this order and return
			return "";
		}

		$order_number = $order->getIncrementId();
		
		$currencyCode   = '';
		$currency       = $order->getBaseCurrency(); //$order object
		if (is_object($currency)) {
			$currencyCode = $currency->getCurrencyCode();
		}
		
		// OS-3835 & OS-3558 & OS-5925
		$customFields_array = array(
			'CreditPoints'=>$order->getData('creditpoint_amount'),
			'OrderProcessingFeeExTax'=>$order->getData('base_et_payment_extra_charge_excluding_tax'),
			'OrderProcessingFee'=>$order->getData('base_et_payment_extra_charge'),
			'LastTransactionId'=>$order->getPayment()->getLastTransId()
		);
		
		
		if($order->hasInvoices()){
			foreach($order->getInvoiceCollection() as $inv){
				$customFields_array['Invoices'][] = array(
					'InvNumber'=> 'Inv-'.$inv->getIncrementId().'',
					'Date'=> $inv->getCreatedAt());					
			}
		}
		
		$replaces_order_number_key = 'ReplaceOrderNumber';
		$replaces_order_number = '';
		if (strpos($order_number,'-')) {
		 	$replaces_order_number = $order->getData('relation_parent_real_id');
			$replaced_order_id = $order->getData('relation_parent_id');
			$replaces_order_number_key = 'ReplaceOrderNumber Id="' . $replaced_order_id . '"';
		}

		$ord_info = array(
							'Id' => $id,
							'OrderNumber' => $order_number,
							$replaces_order_number_key => $replaces_order_number,
							'Date' => $order->getCreatedAt(),
							'LastUpdatedDate' => $order->getUpdatedAt(),
							'Type'=>"Order",
							'Status'=> $order->getStatus(),
							'CurrencyCode'=> $currencyCode,
							'Notes'=>$order->getCustomerNote(),
							'Tags'=>'StoreName:'.$order->getStoreName().'',
							'Discounts'=>0.0 + abs($order->getBaseDiscountAmount()) + abs($order->getBaseCreditDiscountAmount()),
							'Total'=>$order->getBaseGrandTotal(),
							'Contact' => $contact_info,
							'Addresses' => $addresses,
							'Items'=>$items_arr,
							'Shipping'=>$shippings_array,
							'Payments'=>$payments_array,
							'Credits'=>$credits_array,
							'CustomFields'=>$customFields_array
						);

		return $ord_info;
	}

	/*** Order API Functions End ***/

	/*** Configuration API Functions Start ***/

	public function configurationsAction($element){
		$this->json = array_merge($this->json,$this->getConfiguration($element));
	}

	private function getTaxRateModel(){		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();     
		return $objectManager->create('Magento\Tax\Model\Calculation\Rate');
	}
	
	private function getConfigCollection(){
		
		//Active Payment methods
		$activeMethods = $this->_paymentModelConfig->getActiveMethods();
		$paymentMethods = array();
		foreach ($activeMethods as $paymentCode=>$paymentModel){
			$paymentTitle = $this->_appConfigScopeConfigInterface->getValue('payment/'.$paymentCode.'/title');
			$paymentMethods['PaymentMethods'][] = array('Name'=>$paymentCode, 'Description'=>$paymentTitle);
		}
		
		// Existing Tax rates
		$trModel = $this->getTaxRateModel();
		$tr_data = $trModel->getCollection()->getData();
		$trs = array();
		foreach($tr_data as $tr){
			//$stateCode = Mage::getModel('directory/region')->load($tr['tax_region_id'])->getCode();
			$trs['TaxCodes'][]=array('Name'=>$tr['code'], 'Rate'=>(0.0 + $tr['rate'])/100, 'CountryCode'=>$tr['tax_country_id'], 'Zip'=>$tr['tax_postcode']);
		}
		
		// Active Shipping methods
		$carriers = $this->_shippingConfig->getActiveCarriers();
		$ShippingMethods=array();
        foreach ($carriers as $carrierCode => $carrierModel) {	
            $carrierTitle = $this->_appConfigScopeConfigInterface->getValue('carriers/' . $carrierCode . '/title',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			$ShippingMethods['ShippingMethods'][]=array('Name'=>$carrierCode, 'Description'=>strip_tags($carrierTitle));
        }
		
		//Order Statuses
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 	
		$orderstatuses = $objectManager->create('Magento\Sales\Model\Order\Status')->getResourceCollection()->getData();
		foreach ($orderstatuses as $orderstatus){	
			$OrderStatuses['OrderStatuses'][]=array('Name'=>$orderstatus['status'], 'Description'=>$orderstatus['label']);
		}
		
		//Plugin Features
		$features_array = array('BatchStockUpdates'=>'false');

		//Store Configuration Info	
		$storeInfo_array['StoreName'] = $this->_appConfigScopeConfigInterface->getValue('general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);	
		$storeInfo_array['Timezone'] = $this->timezone->getConfigTimezone();
		$price_setting = $this->_appConfigScopeConfigInterface->getValue('tax/calculation/price_includes_tax', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$tax_after_discount = $this->_appConfigScopeConfigInterface->getValue('tax/calculation/apply_after_discount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$discount_on_price = $this->_appConfigScopeConfigInterface->getValue('tax/calculation/discount_tax', \Magento\Store\Model\ScopeInterface::SCOPE_STORE); 
		$storeInfo_array['IsSellingPriceIncludingTax'] = ($price_setting === '0') ? false : true ;
		$storeInfo_array['IsTaxAppliedAfterDiscount'] = ($tax_after_discount === '0') ? false : true ;
		$storeInfo_array['IsDiscountTaxed'] = ($discount_on_price === '0') ? false : true ;	
		$config = array('PaymentGateways'=>$paymentMethods,'TaxCodes'=>$trs,'ShippingMethods'=>$ShippingMethods, 'OrderStatuses'=>$OrderStatuses, 'PluginFeatures'=>$features_array, 'StoreInfo'=>$storeInfo_array);
		return $config;
	}

	private function getConfiguration($element){
		$content = array();
		$content = $this->getConfigCollection();
		return $content[$element];
	}

	/*** Configuration API Functions End ***/	

	/*** Order Shipping Tracking API Functions Start ***/

	public function shippingtrackingAction(){
		// Parse posted parameters ShippingTrackingId, OrderNumber, Date, TrackingCode, CarrierCode, CarrierName, Notes
		  
		//Receive the RAW post data.
		$jsonRequest = trim(file_get_contents("php://input"));
		$content = json_decode($jsonRequest,true);
		if (!empty($content)) {			
			foreach($content['OrderShippingTracking'] as $key => $value) {
				switch ($key) {
					case 'Id':
						$OrderId = $value;
						break;
					case 'OrderNumber':
						$OrderIncrementId = $value;
						break;
					case 'Date':
						$Date = (string)$value;
						break;
					case 'TrackingCode':
						$TrackingCode = (string)$value;
						break;
					case 'CarrierCode':
						$CarrierCode = (string)$value;
						break;
					case 'CarrierName':
						$CarrierName = (string)$value;
						break;
					case 'Notes':
						$Notes = (string)$value;
						break;
					default:
						// Not interested
						break;
				}
			}
			if ( ($OrderId != '') ) {
				try {
					$order = $this->getOrderModel()->load($OrderId);
					if(!$order->hasShipments())
					{
						if ($order->canShip()) {
							$convertOrder = $this->_objectManager->create('Magento\Sales\Model\Convert\Order');
							$shipment = $convertOrder->toShipment($order);
							
							// Loop through order items
							foreach ($order->getAllItems() AS $orderItem) {
								// Check if order item has qty to ship or is virtual
								if (! $orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
									continue;
								}
								$qtyShipped = $orderItem->getQtyToShip();
								// Create shipment item with qty
								$shipmentItem = $convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);
								// Add shipment item to shipment
								$shipment->addItem($shipmentItem);
							}

							// Register shipment
							$shipment->register();
							
							try {
								// Save created shipment and order
								$track = $this->_trackFactory->create();
								$track->setCreatedAt($Date);
								$track->setOrderId($shipment->getOrderId());
								$track->setTrackNumber((isset($TrackingCode)) ? $TrackingCode : '-' );
								$track->setTitle((isset($CarrierName)) ? $CarrierName : '-' );
								$track->setCarrierCode((isset($CarrierCode)) ? $CarrierCode : 'custom' );
								$track->setDescription((isset($Notes)) ? $Notes : '-' );
								$shipment->addTrack($track);
								$shipment->save();
								$shipment->getOrder()->save();
								// Send email
								$this->_objectManager->create('Magento\Shipping\Model\ShipmentNotifier')
									->notify($shipment);
								$shipment->save();							
								$order->setStatus('complete')->save();
							} catch (\Exception $e) {
								throw new \Magento\Framework\Exception\LocalizedException(
												__($e->getMessage())
											);
							}
						  }
					}
					else
					{
						$shipment_collection = $order->getShipmentsCollection();	
										
						foreach($shipment_collection as $sc) {
							$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
							$shipment = $objectManager->create('Magento\Sales\Model\Order\Shipment');
							$shipment->load($sc->getId());
							if($shipment->getId() != '') {
								if(!$shipment->getTracks())
								{
									$track = $objectManager->create('Magento\Sales\Model\Order\Shipment\Track')->setShipment($shipment);
									$track->setCreatedAt($Date);
									$track->setOrderId($shipment->getOrderId());
									$track->setTrackNumber((isset($TrackingCode)) ? $TrackingCode : '-' );
									$track->setTitle((isset($CarrierName)) ? $CarrierName : '-' );
									$track->setCarrierCode('custom');
									$track->setDescription((isset($Notes)) ? $Notes : '-' );
									$track->save();									
								}
								else
								{
									//Ignore shipping tracking update if order already has tracking info assigned. 
									/*
									$track_collection = $shipment->getTracksCollection();
									foreach($track_collection as $tc)
									{
										$track = $objectManager->create('Magento\Sales\Model\Order\Shipment\Track');
										$track->load($tc->getId());
										$track->setCreatedAt($Date);
										$track->setOrderId($shipment->getOrderId());
										$track->setTrackNumber((isset($TrackingCode)) ? $TrackingCode : '-' );
										$track->setTitle((isset($CarrierName)) ? $CarrierName : '-' );
										$track->setCarrierCode('custom');
										$track->setDescription((isset($Notes)) ? $Notes : '-' );
										$track->save();										
									}*/	
								}	
							}
						}
						if($shipment){
							if(!$shipment->getEmailSent()){
							$shipment->setSendEmail(true);
							$shipment->setEmailSent(true);
							$shipment->save();
							}
						$order->setStatus('complete')->save();
						}						
					}						
					$this->json = array('Success'=>'Operation Succeeded','ShipmentId'=>$shipment->getIncrementId());
				} catch (Exception $ex) {
					$this->json = array('Error'=>'Shipping tracking update failed: ' . $ex . '. OrderId=' . $OrderId . ' OrderIncrementalId=' . $OrderIncrementId . '');
				}
			} else {
				$this->json = array('Error'=>'Wrong parameters: Order Id = ' . $OrderId . '');
			}
		} else {
			$this->json = array('Error'=>'Wrong format');
		}
	}

	/*** Order Shipping Tracking API Functions End ***/

	/*** Update Stock API Functions Start ***/

	public function updateStockAction() {
		// Parse posted parameters StockUpdateId, ProductCode, StockAtHand, StockAllocated, StockAvailable
		
		//Receive the RAW post data.
		$jsonRequest = trim(file_get_contents("php://input"));
		$content = json_decode($jsonRequest,true);
		if (!empty($content)) {			
			foreach($content['ProductStockUpdate'] as $key => $value) {
				switch ($key) {
					case 'Id':
						$stockUpdateId = $value;
						break;					
					case 'StockAtHand':
						$StockAtHand = $value;
						break;
					case 'StockAllocated':
						$StockAllocated = $value;
						break;
					case 'StockAvailable':
						$StockAvailable = (float)$value;
						break;
					default:
						// Not interested
					break;
				}
			}
			if ($stockUpdateId  != '') 
			{
				$product = $this->getProductModel()->load($stockUpdateId);
				$type = $product->getTypeId();
				if (is_object($product) && $type != 'configurable') {
					//$product->setStockData(['qty' => $StockAvailable, 'is_in_stock' => ($StockAvailable <= 0) ? 0 : 1]);
					//$product->setQuantityAndStockStatus(['qty' => $StockAvailable, 'is_in_stock' => ($StockAvailable <= 0) ? 0 : 1]);
					try {
						$prodSku = $product->getSku();
						$stockItem = $this->_stockRegistry->getstockItemBySku($prodSku);
						$stockItem->setQty($StockAvailable);
						$stockItem->setIsInStock((bool)$StockAvailable);
						$this->_stockRegistry->updateStockItemBySku($prodSku, $stockItem);
						$this->json = array('Success'=>'Operation Succeeded');
					} catch (\Magento\Framework\Exception\NoSuchEntityException $ex) 
					{
						$this->json = array('Error'=>'Stock update failed: ' . htmlspecialchars($ex) . '');	
					}
				}
				else {	
					$this->json = array('Error'=>'Wrong parameters: Product is Configurable or is not object. Id="' . $stockUpdateId  . '" StockAvailable=' . $StockAvailable. '');
				}
			} else {
				$this->json = array('Error'=>'Missing Product Id');
			}				
		}else {
			$this->json = array('Error'=>'Wrong format');
		}
	}
	/*** Update Stock API Functions End ***/	

	/*** General Functions Start ***/

	private function getAccessKey(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$key = $objectManager->create('Onesaas\Connect\Model\Resource\Key\Collection');
		$data = $key->getData();
		return $data[0]['key'];
	}	
	
	private function verifyAccessKey($userKey){
		$key = $this->getAccessKey();
		if($userKey === $key){
			return true;
		}	
	}	
	
	private function getOneSaasConnectVersion(){
		return '4.2.0.6';
	}	
	
	private function getPageNoIsValid($entity){
		if($entity == 'Product') {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
			$productCollections = $productCollection->create()
						->addFieldToFilter('updated_at', array('gt' => $this->lstUpdTm))
						->getData();
						
			$proCount = ceil(count($productCollections)/$this->pageSize);

			if($this->pageNo>$proCount) {
				return False;
			} else {
      			return True;
      		}
		}
		if($entity == 'Contact') {

			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$customerCollection = $objectManager->create('Magento\Customer\Model\ResourceModel\Customer\CollectionFactory');
			$customerCollections = $customerCollection->create()
								->addFieldToFilter('updated_at', array('gt' => $this->lstUpdTm))
								->getData();
								
			$custCount = ceil(count($customerCollections)/$this->pageSize);

			if($this->pageNo>$custCount)
				return False;
			else

      			return True;

		}
		if($entity == 'Order') {
			
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$orderCollection = $objectManager->create('Magento\Sales\Model\ResourceModel\Order\CollectionFactory');
			$orderCollections = $orderCollection->create()
						->addFieldToFilter('updated_at', array('gt' => $this->lstUpdTm))
						->addFieldToFilter('created_at', array('gt' => $this->ordCreatedTm))
						->getData();
						
			$orderCount = ceil(count($orderCollections)/$this->pageSize);

			if($this->pageNo>$orderCount)
				return False;
			else

      			return True;

		}		
	}	
}