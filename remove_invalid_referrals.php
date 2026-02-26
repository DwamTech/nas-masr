<?php

/**
 * Remove Invalid Referral Codes
 * This will remove referral_code = 8 from users since user 8 is not a representative
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== Removing Invalid Referral Codes ===\n\n";

// Find users with referral_code = 8
$users = User::where('referral_code', '8')->get();

echo "Found {$users->count()} users with referral_code = 8:\n";
foreach ($users as $user) {
    echo "  - ID: {$user->id} | Name: {$user->name} | Phone: {$user->phone}\n";
}

if ($users->count() === 0) {
    echo "No users to update.\n";
    exit(0);
}

echo "\nRemoving referral_code from these users...\n";

foreach ($users as $user) {
    $user->referral_code = null;
    $user->save();
    echo "  ✅ Removed from user ID {$user->id}\n";
}

// Also clean up user_clients table
echo "\nCleaning up user_clients table...\n";
$deleted = \DB::table('user_clients')->where('user_id', 8)->delete();
echo "  ✅ Deleted {$deleted} record(s) from user_clients\n";

echo "\n=== Done ===\n";
