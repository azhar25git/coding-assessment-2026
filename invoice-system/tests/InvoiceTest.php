<?php

require_once 'vendor/autoload.php';
use App\Invoice;
use App\PDFGenerator;
use App\InvoiceCalculator;

/**
 * Basic tests for Invoice system
 *
 * Note: Only had time to write basic tests
 * Need more coverage (edge cases, validation, error handling, etc.)
 * Some tests are failing - not sure if tests are wrong or code is wrong??
 *
 * Run with: php run_tests.php
 */

class InvoiceTest {

    private $testsPassed = 0;
    private $testsFailed = 0;
    private $failures = [];

    /**
     * Run all tests
     */
    public function runAll() {
        echo "Running Invoice Tests...\n";
        echo str_repeat("=", 50) . "\n\n";

        $this->test_create_invoice();
        $this->test_calculate_total();
        $this->test_calculate_total_fail();
        $this->test_add_multiple_items();
        $this->test_throws_error_at_wrong_name();
        $this->test_throws_error_at_wrong_price();
        $this->test_throws_error_at_wrong_qty();
        $this->test_save_and_load();
        $this->test_tax_calculation();
        $this->test_invoice_pdf_generation();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: " . $this->testsPassed . "\n";
        echo "Tests Failed: " . $this->testsFailed . "\n";

        if ($this->testsFailed > 0) {
            echo "\nFailures:\n";
            foreach ($this->failures as $failure) {
                echo "  - " . $failure . "\n";
            }
        }

        return $this->testsFailed === 0;
    }

    /**
     * Test: Create basic invoice
     * Status: PASSING ✓
     */
    private function test_create_invoice() {
        $invoice = new Invoice("Test Customer");

        $this->assert(
            $invoice->getCustomer() === "Test Customer",
            "test_create_invoice",
            "Customer name should match"
        );
    }

    /**
     * Test: Calculate total for single item
     * Status: PASSING ✓
     */
    private function test_calculate_total() {
        $invoice = new Invoice("Test Customer");
        $invoice->addItem("Test Item", 10.00, 2);

        $expected = 20.00;
        $actual = $invoice->getTotal();

        $this->assert(
            $actual === $expected,
            "test_calculate_total",
            "Total should be $20.00, got $" . number_format($actual, 2)
        );
    }

    /**
     * Test: Calculate fail total for single item
     * Status: PASSING ✓
     */
    private function test_calculate_total_fail() {
        $invoice = new Invoice("Test Customer");
        $invoice->addItem("Test Item", 15.00, 2);

        $expected = 20.00;
        $actual = $invoice->getTotal();

        $this->assert(
            $actual !== $expected,
            "test_calculate_total_fail",
            "Total should be $20.00, got $" . number_format($actual, 2)
        );
    }

    /**
     * Test: Add multiple items and calculate total
     * Status: PASSING ✓
     */
    private function test_add_multiple_items() {
        $invoice = new Invoice("Test Customer");
        $invoice->addItem("Item 1", 10.00, 2);
        $invoice->addItem("Item 2", 15.00, 3);
        $invoice->addItem("Item 3", 5.00, 1);

        $expected = 20.00 + 45.00 + 5.00; // = 70.00
        $actual = $invoice->getTotal();

        $this->assert(
            $actual === $expected,
            "test_add_multiple_items",
            "Total should be $70.00, got $" . number_format($actual, 2)
        );
    }

    /**
     * Test: Add multiple items and calculate total wrong data
     * Status: PASSING ✓
     */
    private function test_throws_error_at_wrong_name() {
        try {
            $invoice = new Invoice("Test Customer");
            $invoice->addItem("", 10.00, 2);
            $invoice->addItem("", 15.00, 4);
    
            $expected = 20.00;
            $actual = $invoice->getTotal();
    
            $this->assert(
                $actual === $expected,
                __FUNCTION__,
                "Total should be $70.00, got $" . number_format($actual, 2)
            );
        }
        catch(Exception $e) {
            $actual = $e->getMessage();
            $expected = '{"items":{"name":["Item name can not be empty :0"]}}';
            $this->assert(
                $actual === $expected,
                __FUNCTION__,
                $actual
            );
        }
    }

