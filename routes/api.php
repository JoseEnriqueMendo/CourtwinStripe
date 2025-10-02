<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\SetupIntent;
use Stripe\PaymentMethod; // Required to manage Payment Methods

// Group all Stripe-related routes under /api/v1/stripe
Route::prefix('v1/stripe')->group(function () {

    /**
     * Create a Stripe Customer
     *
     * Endpoint: POST /api/v1/stripe/create-customer
     *
     * Request body parameters:
     *  - email (string, required): Customer email
     *  - name (string, required): Customer name
     *
     * Response:
     *  - JSON object with full Stripe customer information
     */
    Route::post('/create-customer', function (Request $request) {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Validate request
        $validated = $request->validate([
            'email' => 'required|email',
            'name'  => 'required|string|max:255',
        ]);

        // Create customer in Stripe
        $customer = Customer::create([
            'email' => $validated['email'],
            'name'  => $validated['name'],
        ]);

        return response()->json($customer);
    });

    /**
     * Create a SetupIntent to save a card
     *
     * Endpoint: POST /api/v1/stripe/setup-intent
     *
     * Request body parameters:
     *  - customer_id (string, required): Stripe customer ID
     *
     * Response:
     *  - JSON object with the SetupIntent client_secret
     */
    Route::post('/setup-intent', function (Request $request) {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $customerId = $request->input('customer_id');

        $intent = SetupIntent::create([
            'customer' => $customerId,
        ]);

        return response()->json([
            'client_secret' => $intent->client_secret
        ]);
    });

    /**
     * List all cards for a given Stripe Customer
     *
     * Endpoint: GET /api/v1/stripe/cards
     *
     * Query parameters:
     *  - customer_id (string, required): Stripe customer ID
     *
     * Response:
     *  - JSON object containing:
     *      - customer_id: the requested customer ID
     *      - count: number of cards found
     *      - cards: array of simplified card objects
     */
    Route::get('/cards', function (Request $request) {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Validate that customer_id is provided
        $validated = $request->validate([
            'customer_id' => 'required|string',
        ]);

        $customerId = $validated['customer_id'];

        try {
            // List PaymentMethods of type 'card' for this customer
            $paymentMethods = PaymentMethod::all([
                'customer' => $customerId,
                'type' => 'card',
                'limit' => 100,
            ]);

            // Simplify the structure for the frontend
            $cards = collect($paymentMethods->data)->map(function ($pm) {
                return [
                    'id' => $pm->id,
                    'brand' => $pm->card->brand ?? null,
                    'last4' => $pm->card->last4 ?? null,
                    'exp_month' => $pm->card->exp_month ?? null,
                    'exp_year' => $pm->card->exp_year ?? null,
                    'funding' => $pm->card->funding ?? null,
                    'billing_name' => $pm->billing_details->name ?? null,
                    'billing_email' => $pm->billing_details->email ?? null,
                ];
            });

            return response()->json([
                'customer_id' => $customerId,
                'count' => $cards->count(),
                'cards' => $cards->values(),
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Stripe-specific API error
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            // General error
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    });

Route::delete('/cards/{paymentMethodId}', function ($paymentMethodId) {
    $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

    try {
        // Detach the payment method using StripeClient
        $deleted = $stripe->paymentMethods->detach($paymentMethodId, []);

        return response()->json([
            'success' => true,
            'message' => 'Card deleted successfully',
            'id' => $paymentMethodId,
            'raw' => $deleted,
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
            'type' => get_class($e),
        ], 500);
    } catch (\Exception $e) {
        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
            'type' => get_class($e),
        ], 500);
    }
});




Route::post('/charge', function (Request $request) {
    $request->validate([
        'customer_id' => 'required|string',
        'payment_method_id' => 'required|string',
        'amount' => 'required|integer|min:1', // amount in cents
        'currency' => 'sometimes|string|in:usd,eur,pen' // adjust as needed
    ]);

    $customerId = $request->input('customer_id');
    $paymentMethodId = $request->input('payment_method_id');
    $amount = $request->input('amount'); // cents
    $currency = $request->input('currency', 'usd');

    $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

    try {
        // Create & confirm PaymentIntent using the saved payment method
        $pi = $stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $customerId,
            'payment_method' => $paymentMethodId,
            'off_session' => true,      // charge without interaction
            'confirm' => true,          // try to confirm immediately
            'confirmation_method' => 'automatic',
        ]);

        // Return the whole PaymentIntent so frontend can inspect status
        return response()->json($pi);
    } catch (\Stripe\Exception\CardException $e) {
        // Card declined / requires action / authentication errors
        return response()->json([
            'error' => true,
            'type' => 'card_error',
            'message' => $e->getError()->message ?? $e->getMessage(),
            'stripe_code' => $e->getError()->code ?? null,
        ], 402);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        return response()->json([
            'error' => true,
            'type' => 'api_error',
            'message' => $e->getMessage(),
        ], 500);
    } catch (\Exception $e) {
        return response()->json([
            'error' => true,
            'type' => 'server_error',
            'message' => $e->getMessage(),
        ], 500);
    }
});

});
