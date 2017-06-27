<?php

namespace Intelie\LiveObserver\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use Intelie\LiveObserver\Observer\Dispatch\Console;
use \Magento\Framework\DataObject;


class Observe implements ObserverInterface
{

    public function __construct()
    {
        $this->console = new Console();
    }

    private function log($msg)
    {
        $this->console->log(json_encode($msg));
    }

    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();

        switch ($event->getName()) {
            case 'controller_action_predispatch':
                $this->actionPredispatch($event);
                break;
            case 'default':
                $this->actionPredispatch($event);
                break;

            case 'checkout_cart_add_product_complete':
                $this->log_product($event);
                break;

            case 'sales_quote_remove_item':
                $this->log_product($event);
                break;

            case 'catalog_controller_product_view':
                $this->log_product($event);
                break;
            case 'catalog_controller_category_init_after':
                $this->log_catalog($event);
                break;

            case 'customer_login':
                $this->log_customer($event);
                break;
            case 'customer_logout':
                $this->log_customer($event);
                break;
            case 'customer_register_success':
                $this->log_customer($event);
                break;
            case 'customer_save_after_data_object':
                $this->log_customer($event);
                break;
            case 'customer_validate':
                $this->log_customer($event);
                break;
            case 'sales_quote_item_set_product':
                $this->log_product($event);
                break;
            case 'checkout_onepage_controller_success_action':
                $msg['type'] = $event->getName();
                $this->log($msg);
                break;

            case 'checkout_allow_guest': {
                $result = $event->getData('result');
                if ($result->getData('is_allowed')) {
                    $fields = array('name', 'items_qty', 'subtotal', 'items_count', 'customer_email', 'customer_id', 'is_allowed');
                    $quote = $event->getData('quote');
                    $msg = $this->AddBasic($quote, $fields);
                    $msg['type'] = $event->getName();
                    $this->log($msg);
                }
            }
            default:
                break;
        }
        return $this;
    }

    private function log_product($event)
    {
        $fields = array('name', 'qty', 'price', 'category_ids', 'sku');
        $product = $event->getData('product');
        $msg = $this->AddBasic($product, $fields);
        $msg['type'] = $event->getName();
        $this->log($msg);
    }

    private function log_catalog($event)
    {
        $msg['type'] = $event->getName();
        $catalog = $event->getData('category');
        $path = $catalog->getData('url_path');
        $msg['session'] = ((explode("/", $path)));
        $this->log($msg);
    }

    private function log_customer($event)
    {
        $customer = $event->getData('customer');
        if ($customer instanceof DataObject) {
            $fields = array('email', 'firstname', 'lastname', 'country_id', 'company', 'city', 'postcode', 'telephone');
            $msg = $this->AddBasic($customer, $fields);
        } else {
            $msg = array('email' => $customer->getEmail(), 'firstname' => $customer->getFirstname(), 'lastname' => $customer->getLastname());
        }
        $msg['type'] = $event->getName();
        $this->log($msg);
    }


    private function actionPredispatch($event)
    {

        $rq = $event->getData('request');
        $ca = $event->getData('controller_action');

        $msg = ['type' => $event->getName()];
        $msg['RouteName'] = $rq->getRouteName();
        $msg['FullActionName'] = $rq->getFullActionName();
        $msg['getFrontName'] = $rq->getFrontName();
        $msg['getHttpHost'] = $rq->getHttpHost();
        $msg['className'] = get_class($ca);
        $this->log($msg);
        return $this;
    }


    private function MsgAdd($val)
    {

        switch (gettype($val)) {
            case 'boolean':
                return $val;
                break;
            case 'integer':
                return $val;
                break;

            case 'double':
                return $val;
                break;

            case 'string':
                return $val;
                break;

            case 'array':
                $data = [];
                foreach ($data as $key => $value) {
                    $data[$key] = $value;
                }
                return json_encode($data);
                break;

            case 'object':
                $data = [];
                if ($val instanceof DataObject) {
                    foreach ($val->toArray() as $key => $value) {
                        $this->log($this->MsgAdd($value));
                    }
                } else {
                    $obj = new DataObject();
                    foreach (($obj->addData(get_class_methods($val)))->toArray() as $key => $value) {
                        $data[$key] = $value;
                    }
                }
                return json_encode($data);
                break;
            case 'resource':
                return get_resource_type($value);
                break;
            case 'NULL':
                return $this;
                break;
            default:
                return $this;
                break;
        }
    }

    private function AddBasic($product, $fields)
    {
        foreach ($fields as $field) {
            $msg[$field] = $product->getData($field);
        }
        return $msg;
    }

}
