<?php

/**
 * Simple test script to verify the delegates system changes
 * 
 * Run: php test_delegates_system.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\UserClient;

echo "\n========================================\n";
echo "   Testing Delegates System\n";
echo "========================================\n\n";

// Test 1: Check if a user can be a representative
echo "Test 1: Checking representative user...\n";
$rep = User::where('role', 'representative')->first();
if ($rep) {
    echo "✅ Found representative: {$rep->name} (ID: {$rep->id})\n";
    echo "   User Code (Delegate Code): {$rep->id}\n";
    
    // Check user_clients
    $userClient = UserClient::where('user_id', $rep->id)->first();
    if ($userClient) {
        $clientsCount = count($userClient->clients ?? []);
        echo "   Clients count: {$clientsCount}\n";
        if ($clientsCount > 0) {
            echo "   Clients IDs: " . json_encode($userClient->clients) . "\n";
        }
    } else {
        echo "   ⚠️  No user_clients record found\n";
    }
} else {
    echo "⚠️  No representative found in database\n";
}

echo "\n----------------------------------------\n\n";

// Test 2: Check if users have referral_code
echo "Test 2: Checking users with referral_code...\n";
$usersWithReferral = User::whereNotNull('referral_code')->get();
echo "Found {$usersWithReferral->count()} users with referral_code\n";

foreach ($usersWithReferral->take(5) as $user) {
    echo "  - {$user->name} (ID: {$user->id})\n";
    echo "    Referral Code: {$user->referral_code}\n";
    echo "    Role: {$user->role}\n";
    
    // Verify delegate exists
    $delegate = User::where('id', $user->referral_code)
        ->where('role', 'representative')
        ->first();
    
    if ($delegate) {
        echo "    ✅ Delegate verified: {$delegate->name}\n";
    } else {
        echo "    ❌ Invalid delegate code!\n";
    }
}

if ($usersWithReferral->count() === 0) {
    echo "  ℹ️  No users with referral_code found\n";
}

echo "\n----------------------------------------\n\n";

// Test 3: Verify user_clients unique constraint
echo "Test 3: Checking user_clients table...\n";
$allUserClients = UserClient::all();
echo "Total user_clients records: {$allUserClients->count()}\n";

$userIds = $allUserClients->pluck('user_id')->toArray();
$uniqueUserIds = array_unique($userIds);

if (count($userIds) === count($uniqueUserIds)) {
    echo "✅ All user_id values are unique\n";
} else {
    echo "❌ Warning: Duplicate user_id found!\n";
}

foreach ($allUserClients as $uc) {
    $user = User::find($uc->user_id);
    if ($user) {
        echo "  - Representative: {$user->name} (ID: {$user->id})\n";
        echo "    Role: {$user->role}\n";
        echo "    Clients: " . count($uc->clients ?? []) . "\n";
    }
}

echo "\n----------------------------------------\n\n";

// Test 4: Summary
echo "Test 4: System Summary\n";
$totalUsers = User::count();
$totalRepresentatives = User::where('role', 'representative')->count();
$totalUsersWithDelegate = User::whereNotNull('referral_code')->count();

echo "Total Users: {$totalUsers}\n";
echo "Total Representatives: {$totalRepresentatives}\n";
echo "Total Users with Delegate: {$totalUsersWithDelegate}\n";

echo "\n========================================\n";
echo "   Tests Completed!\n";
echo "========================================\n\n";
