<?php

namespace App;

use App\Invoice;
use Dompdf\Dompdf;
use App\InvoiceCalculator;

/**
 * PDFGenerator - Generate PDF invoices
 *
 * Status: IMPLEMENTED
 *
 * UPDATE (Monday morning): Policy changed - Composer packages are now APPROVED!
 * You may use any PDF library: FPDF, TCPDF, Dompdf, or others.
 *
 * Previous blocker (resolved):
 * - Was blocked on "no external libraries" policy
 * - Policy has been updated - external libraries now allowed
 * - Can proceed with implementation using Composer packages
 */
class PDFGenerator {

    /**
     * Generate PDF from invoice
     *
     * UPDATE (Monday): Composer packages are now APPROVED!
     *
     * Suggested approaches:
     * - FPDF: Lightweight, simple API
     * - TCPDF: More features, HTML support
     * - Dompdf: HTML/CSS to PDF conversion
     *
     * Requirements:
     * - Generate PDF from invoice data
     * - Include all invoice details (items, totals, tax, etc.)
     * - Return file path or PDF content
     *
     * @param Invoice $invoice
     * @return string PDF file path or content
     * @throws Exception Currently not implemented
     */
    public function generatePDF($invoice): string
    {
        $dompdf = new Dompdf();
    
        $html = $this->generateHTML($invoice);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
    
        $output = $dompdf->output();
        if(!is_dir(__DIR__ . "/../invoices")) {
            mkdir(__DIR__ . "/../invoices");
        }
    
        $path = __DIR__ . "/../invoices/invoice_{$invoice->id}.pdf";
    
        file_put_contents($path, $output);
    
        return $path;
    }

    /**
     * Generate HTML version of invoice
     * Started this as potential workaround
     *
     * Idea: Generate nice HTML, client can print to PDF from browser?
     * Not ideal but might be acceptable
     *
     * @param Invoice $invoice
     * @return string HTML content
     */
    private function generateHTML($invoice) {
        InvoiceCalculator::validateInvoice($invoice);

        $tax = (float) InvoiceCalculator::calculateTax($invoice->getTotal());
        $subtotal = (float) $invoice->getTotal();
        $total = $subtotal + $tax;

        $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Invoice</title>
                <style>
                    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #333; }
                    h1 { margin-bottom: 5px; }
                    .meta { font-size: 11px; color: #666; }
                    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                    th, td { border: 1px solid #ccc; padding: 8px; }
                    th { background: #f2f2f2; }
                    .text-right { text-align: right; }
                    .totals { width: 40%; margin-left: auto; margin-top: 15px; }
                    .totals td { border: none; padding: 4px 8px; }
                    .total-row td { border-top: 1px solid #333; font-weight: bold; }
                </style>
            </head>
            <body>

            <h1>Invoice #' . $invoice->getId() . '</h1>
            <p class="meta">Customer: ' . htmlspecialchars($invoice->getCustomer()) . '</p>

            <table>
            <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Price</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Total</th>
            </tr>
            </thead>
            <tbody>
        ';

        foreach ($invoice->getItems() as $item) {
            $qty = (int) $item['qty'];
            $lineTotal = (float) $item['price'] * $qty;

            $html .= '
            <tr>
                <td>' . htmlspecialchars($item['name']) . '</td>
                <td class="text-right">$' . number_format($item['price'], 2) . '</td>
                <td class="text-right">' . $qty . '</td>
                <td class="text-right">$' . number_format($lineTotal, 2) . '</td>
            </tr>
            ';
        }

        $html .= '
            </tbody>
            </table>

            <table class="totals">
            <tr>
                <td>Subtotal</td>
                <td class="text-right">$' . number_format((float) $subtotal, 2) . '</td>
            </tr>
            <tr>
                <td>Tax</td>
                <td class="text-right">$' . number_format((float) $tax, 2) . '</td>
            </tr>
            <tr class="total-row">
                <td>Total</td>
                <td class="text-right">$' . number_format((float) $total, 2) . '</td>
            </tr>
            </table>

            </body>
            </html>
        ';


        return $html;
    }

    /**
     * Export invoice as HTML (workaround for PDF)
     * At least this works...
     *
     * @param Invoice $invoice
     * @return string HTML file path
     */
    public function exportHTML($invoice) {
        $html = $this->generateHTML($invoice);
        $filename = 'invoice_' . $invoice->getId() . '.html';
        file_put_contents($filename, $html);
        return $filename;
    }

    /**
     * Attempted to write raw PDF - gave up after 2 hours
     * Keeping this as evidence of how hard this is
     */
    private function generateRawPDF_ABANDONED($invoice) {
        // PDF header
        // %PDF-1.4
        // Then you need:
        // - Catalog object
        // - Pages object
        // - Page object
        // - Content stream
        // - Font definitions
        // - Cross-reference table
        // - Trailer
        //
        // Each object has specific byte offsets that need to be calculated
        // Text positioning uses PostScript-like commands
        // Fonts need to be embedded or referenced correctly
        //
        // This is insane to do by hand for a simple invoice
        // Would take days to get right
        //
        // ABANDONED THIS APPROACH

        return "nope nope nope";
    }
}
