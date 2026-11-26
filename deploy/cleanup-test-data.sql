-- Run in production AFTER deployment to remove test accounts and reset demo passwords.
-- Review carefully before executing.

-- 1. Remove test accounts created during development (adjust IDs as needed).
-- DELETE FROM memberships WHERE user_id IN (34, 30, 31);
-- DELETE FROM users WHERE id IN (34, 30, 31);

-- 2. Force password change for any account still using the dev password.
-- (Run in PHP instead: password_hash a new temp password and set must_change_password=1)
-- UPDATE users SET must_change_password = 1 WHERE username IN ('test', 'bugtest_zdvuht');

-- 3. Remove demo businesses/products/services if they were test data.
-- DELETE FROM sales WHERE business_id IN (13, 14, 15, 18);
-- DELETE FROM products WHERE business_id IN (13, 14, 15, 18);
-- DELETE FROM services WHERE business_id IN (13, 14, 15, 18);
-- DELETE FROM memberships WHERE business_id IN (13, 14, 15, 18);
-- DELETE FROM businesses WHERE id IN (13, 14, 15, 18);

-- NOTE: Keep at least one owner account so you can log in after cleanup.