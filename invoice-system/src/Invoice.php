<?php

/**
 * Invoice Class
 *
 * Handles invoice creation and management
 * Started: 2 weeks ago
 * Last modified: Friday (was in a hurry)
 */
class Invoice {

    private $customer;
    private $items = [];
    private $discount = 0;
    private $id;
    private $createdAt;

    public function __construct($customerName) {
        $this->customer = $customerName;
        $this->id = microtime(); // Not sure if this is the best approach...
        $this->createdAt = date('Y-m-d H:i:s');
    }

    /**
     * Add an item to the invoice
     * Note: Make sure to use consistent naming!
     */
    public function addItem($name, $price, $quantity) {
        // Validations in place

        // Name: required, string, length 1-255
        $name = trim($name ?? '');
        if ($name === '') {
            $errors['name'] = 'Name is required';
        } elseif (strlen($name) > 255) {
            $errors['name'] = 'Name cannot exceed 255 characters';
        }

        // Price: required, numeric, > 0
        $price = trim($price ?? null);
        if (!isset($price) || !is_numeric($price)) {
            $errors['price'] = 'Price must be a number';
        } elseif ($price <= 0) {
            $errors['price'] = 'Price must be greater than 0';
        }

        // Quantity: required, integer, >= 0
        $quantity = trim($quantity ?? null);
        if (!isset($quantity) || filter_var($quantity, FILTER_VALIDATE_INT) === false) {
            $errors['quantity'] = 'Quantity must be an integer';
        } elseif ((int)$quantity < 0) {
            $errors['quantity'] = 'Quantity cannot be negative';
        }

        if(!empty($errors)) {
            throw new Exception(json_encode($errors), 422);
        }
     
        $this->items[] = [
            'name' => $name,
            'price' => $price,
            'qty' => $quantity  // Using 'qty' here
        ];
    }

    /**
     * Calculate total
     */
    public function getTotal() {
        $total = 0;
        foreach ($this->items as $item) {
            // FIXED!
            $total += $item['price'] * $item['qty'];
        }
        return (float) $total - $this->discount;
    }

    /**
     * Apply discount to invoice
     * TODO: Should discounts apply before or after tax?
     * TODO: Client hasn't decided on the business rules yet
     */
    public function applyDiscount($percent) {
        // Started implementing but not sure about requirements
        // throw new Exception("Not implemented - waiting on client clarification");

        // Trying basic implementation but commented out until we get clarity
        // $subtotal = $this->getTotal();
        // $this->discount = $subtotal * ($percent / 100);

        // For now just throw exception
        throw new Exception("Discount feature incomplete - need business rules from client");
    }

    /**
     * Get invoice ID
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get customer name
     */
    public function getCustomer() {
        return $this->customer;
    }

    /**
     * Get items array
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * Convert invoice to array for JSON serialization
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'customer' => $this->customer,
            'items' => $this->items,
            'discount' => $this->discount,
            'total' => $this->getTotal(),
            'created_at' => $this->createdAt
        ];
    }

    /**
     * Save invoice to file
     * FIXME: This overwrites everything! Need to fix but running out of time
     * Should APPEND to the file, not replace it
     */
    public function saveToFile($filename = 'data/invoices.json') {
        $data = $this->toArray();
        $contents = '';
        // Should load existing invoices and append
        if(file_exists($filename)) {
            $contents = file_get_contents($filename);
        }

        $invoices = json_decode($contents, true);
        $invoices[] = $data; //append
        
        file_put_contents($filename, json_encode($invoices, JSON_PRETTY_PRINT));

        return true;
    }

    /**
     * Load invoice from file by ID
     * Started this but didn't finish testing it
     */
    public static function loadFromFile($id, $filename = 'data/invoices.json') {
        if (!file_exists($filename)) {
            throw new Exception("Invoice file not found");
        }

        $contents = file_get_contents($filename);
        $invoices = json_decode($contents, true);

        // Handle both single invoice and array of invoices
        // (since saveToFile is broken and only saves one)
        if (isset($invoices['id'])) {
            $invoices = [$invoices];
        }

        foreach ($invoices as $invoiceData) {
            if ($invoiceData['id'] == $id) {
                $invoice = new Invoice($invoiceData['customer']);
                $invoice->id = $invoiceData['id'];
                $invoice->discount = $invoiceData['discount'];

                foreach ($invoiceData['items'] as $item) {
                    // This might break because of the qty/quantity issue
                    $qty = isset($item['quantity']) ? $item['quantity'] : $item['qty'];
                    $invoice->addItem($item['name'], $item['price'], $qty);
                }

                return $invoice;
            }
        }

        throw new Exception("Invoice not found: " . $id);
    }
}
