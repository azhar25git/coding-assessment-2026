<?php

namespace App;
/**
 * InvoiceCalculator - Helper class for invoice calculations
 *
 * Static utility methods for business logic
 * Client keeps changing their mind on requirements...
 */
class InvoiceCalculator {

    /**
     * Calculate tax for an invoice
     *
     * TODO: Load tax rates from data/tax_rates.json instead of hardcoding
     * Currently just using 10% for everything which is WRONG
     *
     * @param float $subtotal The subtotal before tax
     * @param string $region Region code (e.g., "US-CA", "CA-ON")
     * @return float Tax amount
     */
    public static function calculateTax($subtotal, $region = 'US-CA') {

        // subtotal: required, numeric, > 0
        $subtotal = trim($subtotal ?? null);
        if (!isset($subtotal) || !is_numeric($subtotal)) {
            $errors['subtotal'] = 'Subtotal must be a number';
        } elseif ($subtotal < 0) {
            $errors['subtotal'] = 'Subtotal must be positive number';
        }

        $region = trim($region ?? 'US-CA'); // Default 'US-CA'
        if (!isset($region) || !is_string($region) || strlen($region) < 2) {
            $errors['region'] = 'Subtotal must be a valid string';
        } elseif ($region < 0) {
            $errors['region'] = 'Subtotal must be positive number';
        }

        if(!empty($errors)) {
            throw new \Exception(json_encode($errors), 422);
        }

        return $subtotal * self::taxRateByRegion($region);
    }

    /**
     * Apply business rules to an invoice
     *
     * Rules from client (received via email last Thursday):
     * 1. Orders over $1000 get automatic 5% discount
     * 2. BUT discount should NOT apply to items marked as "sale" items
     * 3. How do we even track which items are on sale??
     * 4. Does the $1000 include tax or not?? (Waiting for response)
     *
     * Client keeps changing their mind on this feature
     * Started implementation 3 times, gave up
     *
     * @param Invoice $invoice
     * @return Invoice Modified invoice
     */
    public static function applyBusinessRules($invoice) {
        // Need to figure out requirements first

        // Pseudo-code for what they MIGHT want:
        // if (invoice total > 1000 && !has_sale_items) {
        //     apply 5% discount
        // }

        // Problems:
        // 1. How to identify sale items? Add a flag to item array?
        // 2. Does discount apply before or after tax?
        // 3. Can discounts stack with other discounts?
        // 4. What if they return items - does discount get recalculated?

        // For now, just return the invoice unchanged
        // Need meeting with client to clarify

        return $invoice;
    }

    /**
     * Calculate line item total
     * This one actually works correctly!
     *
     * @param array $item Item with price and qty
     * @return float Line item total
     */
    public static function calculateLineItem($item) {
        $price = $item['price'];

        $qty = isset($item['qty']) ? $item['qty'] : $item['qty'];

        return $price * $qty;
    }

    /**
     * Format currency for display
     * Quick helper I added
     *
     * @param float $amount
     * @return string Formatted currency
     */
    public static function formatCurrency($amount) {
        return '$' . number_format($amount, 2);
    }

    /**
     * Validate invoice data
     * Started but didn't finish
     *
     * Should check:
     * - No negative prices
     * - No negative quantities
     * - Customer name not empty
     * - At least one item
     * - etc.
     */
    public static function validateInvoice(Invoice $invoice) {
        $errors = [];
        // TODO: Add actual validation logic
        // Name: required, string, length 1-255

        if(count($invoice->items) <= 0) {
            $errors['items'] = 'At least one item need to be added to invoice';
        }
        $customer = trim($invoice->customer ?? '');
        if ($customer === '') {
            $errors['customer'] = 'Customer name can not be empty: ' . $invoice->id;
        } elseif (strlen($customer) > 255) {
            $errors['customer'] = 'Customer Name cannot exceed 255 characters: ' . $invoice->id;
        }
       
        foreach($invoice->items as $key => $items) {
            $name = trim($items['name'] ?? '');
            if ($name === '') {
                $errors['items']['name'][$key] = 'Item name can not be empty :' . $key;
                continue;
            } elseif (strlen($name) > 255) {
                $errors['items']['name'][$key] = 'Item Name cannot exceed 255 characters:' . $key;
                continue;
            }
            // Price: required, numeric, > 0
            $price = trim($items['price'] ?? null);
            if (!isset($price) || !is_numeric($price)) {
                $errors['items']['price'][$key] = 'Price must be a number:' . $key;
                continue;
            } elseif ($price <= 0) {
                $errors['items']['price'][$key] = 'Price must be greater than 0:' . $key;
                continue;
            }
    
            // Quantity: required, integer, >= 0
            $qty = trim($items['qty'] ?? null);
            if (!isset($qty) || filter_var($qty, FILTER_VALIDATE_INT) === false) {
                $errors['items']['qty'][$key] = 'Quantity must be an integer:' . $key;
                continue;
            } elseif ((int)$qty < 1) {
                $errors['items']['qty'][$key] = 'At least one item need to be added:' . $key;
                continue;
            }

        }

        if(!empty($errors)) {
            throw new \Exception(json_encode($errors), 422);
        }

        return true;
    }

    public static function taxRateByRegion(string $region): float
    {
        static $rates;
    
        if ($rates === null) {
            $rates = json_decode(
                file_get_contents(__DIR__ . '/../data/tax_rates.json'),
                true,
                flags: JSON_THROW_ON_ERROR
            );
        }
    
        [$country, $state] = array_pad(
            explode('-', strtoupper(trim($region)), 2),
            2,
            null
        );
    
        if (!isset($rates[$country])) {
            throw new \InvalidArgumentException("Unknown country: $country");
        }
    
        return $rates[$country][$state]
            ?? $rates[$country]['default']
            ?? throw new \RuntimeException("No default rate for $country");
    }
}
