<?php

/**
 * Fix User ID 8 Role
 * This will change user ID 8 from 'advertiser' to 'representative'
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== Fixing User ID 8 Role ===\n\n";

$user = User::find(8);

if (!$user) {
    echo "❌ User ID 8 not found!\n";
    exit(1);
}

echo "Current User Info:\n";
echo "  ID: {$user->id}\n";
echo "  Name: {$user->name}\n";
echo "  Phone: {$user->phone}\n";
echo "  Current Role: {$user->role}\n\n";

echo "Changing role from '{$user->role}' to 'representative'...\n";

$user->role = 'representative';
$user->save();

echo "✅ Role updated successfully!\n\n";

// Verify the change
$user->refresh();
echo "Verification:\n";
echo "  New Role: {$user->role}\n";

// Test the query
$found = User::where('id', 8)->where('role', 'representative')->first();
echo "  Query Test: " . ($found ? "✅ NOW WORKS!" : "❌ Still not working") . "\n";

echo "\n=== Done ===\n";
