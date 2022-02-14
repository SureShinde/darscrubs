<?php
namespace Firebear\Darscrubs\Model\Export\Order;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;


/**
 * Class Converter
 * @package Firebear\Darscrubs\Model\Export\Order
 */
class Converter
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CustomerResourceModel
     */
    private $customerResource;

    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $customerGenderAttributeCache = [];

    /**
     * Converter constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerResourceModel $customerResource
     * @param Subscriber $subscriber
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CustomerResourceModel $customerResource,
        Subscriber $subscriber,
        LoggerInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->customerResource = $customerResource;
        $this->logger = $logger;
        $this->subscriber = $subscriber;
    }

    /**
     * Convert row data
     *
     * @param array $dataSet
     * @return array
     * @throws LocalizedException
     */
    public function covertOrderData(array $dataSet)
    {
        $convertedData = [];

        $orderData = $this->getOrderRow($dataSet);
        $orderItemsData = $this->getOrderItemsRows($dataSet);
        $shippingAddressData = $this->getAddressRow($dataSet, 'shipping');
        $billingAddressData = $this->getAddressRow($dataSet, 'billing');

        foreach ($orderItemsData as $orderItemRow) {
            $convertedData[] = [
                '01', // Company Number, Barco Company Number (Always 01)
                '46301', // FCCustomerID, Barco Account Number for Customer
                $orderData['customer_id'], // CustomerID, Storefront Unique Customer Identifier
                '', // CustomerType, Barco Customer Type
                $orderData['entity_id'], // OrderNumber, Web Order Number
                $orderData['entity_id'], // PONumber, Customer PO Number. If left blank, will default to Order Number.
                strtotime($orderData['created_at']), // OrderDate, Date Order is created on the Website
                strtotime($orderData['shipment:created_at']), // Start Ship Date
                $this->getOrderCancelDate($orderData['entity_id']), // CancelDate
                '', // Do not populate
                '', // Do not populate
                '', // Do not populate
                '', // Do not populate
                '', // Do not populate
                '', // Do not populate
                '', // Do not populate
                '', // Do not populate
                $orderItemRow['item:item_id'], // Storefront OrderItemID – Unique for each item in an order for all orders
                $orderItemRow['shipment_item:sku'], // UPC Code, If UPC codes are used, then fields 20,21,22,23 can be ignored
                '', // Do not populate
                '', // Barco Color Code
                '', // Size Type
                $orderItemRow['product:size'], // Size
                $orderData['order_currency_code'], // Currency Code
                $orderItemRow['item:price_incl_tax'], // UnitPrice
                $orderItemRow['item:qty_ordered'], // Quantity
                $shippingAddressData['address:entity_id'], // ShipToAddressID
                $shippingAddressData['address:firstname'] . ' ' . $shippingAddressData['address:lastname'], // ShipToName [address:address_type] => shipping
                $shippingAddressData['address:street'], // ShipToAddress1
                '', // ShipToAddress2
                '', // ShipToAddress3
                $shippingAddressData['address:city'], // ShipToCity
                $shippingAddressData['address:region'], // ShipToState
                $shippingAddressData['address:postcode'], // ShipToZip
                $shippingAddressData['address:country_id'], // ShipToCountry
                str_replace(' ', '', $shippingAddressData['address:telephone']), // ShipToPhone
                $shippingAddressData['address:email'], // ShipToEmail
                $shippingAddressData['shipping_method'], // ShipMethod
                '', // Special Instructions
                $billingAddressData['address:entity_id'], // BillToAddressID
                $billingAddressData['address:firstname'] . ' ' . $billingAddressData['address:lastname'], // BillToName
                $billingAddressData['address:street'], // BillToAddress1
                '', // BillToAddress2
                '', // BillToAddress3
                $billingAddressData['address:city'], // BillToCity
                $billingAddressData['address:region'], // BillToState
                $billingAddressData['address:postcode'], // BillToZip
                $billingAddressData['address:country_id'], // BillToCountry
                str_replace(' ', '', $billingAddressData['address:telephone']), // BillToPhone
                $orderData['tax:code'], // TaxCode
                '', // Do not Populate
                '', // Do not Populate
                $orderData['email_sent'], // MailingListStatus
                '', // Web Account Login
                $orderData['customer_firstname'], // First Name
                $orderData['customer_lastname'], // Last Name
                $orderData['customer_middlename'], // Middle Initial
                $this->getCustomerGender($orderData['customer_gender']), // Gender
                '', // Company
                '', // Position (Title)
                // Market
                '', // Do not populate
                $billingAddressData['address:telephone'], // Phone Number
                $this->isSubscribed($orderData['email_sent']), // Receives Emails
                '', // Do not populate
                '', // Do not populate
                '', // Do not populate
                '', // Embellishment Code
                '', // Embellishment notes Line 1
                '', // Embellishment notes Line 2
                '', // Embellishment notes Line 3
                '', // Embellishment notes Line 4
                '', // Do not populate
                '', // Source Code
                '', // Do not populate
                '', // Do not populate
                '', // Do not populate
                $this->getShippingStatus($orderData['state']), // Ship Complete
                '', // Do not populate
                'DSHP' // Drop Ship Code
            ];
        }

        return $convertedData;
    }

    /**
     * Get order data
     *
     * @param array $dataSet
     * @return array
     */
    private function getOrderRow($dataSet)
    {
        return $dataSet[0];
    }

    /**
     * Get order items rows
     *
     * @param $dataSet
     * @return array
     */
    private function getOrderItemsRows($dataSet)
    {
        $rows = [];
        foreach ($dataSet as $row) {
            if (!empty($row['item:item_id'])) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Get address data row
     *
     * @param array $dataSet
     * @param string $type
     * @return array|null
     */
    private function getAddressRow($dataSet, $type)
    {
        foreach ($dataSet as $row) {
            if (!empty($row['address:address_type']) && $row['address:address_type'] == $type) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Get customer gender
     *
     * @param $value
     * @return string
     * @throws LocalizedException
     */
    private function getCustomerGender($value)
    {
        if (!isset($this->customerGenderAttributeCache[$value])) {
            $this->customerGenderAttributeCache[$value] = $this->customerResource
                ->getAttribute('gender')
                ->getSource()
                ->getOptionText($value);
        }

        return (string) $this->customerGenderAttributeCache[$value];
    }

    /**
     * Is subscribed
     *
     * @param string $email
     * @return string
     */
    private function isSubscribed($email)
    {
        $status = 'N';

        $subscriber = $this->subscriber->loadByEmail($email);
        if ($subscriber->isSubscribed()) {
            $status = 'Y';
        }

        return $status;
    }

    /**
     * Get shipping status
     *
     * @link https://docs.magento.com/user-guide/sales/order-status.html
     * @param string $status
     * @return string
     */
    private function getShippingStatus($status)
    {
        return $status === 'complete' ? 'Y' : 'N';
    }

    /**
     * @param $orderId
     * @return string|int
     */
    private function getOrderCancelDate($orderId)
    {
        try {
            $order = $this->orderRepository->get($orderId);

            foreach ($order->getStatusHistoryCollection() as $status) {
                if ($status->getStatus() == Order::STATE_CANCELED) {
                    return strtotime($status->getCreatedAt());
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return '';
    }
}
