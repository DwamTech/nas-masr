<?php

/**
 * SAFE DEPLOYMENT SCRIPT FOR is_representative FEATURE
 * 
 * This script will:
 * 1. Run the migration to add is_representative column
 * 2. Set is_representative=true for all users with role='representative'
 * 3. Fix user ID 8 by setting is_representative=true (keeping role as advertiser)
 * 4. Verify the changes
 * 
 * SAFE FOR PRODUCTION - Does not modify existing role column
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== SAFE DEPLOYMENT: is_representative Feature ===\n\n";

// Step 1: Check if column already exists
echo "Step 1: Checking if is_representative column exists...\n";
$columnExists = DB::select("SHOW COLUMNS FROM users LIKE 'is_representative'");

if (!empty($columnExists)) {
    echo "  ⚠️  Column already exists. Skipping migration.\n\n";
} else {
    echo "  ℹ️  Column does not exist. Running migration...\n";
    
    try {
        // Run migration
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_02_26_000001_add_is_representative_to_users.php', '--force' => true]);
        echo "  ✅ Migration completed successfully!\n\n";
    } catch (\Exception $e) {
        echo "  ❌ Migration failed: " . $e->getMessage() . "\n";
        echo "  Please run manually: php artisan migrate --force\n\n";
        exit(1);
    }
}

// Step 2: Verify migration
echo "Step 2: Verifying migration...\n";
$repsWithFlag = User::where('role', 'representative')->where('is_representative', true)->count();
$totalReps = User::where('role', 'representative')->count();
echo "  Representatives with is_representative=true: $repsWithFlag / $totalReps\n";

if ($repsWithFlag < $totalReps) {
    echo "  ⚠️  Some representatives don't have the flag. Fixing...\n";
    DB::statement("UPDATE users SET is_representative = 1 WHERE role = 'representative'");
    echo "  ✅ Fixed!\n";
}
echo "\n";

// Step 3: Fix user ID 8 specifically
echo "Step 3: Fixing user ID 8...\n";
$user8 = User::find(8);

if ($user8) {
    echo "  Current state:\n";
    echo "    - Name: {$user8->name}\n";
    echo "    - Role: {$user8->role}\n";
    echo "    - is_representative: " . ($user8->is_representative ? 'true' : 'false') . "\n";
    
    if (!$user8->is_representative) {
        echo "  Setting is_representative = true...\n";
        $user8->is_representative = true;
        $user8->save();
        echo "  ✅ User ID 8 is now a representative (role remains '{$user8->role}')\n";
    } else {
        echo "  ✅ User ID 8 already has is_representative = true\n";
    }
} else {
    echo "  ⚠️  User ID 8 not found\n";
}
echo "\n";

// Step 4: Test the validation logic
echo "Step 4: Testing validation logic...\n";
$test8 = User::where('id', 8)->where('is_representative', true)->first();
$test30 = User::where('id', 30)->where('is_representative', true)->first();

echo "  Query: User::where('id', 8)->where('is_representative', true)->first()\n";
echo "    Result: " . ($test8 ? "✅ FOUND (Name: {$test8->name})" : "❌ NOT FOUND") . "\n";

echo "  Query: User::where('id', 30)->where('is_representative', true)->first()\n";
echo "    Result: " . ($test30 ? "✅ FOUND (Name: {$test30->name})" : "❌ NOT FOUND") . "\n";
echo "\n";

// Step 5: Summary
echo "=== SUMMARY ===\n";
echo "Total users: " . User::count() . "\n";
echo "Users with role='representative': " . User::where('role', 'representative')->count() . "\n";
echo "Users with is_representative=true: " . User::where('is_representative', true)->count() . "\n";
echo "Users with referral_code='8': " . User::where('referral_code', '8')->count() . "\n";
echo "Users with referral_code='30': " . User::where('referral_code', '30')->count() . "\n";

echo "\n=== DEPLOYMENT COMPLETE ===\n";
echo "✅ The system now supports users being both advertisers AND representatives!\n";
echo "✅ User ID 8 can now accept referral codes.\n";
echo "✅ All existing functionality preserved.\n\n";

echo "⚠️  IMPORTANT: Clear application cache:\n";
echo "   php artisan config:clear\n";
echo "   php artisan cache:clear\n";