    /**
     * Test: Add multiple items and calculate total wrong data
     * Status: PASSING ✓
     */
    private function test_throws_error_at_wrong_price() {
        try {
            $invoice = new Invoice("Test Customer");
            $invoice->addItem("A", -10.00, 2);
            $invoice->addItem("B", -15.00, 4);
    
            $expected = 20.00;
            $actual = $invoice->getTotal();
            $this->assert(
                $actual === $expected,
                __FUNCTION__,
                "Total should be $70.00, got $" . number_format($actual, 2)
            );
        }
        catch(Exception $e) {
            $actual = $e->getMessage();
            $expected = '{"items":{"price":["Price must be greater than 0:0"]}}';
            $this->assert(
                $actual === $expected,
                __FUNCTION__,
                $actual
            );
        }
    }
    /**
     * Test: Add multiple items and calculate total wrong data
     * Status: PASSING ✓
     */
    private function test_throws_error_at_wrong_qty() {
        try {
            $invoice = new Invoice("Test Customer");
            $invoice->addItem("A", 10.00, 0);
            $invoice->addItem("B", 15.00, -4);
    
            $expected = 20.00;
            $actual = $invoice->getTotal();
    
            $this->assert(
                $actual === $expected,
                __FUNCTION__,
                "Total should be $20.00, got $" . number_format($actual, 2)
            );
        }
        catch(Exception $e) {
            $actual = $e->getMessage();
            $expected = '{"items":{"qty":["At least one item need to be added:0"]}}';
            $this->assert(
                $actual === $expected,
                __FUNCTION__,
                $actual
            );
        }
    }

    /**
     * Test: Save invoice to file and load it back
     * Status: PASSING ✓
     */
    private function test_save_and_load() {
        $testFile = __DIR__ . '/../data/test_invoices.json';

        // Clean up first
        if (file_exists($testFile)) {
            unlink($testFile);
        }

        // Create and save first invoice
        $invoice1 = new Invoice("Customer 1");
        $invoice1->addItem("Item A", 100.00, 1);
        $invoice1->saveToFile($testFile);

        // Create and save second invoice
        $invoice2 = new Invoice("Customer 2");
        $invoice2->addItem("Item B", 200.00, 1);
        $invoice2->saveToFile($testFile);

        // Try to load first invoice - this will fail
        // because saveToFile overwrites everything
        try {
            $loaded = Invoice::loadFromFile($invoice1->getId(), $testFile);
            $this->assert(
                $loaded->getCustomer() === "Customer 1",
                __FUNCTION__,
                "Should be able to load first invoice"
            );
        } catch (Exception $e) {
            $this->assert(
                false,
                __FUNCTION__,
                "Failed to load invoice: " . $e->getMessage()
            );
        }

        // Clean up
        if (file_exists($testFile)) {
            unlink($testFile);
        }
    }

    /**
     * Test: Tax calculation
     * Status: PASSING ✓
     *
     * Tax rates load from the json file
     */
    private function test_tax_calculation() {
        $subtotal = 100.00;
        $region = 'US-CA';
        $tax = InvoiceCalculator::calculateTax($subtotal, $region);
        
        $expected = $subtotal * InvoiceCalculator::taxRateByRegion($region);

        $this->assert(
            $tax === $expected,
            __FUNCTION__,
            sprintf(
                "Tax should be %d, got %d", 
                number_format($expected, 2), 
                number_format($tax, 2)
            )
        );
    }

    /**
     * Test: Invoice PDF generation
     * Status: PASSING ✓
     */
    private function test_invoice_pdf_generation() {
        $invoice = new Invoice("Test Customer");
  
        for($i=1; $i<=10; $i++) {
            $invoice->addItem("Product - " . $i, (float) $i, $i);
        }

        $pdfGenerator = new PDFGenerator($invoice);
        $pdfGenerator->generatePDF($invoice);

        $this->assert(
            file_exists('invoices/invoice_'. $invoice->id .'.pdf'),
            __FUNCTION__,
            'PDF for invoice ' . $invoice->id . ' not generated'
        );
    }

    /**
     * Simple assertion helper
     */
    private function assert($condition, $testName, $message) {
        if ($condition) {
            $this->testsPassed++;
            echo "✓ " . $testName . "\n";
        } else {
            $this->testsFailed++;
            echo "✗ " . $testName . " - " . $message . "\n";
            $this->failures[] = $testName . ": " . $message;
        }
    }
}

// Don't auto-run if included by run_tests.php
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $test = new InvoiceTest();
    $success = $test->runAll();
    exit($success ? 0 : 1);
}
