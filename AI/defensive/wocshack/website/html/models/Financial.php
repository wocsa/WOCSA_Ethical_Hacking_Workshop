<?php
require_once('tcpdf/tcpdf.php'); // Include TCPDF library
require 'tools/sanitize.php';

class Financial
{
    private $associationName;
    private $donatorName;
    private $amount;
    private $bankAccount;
    private $pdo;

    public function __construct($pdo, $associationName, $donatorName, $amount, $bankAccount = null)
    {
        $this->pdo = $pdo;
        $this->associationName = $associationName;
        $this->donatorName = $donatorName;
        $this->amount = $amount;
        $this->bankAccount = $bankAccount;
    }

    public function generatePDF($outputPath)
    {
        // Ensure the directory exists
        $directory = dirname($outputPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true); // Create the directory with recursive permissions
        }
    
        // Create a new TCPDF instance
        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($this->associationName);
        $pdf->SetTitle('Donation Receipt');
        $pdf->SetSubject('Donation Receipt');
        $pdf->SetKeywords('Donation, Receipt, Association');
    
        // Set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Donation Receipt', $this->associationName);
    
        // Set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
        // Add a page
        $pdf->AddPage();
    
        // Set font
        $pdf->SetFont('Helvetica', 'B', 16);
    
        // Add title
        $pdf->Cell(0, 10, 'Donation Receipt', 0, 1, 'C');
        $pdf->Ln(10); // Line break
    
        // Add association name
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'Association: ' . $this->associationName, 0, 1);
    
        // Add donation amount
        $pdf->Cell(0, 10, 'Amount: $' . number_format($this->amount, 2), 0, 1);
    
        // Create a form field for displaying the donator's name
        $pdf->SetXY(10, 100); // Position the form field
        $pdf->SetFont('Helvetica', 'I', 12);
        $pdf->Cell(0, 10, 'Donator: ', 0, 1);
    
        // Create an editable form field to display the donator's name (JavaScript will modify this)
        $pdf->SetXY(50, 100);
        $pdf->SetFont('Helvetica', 'I', 12);
        $pdf->Cell(0, 10, $this->donatorName, 0, 1);
    
        // Add thank you note
        $pdf->Ln(10);
        $pdf->MultiCell(0, 10, "Thank you for your generous donation! Your contribution helps us to continue our mission and make a positive impact.", 0, 'L');
    
        // Add JavaScript to dynamically update the form field with the donator's name
        $js = "
        var donatorField = this.getField('donator_name');
        if (donatorField != null) {donatorField.value = '" . $this->donatorName . "';}";
        $pdf->IncludeJS($js); // Add JavaScript to the PDF
    
        // Add a border around the content
        $pdf->Rect(10, 10, 190, 270, 'D');
    
        // Output the PDF
        $pdf->Output($outputPath, 'F'); // Save to the specified file
    }

    public function addBankAccount($accountNumber, $bankName, $routingNumber)
    {
        // Check for duplicate account number and routing number
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM bank_accounts WHERE account_number = :account_number AND routing_number = :routing_number");
        $stmt->bindParam(':account_number', $accountNumber);
        $stmt->bindParam(':routing_number', $routingNumber);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            throw new Exception("Bank account with this account number and routing number already exists.");
        }

        // Prepare the SQL statement
        $stmt = $this->pdo->prepare("INSERT INTO bank_accounts (association_name, account_number, bank_name, routing_number) VALUES (:association_name, :account_number, :bank_name, :routing_number)");

        // Bind the parameters
        $stmt->bindParam(':association_name', $this->associationName);
        $stmt->bindParam(':account_number', $accountNumber);
        $stmt->bindParam(':bank_name', $bankName);
        $stmt->bindParam(':routing_number', $routingNumber);

        // Execute the statement
        $stmt->execute();

        // Update the local bank account property
        $this->bankAccount = [
            'accountNumber' => $accountNumber,
            'bankName' => $bankName,
            'routingNumber' => $routingNumber
        ];
    }

    public function getBankAccount()
    {
        // Prepare the SQL statement
        $stmt = $this->pdo->prepare("SELECT * FROM bank_accounts WHERE association_name = :association_name");

        // Bind the parameter
        $stmt->bindParam(':association_name', $this->associationName);

        // Execute the statement
        $stmt->execute();

        // Fetch the bank account details
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function removeBankAccount()
    {
        // Prepare the SQL statement
        $stmt = $this->pdo->prepare("DELETE FROM bank_accounts WHERE association_name = :association_name");

        // Bind the parameter
        $stmt->bindParam(':association_name', $this->associationName);

        // Execute the statement
        $stmt->execute();

        // Clear the local bank account property
        $this->bankAccount = null;
    }

    public function addTransaction($amount, $description, $donatorName)
    {
        // Prepare the SQL statement
        $stmt = $this->pdo->prepare("INSERT INTO transactions (association_name, donator_name, amount, description, transaction_date) VALUES (:association_name, :donator_name, :amount, :description, NOW())");

        // Bind the parameters
        $stmt->bindParam(':association_name', $this->associationName);
        $stmt->bindParam(':donator_name', $donatorName);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':description', $description);

        // Execute the statement
        $stmt->execute();
    }

    public function getTransactions()
    {
        // Prepare the SQL statement
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE association_name = :association_name;");

        // Bind the parameters
        $stmt->bindParam(':association_name', $this->associationName);

        // Execute the statement
        $stmt->execute();

        // Fetch all transactions
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteTransaction($transactionId)
    {
        // Prepare the SQL statement
        $stmt = $this->pdo->prepare("DELETE FROM transactions WHERE id = :transaction_id");

        // Bind the parameter
        $stmt->bindParam(':transaction_id', $transactionId);

        // Execute the statement
        $stmt->execute();
    }

    public function getTotalAmount()
    {
        // Prepare the SQL statement
        $stmt = $this->pdo->prepare("SELECT SUM(amount) as total_amount FROM transactions WHERE association_name = :association_name");

        // Bind the parameters
        $stmt->bindParam(':association_name', $this->associationName);

        // Execute the statement
        $stmt->execute();

        // Fetch the total amount
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!isset($result['total_amount']) || is_null($result['total_amount'])) {
            $result['total_amount'] = 0;
        }

        return $result['total_amount'];
    }

    public function computeCalc($amount_simulator)
    {
    
        $sanitize = new sanitize();
        $safe_input = $sanitize->contains_forbidden_characters($amount_simulator);

        //if (preg_match('/^[0-9+\-*\/().\s]+$/', $amount_simulator)) {
        if ($safe_input) {
            try {
                $calculation_result = eval("return $amount_simulator + $this->amount;");
            } catch (Throwable $e) {
                $calculation_result = 'Error in calculation!';
            }
        } else {
            $calculation_result = 'Invalid input!';
        }

        return $calculation_result;
    }
}
?>