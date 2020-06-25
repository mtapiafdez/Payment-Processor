-- Table Creation
CREATE TABLE payment_processor (
    payment_id INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
	first_name VARCHAR(50) NOT NULL,
	last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    charge DECIMAL(10,2) NOT NULL,
	card VARCHAR(100) NOT NULL,
	expiration_date DATE NOT NULL,
    cvc SMALLINT NOT NULL,
    reason VARCHAR(200)
);

-- Forgot To Add Timestamp
ALTER TABLE `payment_processor` 
ADD `creation` TIMESTAMP NOT NULL 
DEFAULT CURRENT_TIMESTAMP AFTER `reason`;

-- Event To Clean UP Payments After Two Weeks
SET GLOBAL event_scheduler="ON"  -- Turn On Event Scheduler

CREATE EVENT remove_old_payments ON SCHEDULE EVERY 2 WEEK ENABLE
	DO
    DELETE FROM payment_processor WHERE DATE_ADD(creation, INTERVAL 2 WEEK) <= CURRENT_TIMESTAMP;