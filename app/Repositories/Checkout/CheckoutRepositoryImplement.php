<?php

namespace App\Repositories\Checkout;

use App\Enums\OrderStatus;
use App\Exceptions\CheckoutException;
use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Order;
use App\Models\ShippingDetail;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Services\Cart\CartService;
use App\Services\Customer\CustomerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CheckoutRepositoryImplement extends Eloquent implements CheckoutRepository
{

    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     * @property Model|mixed $model;
     */
    protected $model;
    protected $cartService;
    protected $shippingDetail;
    protected $orderDetail;
    protected $customerService;
    protected $productModel;

    public function __construct(Order $model, CartService $cartService, ShippingDetail $shippingDetail, OrderDetail $orderDetail, CustomerService $customerService, Product $productModel)
    {
        $this->model = $model;
        $this->cartService = $cartService;
        $this->shippingDetail = $shippingDetail;
        $this->orderDetail = $orderDetail;
        $this->customerService = $customerService;
        $this->productModel = $productModel;
    }

    /**
     * Store the order and its details.
     * @param array $data Checkout data
     * @return array Result of the checkout operation
     */
    public function storeCheckout($data)
    {
        try {
            // Update customer data
            $this->customerService->updateCustomer($data['customer_id'], $data);
            // Store the order and its details
            $order = $this->createOrder($data['customer_id'], $data);
            $this->createShippingDetail($order, $data);
            $this->processCartItems($order);
            return [
                'order_uid' => $order->order_uid,
                'success' => true
            ];
        } catch (\Exception $e) {
            // Log the exception message for debugging
            Log::error('Failed to checkout: ' . $e->getMessage());

            // Throwing the custom exception
            throw new CheckoutException('Terjadi kesalahan saat proses checkout. Silakan coba lagi.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new order instance.
     * @param int $customer_id The ID of the customer making the order.
     * @param array $data The data for creating the order.
     * @return \App\Models\Order
     */
    private function createOrder($customer_id, $data)
    {
        if(strtoupper($data['paymentMethod']) == "COD"){
            $orderStatus = OrderStatus::PAYMENT_VERIFICATION;
        }else{
            $orderStatus = OrderStatus::PENDING_PAYMENT;
        }
        // Create a new order instance
        $order = $this->model->create([
            'customer_id' => $customer_id,
            'order_date' => date('Y-m-d H:i:s'),
            'order_status' => $orderStatus,
            'order_type' => $data['paymentMethod'],
            'total_price' => $data['total'],
            'receiver_name' => $data['name'],
            'shipping_address' => $data['address'],
            'shipping_city' => $data['city_name'],
            'shipping_province' => $data['province_name'],
            'shipping_district' => $data['district_name'],
            'shipping_postal_code' => $data['postal_code'],
            'receiver_phone' => $data['phone'],
            'order_number' => $this->generateOrderNumber()
        ]);
        return $order;
    }

    /**
     * Generate a unique order number.
     * @return string
     */
    private function generateOrderNumber()
    {
        $datePrefix = date('ymd'); // Will generate something like 230617
        $lastOrderToday = $this->model
            ->whereDate('created_at', Carbon::today())
            ->orderBy('created_at', 'desc')
            ->first();
        if ($lastOrderToday) {
            $lastOrderNumber = $lastOrderToday->order_number;
            $lastOrderSequenceNumber = intval(substr($lastOrderNumber, -6)); // Get the last 6 digits of order number

            $nextSequenceNumber = $lastOrderSequenceNumber + 1;
        } else {
            $nextSequenceNumber = 1;
        }

        // Padding the sequence number with leading zeros, to get it 6 digits long.
        $nextSequenceNumber = str_pad($nextSequenceNumber, 6, '0', STR_PAD_LEFT);
        return "ORD-" . $datePrefix . $nextSequenceNumber; // Will generate something like ORD-230617000020
    }

    /**
     * Create a new shipping detail instance.
     * @param Order $order The order instance that the shipping detail is associated with.
     * @param array $data The data for creating the shipping detail.
     * @return \App\Models\ShippingDetail
     * @throws \Exception
     */
    private function createShippingDetail(Order $order, $data)
    {
        try {
            return $this->shippingDetail->create([
                'order_id' => $order->order_id,
                'expedition' => $data['expedition'],
                'parcel' => $data['parcel'],
                'delivery_cost' => $data['deliveryCost'],
                'weight' => $data['weight']
            ]);
        } catch (\Exception $e) {
            // Log the exception message for debugging
            Log::error('Failed to create shipping detail: ' . $e->getMessage());

            // Rethrow the exception to be handled by the parent method
            throw $e;
        }
    }

    /**
     * Process all items in the customer's cart.
     * @param Order $order The order instance that the cart items are associated with.
     * @return void
     */
    private function processCartItems(Order $order)
    {
        // Retrieve all items in the customer's cart
        $cartItems = $this->getCustomerCartData();
        $subTotal = 0;
        // Loop through each cart item
        foreach ($cartItems as $cart) {
            $totalPerPrice = $cart->quantity * ($cart->product->price - $cart->product->discount);
            $subTotal += $totalPerPrice;
            $cart->subTotal = $totalPerPrice;
            // Create an order detail for the cart item
            $this->createOrderDetail($order, $cart);

            // Remove the cart item
            $this->removeFromCart($cart);
        }
    }

    /**
     * Fetch customer cart data.
     * @return mixed
     */
    private function getCustomerCartData()
    {
        return $this->cartService->getAllDataByCustomer(Auth::guard('customer')->user()->id);
    }

    /**
     * Create a new order detail instance.
     * Also reduce the product stock accordingly.
     * @param Order $order The order instance that the order detail is associated with.
     * @param $cart The cart item for creating the order detail.
     * @return void
     * @throws \Exception
     */
    private function createOrderDetail(Order $order, $cart)
    {
        try {
            // Reduce the product stock first
            $this->reduceProductStock($cart->product_id, $cart->quantity);

            // Create order detail
            return $this->orderDetail->create([
                'order_id' => $order->order_id,
                'product_id' => $cart->product_id,
                'price' => $cart->subTotal,
                'quantity' => $cart->quantity
            ]);
        } catch (\Exception $e) {
            // Log the exception message for debugging
            Log::error('Failed to create order detail: ' . $e->getMessage());

            // Rethrow the exception to be handled by the parent method
            throw $e;
        }
    }

    /**
     * Reduce the product stock by a certain amount.
     * @param int $productId The ID of the product.
     * @param int $quantity The quantity to reduce.
     * @return void
     * @throws \Exception
     */
    private function reduceProductStock($productId, $quantity)
    {
        $product = $this->productModel->find($productId);
        if ($product) {
            if ($product->stock < $quantity) {
                // If the product stock is not sufficient, throw an exception
                throw new \Exception("Stok produk tidak cukup");
            }

            // Reduce the stock
            $product->stock -= $quantity;
            $product->save();
        } else {
            // If the product is not found, throw an exception
            throw new \Exception("Produk tidak ditemukan");
        }
    }


    /**
     * Remove an item from the cart.
     * @param $cart The cart item to remove.
     * @return void
     */
    private function removeFromCart($cart)
    {
        return $cart->delete();
    }
}
