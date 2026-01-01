<?php

/**
 * Reset Auto Increment for users table
 * Run: php reset_auto_increment.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "\n========================================\n";
echo "   Reset Auto Increment\n";
echo "========================================\n\n";

try {
    // Get maximum ID from users table
    $maxId = User::max('id');
    
    if (!$maxId) {
        echo "⚠️  No users found in database. Setting auto increment to 1.\n";
        $maxId = 0;
    } else {
        echo "✓ Current maximum user ID: {$maxId}\n";
    }
    
    // Set auto increment to max ID + 1
    $nextId = $maxId + 1;
    
    DB::statement("ALTER TABLE users AUTO_INCREMENT = {$nextId}");
    
    echo "✓ Auto increment reset to: {$nextId}\n";
    
    // Verify
    $result = DB::select("SHOW TABLE STATUS LIKE 'users'");
    $autoIncrement = $result[0]->Auto_increment ?? 'Unknown';
    
    echo "\n========================================\n";
    echo "   ✅ Success!\n";
    echo "========================================\n\n";
    echo "Next user ID will be: {$autoIncrement}\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
