-- MySQL / MariaDB least-privilege setup for this project.
-- Replace both password placeholders with different strong passwords before running.
-- Run this script as a database administrator, not as the application account.

CREATE USER IF NOT EXISTS 'app_migrator'@'localhost'
    IDENTIFIED BY 'REPLACE_WITH_A_UNIQUE_MIGRATOR_PASSWORD';
CREATE USER IF NOT EXISTS 'app_runtime'@'localhost'
    IDENTIFIED BY 'REPLACE_WITH_A_DIFFERENT_RUNTIME_PASSWORD';

-- Schema changes belong to the separate migration account.
GRANT CREATE, ALTER, INDEX, REFERENCES
    ON `onlineshop`.*
    TO 'app_migrator'@'localhost';
GRANT CREATE, ALTER, INDEX, REFERENCES
    ON `ecommerece`.*
    TO 'app_migrator'@'localhost';

-- The app needs DELETE for cart/wishlist cleanup and admin product/order deletion.
-- These are data privileges only; the runtime account receives no DROP or GRANT OPTION.
GRANT SELECT, INSERT, UPDATE, DELETE
    ON `onlineshop`.*
    TO 'app_runtime'@'localhost';

-- Legacy registration flow in config.php also upgrades hashes after successful login.
GRANT SELECT, INSERT, UPDATE
    ON `ecommerece`.*
    TO 'app_runtime'@'localhost';

SHOW GRANTS FOR 'app_migrator'@'localhost';
SHOW GRANTS FOR 'app_runtime'@'localhost';

-- Verification (connect separately as app_runtime):
-- DROP TABLE `onlineshop`.`categories`;
-- Expected: ERROR 1142 (42000): DROP command denied to user 'app_runtime'@'localhost'
