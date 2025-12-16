<?php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Users (at least 6)
        $users = [
            ['name' => 'Admin', 'email' => 'admin@gmail.com', 'role' => 'owner'],
            ['name' => 'Helper', 'email' => 'helper@aquastar.com', 'role' => 'helper'],
            ['name' => 'Delivery', 'email' => 'delivery@aquastar.com', 'role' => 'delivery'],
        ];

        $createdUsers = collect($users)->map(function ($user) {
            return User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make('password'),
                'role' => $user['role'],
            ]);
        });

        $owners = $createdUsers->where('role', 'owner');
        $deliveryPersons = $createdUsers->where('role', 'delivery');
        $helpers = $createdUsers->where('role', 'helper');

        // Customers (at least 6)
        $customerData = [
            ['name' => 'John Doe', 'phone' => '09171234567', 'email' => 'john.doe@email.com', 'house_number' => '123', 'street' => 'Main Street', 'barangay' => 'Barangay 1', 'city' => 'Manila', 'province' => 'Metro Manila', 'postal_code' => '1000', 'is_regular' => true],
            ['name' => 'Jane Smith', 'phone' => '09172345678', 'email' => 'jane.smith@email.com', 'house_number' => '456', 'street' => 'Oak Avenue', 'barangay' => 'Barangay 2', 'city' => 'Quezon City', 'province' => 'Metro Manila', 'postal_code' => '1100', 'is_regular' => true],
            ['name' => 'Michael Johnson', 'phone' => '09173456789', 'email' => 'michael.johnson@email.com', 'house_number' => '789', 'street' => 'Pine Road', 'barangay' => 'Barangay 3', 'city' => 'Makati', 'province' => 'Metro Manila', 'postal_code' => '1200', 'is_regular' => false],
            ['name' => 'Sarah Williams', 'phone' => '09174567890', 'email' => 'sarah.williams@email.com', 'house_number' => '321', 'street' => 'Elm Street', 'barangay' => 'Barangay 4', 'city' => 'Pasig', 'province' => 'Metro Manila', 'postal_code' => '1600', 'is_regular' => true],
            ['name' => 'Robert Brown', 'phone' => '09175678901', 'email' => 'robert.brown@email.com', 'house_number' => '654', 'street' => 'Maple Drive', 'barangay' => 'Barangay 5', 'city' => 'Taguig', 'province' => 'Metro Manila', 'postal_code' => '1630', 'is_regular' => false],
            ['name' => 'Emily Davis', 'phone' => '09176789012', 'email' => 'emily.davis@email.com', 'house_number' => '987', 'street' => 'Cedar Lane', 'barangay' => 'Barangay 6', 'city' => 'Mandaluyong', 'province' => 'Metro Manila', 'postal_code' => '1550', 'is_regular' => true],
            ['name' => 'David Miller', 'phone' => '09177890123', 'email' => 'david.miller@email.com', 'house_number' => '147', 'street' => 'Birch Way', 'barangay' => 'Barangay 7', 'city' => 'San Juan', 'province' => 'Metro Manila', 'postal_code' => '1500', 'is_regular' => false],
            ['name' => 'Lisa Wilson', 'phone' => '09178901234', 'email' => 'lisa.wilson@email.com', 'house_number' => '258', 'street' => 'Spruce Court', 'barangay' => 'Barangay 8', 'city' => 'Marikina', 'province' => 'Metro Manila', 'postal_code' => '1800', 'is_regular' => true],
        ];

        $customers = collect($customerData)->map(function ($data) {
            return Customer::create($data);
        });

        // Orders (at least 6)
        $waterTypes = ['alkaline', 'purified', 'mineral'];
        $orderStatuses = ['pending', 'completed', 'completed', 'completed', 'completed']; // More completed than pending
        $paymentStatuses = ['paid', 'paid', 'paid', 'unpaid', 'paid']; // More paid than unpaid
        $paymentMethods = ['cash', 'gcash', 'cash', 'gcash', 'cash'];
        
        $baseDate = Carbon::now()->subDays(10);
        $orders = collect(range(0, 7))->map(function ($index) use ($customers, $owners, $deliveryPersons, $waterTypes, $orderStatuses, $paymentStatuses, $paymentMethods, $baseDate) {
            $customer = $customers[$index];
            $owner = $owners->random();
            $isDelivery = $index % 2 === 0;
            $quantity = rand(1, 5);
            $waterType = $waterTypes[array_rand($waterTypes)];
            $waterPrice = $waterType === 'alkaline' ? 35.00 : ($waterType === 'mineral' ? 30.00 : 25.00);
            $deliveryFee = $isDelivery ? 5.00 : 0;
            $total = ($quantity * $waterPrice) + ($quantity * $deliveryFee);
            $orderStatus = $orderStatuses[$index % count($orderStatuses)];
            $paymentStatus = $paymentStatuses[$index % count($paymentStatuses)];
            $paymentMethod = $paymentMethods[$index % count($paymentMethods)];
            $deliveryPerson = $isDelivery ? $deliveryPersons->random() : null;
            
            return Order::create([
                'customer_id' => $customer->id,
                'user_id' => $owner->id,
                'email' => $customer->email,
                'quantity' => $quantity,
                'water_type' => $waterType,
                'water_price' => $waterPrice,
                'is_delivery' => $isDelivery,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $total,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentMethod === 'gcash' ? 'GCASH-' . strtoupper(Str::random(8)) : null,
                'order_status' => $orderStatus,
                'delivery_user_id' => $deliveryPerson?->id,
                'delivery_date' => $isDelivery ? $baseDate->copy()->addDays($index)->addHours(rand(9, 17)) : null,
                'notes' => $isDelivery ? 'Delivery requested' : 'Pickup order',
                'replace_gallon' => rand(0, 1) === 1,
                'replace_caps' => rand(0, 1) === 1,
                'replacement_cost' => rand(0, 1) === 1 ? rand(50, 200) : 0,
            ]);
        });

        // Payments (at least 6)
        $paymentMethodsForPayments = ['cash', 'gcash', 'bank_transfer', 'cash', 'gcash', 'cash'];
        $orders->take(8)->each(function ($order, $index) use ($owners, $paymentMethodsForPayments) {
            // Some orders have full payment, some have partial, some have multiple payments
            if ($order->payment_status === 'paid') {
                // Full payment
                Payment::create([
                    'order_id' => $order->id,
                    'user_id' => $owners->random()->id,
                    'amount' => $order->total_amount,
                    'payment_method' => $paymentMethodsForPayments[$index % count($paymentMethodsForPayments)],
                    'payment_reference' => $paymentMethodsForPayments[$index % count($paymentMethodsForPayments)] === 'gcash' ? 'GCASH-' . strtoupper(Str::random(8)) : null,
                    'payment_date' => $order->created_at->addHours(rand(1, 24)),
                    'notes' => 'Full payment received',
                ]);
            } else {
                // Partial payment or multiple payments
                $partialAmount = $order->total_amount * 0.5;
                Payment::create([
                    'order_id' => $order->id,
                    'user_id' => $owners->random()->id,
                    'amount' => $partialAmount,
                    'payment_method' => $paymentMethodsForPayments[$index % count($paymentMethodsForPayments)],
                    'payment_reference' => $paymentMethodsForPayments[$index % count($paymentMethodsForPayments)] === 'gcash' ? 'GCASH-' . strtoupper(Str::random(8)) : null,
                    'payment_date' => $order->created_at->addHours(rand(1, 24)),
                    'notes' => 'Partial payment',
                ]);
            }
        });
    }
}