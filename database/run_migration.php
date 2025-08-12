<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Database Migration - Add Transaction IDs</h3>
                    </div>
                    <div class="card-body">
                        <p>This migration will:</p>
                        <ul>
                            <li>Add a unique transaction_id column to existing transactions</li>
                            <li>Generate unique transaction IDs for all existing transactions</li>
                            <li>Create the transaction_attachments table for file management</li>
                        </ul>
                        
                        <div class="alert alert-warning">
                            <strong>Important:</strong> Please backup your database before running this migration.
                        </div>

                        <?php if (!isset($_POST['run_migration'])): ?>
                            <form method="post">
                                <button type="submit" name="run_migration" class="btn btn-primary">
                                    Run Migration
                                </button>
                                <a href="../user/transactions.php" class="btn btn-secondary">Cancel</a>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h5>Migration Output:</h5>
                                <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;">
<?php
ob_start();
include 'update_existing_transactions.php';
$output = ob_get_clean();
echo htmlspecialchars($output);
?>
                                </pre>
                            </div>
                            <a href="../user/transactions.php" class="btn btn-success">
                                Go to Transactions
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
