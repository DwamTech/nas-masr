<?php

/**
 * Debug Script for Referral Code Issue
 * Run this file from command line: php debug_referral_code.php
 * Or access it via browser (temporarily): https://back.nasmasr.app/debug_referral_code.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== Debug Referral Code Issue ===\n\n";

// 1. Check all representatives
echo "1. All Representatives:\n";
echo str_repeat("-", 80) . "\n";
$representatives = User::where('role', 'representative')->orderBy('id')->get();
foreach ($representatives as $rep) {
    $age = $rep->id < 10 ? 'Old (ID < 10)' : ($rep->id < 20 ? 'Medium (10-19)' : 'New (ID >= 20)');
    echo sprintf("ID: %3d | Name: %-20s | Phone: %-15s | Age: %s\n", 
        $rep->id, 
        substr($rep->name ?? 'N/A', 0, 20), 
        $rep->phone ?? 'N/A',
        $age
    );
}
echo "\n";

// 2. Check specific IDs (8 and 30)
echo "2. Checking Specific Representatives (ID 8 and 30):\n";
echo str_repeat("-", 80) . "\n";
foreach ([8, 30] as $id) {
    $user = User::find($id);
    if ($user) {
        echo "ID $id: EXISTS\n";
        echo "  Name: {$user->name}\n";
        echo "  Role: {$user->role}\n";
        echo "  Phone: {$user->phone}\n";
        echo "  Is Representative: " . ($user->role === 'representative' ? 'YES ✓' : 'NO ✗') . "\n";
        
        // Check if query would find it
        $found = User::where('id', $id)->where('role', 'representative')->first();
        echo "  Query Result: " . ($found ? 'FOUND ✓' : 'NOT FOUND ✗') . "\n";
    } else {
        echo "ID $id: DOES NOT EXIST ✗\n";
    }
    echo "\n";
}

// 3. Check users with these referral codes
echo "3. Users with Referral Codes 8 and 30:\n";
echo str_repeat("-", 80) . "\n";
foreach (['8', '30'] as $code) {
    $users = User::where('referral_code', $code)->get();
    echo "Referral Code '$code': {$users->count()} users\n";
    foreach ($users as $user) {
        echo sprintf("  - ID: %3d | Name: %-20s | Phone: %s\n", 
            $user->id, 
            substr($user->name ?? 'N/A', 0, 20),
            $user->phone ?? 'N/A'
        );
    }
    echo "\n";
}

// 4. Check for data type issues
echo "4. Checking for Data Type Issues:\n";
echo str_repeat("-", 80) . "\n";
$usersWithReferral = User::whereNotNull('referral_code')->take(10)->get();
foreach ($usersWithReferral as $user) {
    $code = $user->referral_code;
    $length = strlen($code);
    $trimmed = trim($code);
    $hasPadding = $code !== $trimmed;
    
    echo sprintf("User ID: %3d | Referral: [%s] | Length: %d | Has Padding: %s\n",
        $user->id,
        $code,
        $length,
        $hasPadding ? 'YES ⚠️' : 'NO'
    );
}
echo "\n";

// 5. Test the exact query used in the controller
echo "5. Testing Controller Query Logic:\n";
echo str_repeat("-", 80) . "\n";
foreach (['8', '30', ' 8', '8 ', ' 8 '] as $testCode) {
    $result = User::where('id', $testCode)->where('role', 'representative')->first();
    $status = $result ? 'FOUND ✓' : 'NOT FOUND ✗';
    echo sprintf("Testing code [%s] (length: %d): %s\n", $testCode, strlen($testCode), $status);
}
echo "\n";

// 6. Check user_clients table
echo "6. Checking user_clients Table:\n";
echo str_repeat("-", 80) . "\n";
try {
    $clients8 = \DB::table('user_clients')->where('user_id', 8)->first();
    $clients30 = \DB::table('user_clients')->where('user_id', 30)->first();
    
    echo "Delegate ID 8: " . ($clients8 ? "EXISTS (Clients: " . count(json_decode($clients8->clients ?? '[]')) . ")" : "NOT FOUND") . "\n";
    echo "Delegate ID 30: " . ($clients30 ? "EXISTS (Clients: " . count(json_decode($clients30->clients ?? '[]')) . ")" : "NOT FOUND") . "\n";
} catch (\Exception $e) {
    echo "Error checking user_clients: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. Summary
echo "=== SUMMARY ===\n";
echo str_repeat("-", 80) . "\n";
echo "Total Representatives: " . User::where('role', 'representative')->count() . "\n";
echo "Total Users with Referral Code: " . User::whereNotNull('referral_code')->count() . "\n";
echo "Users with Referral Code '8': " . User::where('referral_code', '8')->count() . "\n";
echo "Users with Referral Code '30': " . User::where('referral_code', '30')->count() . "\n";

echo "\n=== END OF DEBUG ===\n";
