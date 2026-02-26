-- Debug Script for Referral Code Issue
-- Run this on your MySQL database to understand the problem

-- 1. Check all representatives (المناديب)
SELECT 
    id,
    name,
    phone,
    role,
    created_at,
    CASE 
        WHEN id < 10 THEN 'Old Representative (ID < 10)'
        WHEN id >= 10 AND id < 20 THEN 'Medium Representative (10-19)'
        ELSE 'New Representative (ID >= 20)'
    END as representative_age
FROM users 
WHERE role = 'representative'
ORDER BY id ASC;

-- 2. Check specific referral codes mentioned in the issue
SELECT 
    id,
    name,
    phone,
    role,
    created_at,
    'Representative with ID 8' as note
FROM users 
WHERE id = 8 AND role = 'representative';

SELECT 
    id,
    name,
    phone,
    role,
    created_at,
    'Representative with ID 30' as note
FROM users 
WHERE id = 30 AND role = 'representative';

-- 3. Check users who have these referral codes
SELECT 
    id,
    name,
    phone,
    referral_code,
    created_at,
    'Users with referral_code = 8' as note
FROM users 
WHERE referral_code = '8';

SELECT 
    id,
    name,
    phone,
    referral_code,
    created_at,
    'Users with referral_code = 30' as note
FROM users 
WHERE referral_code = '30';

-- 4. Check if there's a data type mismatch issue
-- (referral_code stored as string but compared as integer)
SELECT 
    id,
    name,
    role,
    referral_code,
    CAST(referral_code AS UNSIGNED) as referral_code_as_int,
    CASE 
        WHEN referral_code = CAST(referral_code AS CHAR) THEN 'String Match'
        ELSE 'Type Mismatch'
    END as type_check
FROM users 
WHERE referral_code IS NOT NULL
LIMIT 20;

-- 5. Check the exact query that the backend uses
-- This simulates: User::where('id', $validated['referral_code'])->where('role', 'representative')->first()
SELECT 
    id,
    name,
    role,
    'Testing ID = 8' as test_case
FROM users 
WHERE id = 8 AND role = 'representative';

SELECT 
    id,
    name,
    role,
    'Testing ID = "8" (string)' as test_case
FROM users 
WHERE id = '8' AND role = 'representative';

-- 6. Check for any soft deletes or status issues
SELECT 
    id,
    name,
    role,
    status,
    deleted_at,
    'Checking for soft deletes' as note
FROM users 
WHERE id IN (8, 30);

-- 7. Full diagnostic: Compare old vs new representatives
SELECT 
    'Old Representatives (ID < 15)' as category,
    COUNT(*) as count,
    GROUP_CONCAT(id ORDER BY id) as ids
FROM users 
WHERE role = 'representative' AND id < 15

UNION ALL

SELECT 
    'New Representatives (ID >= 15)' as category,
    COUNT(*) as count,
    GROUP_CONCAT(id ORDER BY id) as ids
FROM users 
WHERE role = 'representative' AND id >= 15;

-- 8. Check user_clients table for delegate relationships
SELECT 
    user_id as delegate_id,
    clients,
    'Delegate ID 8 clients' as note
FROM user_clients 
WHERE user_id = 8;

SELECT 
    user_id as delegate_id,
    clients,
    'Delegate ID 30 clients' as note
FROM user_clients 
WHERE user_id = 30;
