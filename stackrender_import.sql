CREATE TABLE `roles` (
  id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  permissions TEXT NULL
);

CREATE TABLE `users` (
  id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  role_id INTEGER NULL,
  username VARCHAR(50) NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  firstname VARCHAR(50) NOT NULL,
  lastname VARCHAR(50) NOT NULL,
  usertype ENUM ('admin', 'seller', 'user') NULL DEFAULT 'user',
  status ENUM ('active', 'suspended') NULL DEFAULT 'active',
  is_2fa_enabled TINYINT(1) NULL DEFAULT 0,
  google2fa_secret VARCHAR(64) NULL,
  failed_attempts INTEGER NULL DEFAULT 0,
  lockout_until DATETIME NULL,
  login_count INTEGER NULL DEFAULT 0,
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `audit_logs` (
  id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INTEGER NULL,
  activity VARCHAR(100) NOT NULL,
  details TEXT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `sellers` (
  id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INTEGER NOT NULL UNIQUE,
  shop_name VARCHAR(100) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone_number VARCHAR(20) NOT NULL,
  status ENUM ('pending', 'approved', 'rejected') NULL DEFAULT 'pending',
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `books` (
  id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INTEGER NOT NULL,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255) NOT NULL,
  isbn VARCHAR(20) NULL,
  genre VARCHAR(100) NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  rental_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  rental_period INTEGER NULL DEFAULT 1,
  quantity INTEGER NOT NULL DEFAULT 1,
  book_condition ENUM ('excellent', 'good', 'fair', 'damaged') NULL DEFAULT 'good',
  status ENUM ('available', 'rented', 'sold', 'archived') NULL DEFAULT 'available',
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `book_images` (
  id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  book_id INTEGER NOT NULL,
  image_url VARCHAR(255) NOT NULL,
  is_cover TINYINT(1) NULL DEFAULT 0,
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `cart` (
  id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INTEGER NOT NULL,
  book_id INTEGER NOT NULL,
  quantity INTEGER NOT NULL DEFAULT 1,
  rental_period INTEGER NULL,
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `orders` (
  id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INTEGER NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  status ENUM ('pending', 'processing', 'completed', 'cancelled') NULL DEFAULT 'pending',
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `order_items` (
  id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id INTEGER NOT NULL,
  book_id INTEGER NOT NULL,
  quantity INTEGER NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL,
  purchase_type ENUM ('buy', 'rent') NOT NULL DEFAULT 'buy'
);

CREATE TABLE `book_rentals` (
  id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id INTEGER NULL,
  book_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  seller_id INTEGER NOT NULL,
  rental_weeks INTEGER NOT NULL DEFAULT 1,
  rental_date DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  due_date DATETIME NOT NULL,
  return_date DATETIME NULL,
  total_price DECIMAL(10,2) NOT NULL,
  late_fee DECIMAL(10,2) NULL DEFAULT 0.00,
  status ENUM ('active', 'returned', 'overdue', 'cancelled') NULL DEFAULT 'active'
);

CREATE TABLE `book_returns` (
  id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  rental_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  seller_id INTEGER NOT NULL,
  book_id INTEGER NOT NULL,
  return_method ENUM ('dropoff', 'pickup') NOT NULL DEFAULT 'dropoff',
  return_details TEXT NULL,
  status ENUM ('pending', 'received', 'completed', 'cancelled') NULL DEFAULT 'pending',
  request_date DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  received_date DATETIME NULL,
  completed_date DATETIME NULL,
  book_condition ENUM ('excellent', 'good', 'fair', 'damaged') NULL,
  late_fee DECIMAL(10,2) NULL DEFAULT 0.00,
  damage_fee DECIMAL(10,2) NULL DEFAULT 0.00,
  additional_fee DECIMAL(10,2) NULL DEFAULT 0.00
);

CREATE TABLE `book_swaps` (
  id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  requester_id INTEGER NOT NULL,
  requested_book_id INTEGER NOT NULL,
  offered_book_id INTEGER NOT NULL,
  status ENUM ('pending', 'accepted', 'rejected', 'completed') NULL DEFAULT 'pending',
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `notifications` (
  id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INTEGER NOT NULL,
  sender_id INTEGER NULL,
  type VARCHAR(50) NOT NULL,
  content TEXT NOT NULL,
  is_read TINYINT(1) NULL DEFAULT 0,
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP
);

-- ================================================================
-- FOREIGN KEY CONSTRAINTS (RELATIONSHIPS)
-- ================================================================

ALTER TABLE `users`
ADD CONSTRAINT `fk_users_roles` FOREIGN KEY (role_id) REFERENCES `roles` (id) ON DELETE SET NULL;

ALTER TABLE `audit_logs`
ADD CONSTRAINT `fk_audit_users` FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE SET NULL;

ALTER TABLE `sellers`
ADD CONSTRAINT `fk_sellers_users` FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE;

ALTER TABLE `books`
ADD CONSTRAINT `fk_books_users` FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE;

ALTER TABLE `book_images`
ADD CONSTRAINT `fk_images_books` FOREIGN KEY (book_id) REFERENCES `books` (id) ON DELETE CASCADE;

ALTER TABLE `cart`
ADD CONSTRAINT `fk_cart_users` FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE;

ALTER TABLE `cart`
ADD CONSTRAINT `fk_cart_books` FOREIGN KEY (book_id) REFERENCES `books` (id) ON DELETE CASCADE;

ALTER TABLE `orders`
ADD CONSTRAINT `fk_orders_users` FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE;

ALTER TABLE `order_items`
ADD CONSTRAINT `fk_items_orders` FOREIGN KEY (order_id) REFERENCES `orders` (id) ON DELETE CASCADE;

ALTER TABLE `order_items`
ADD CONSTRAINT `fk_items_books` FOREIGN KEY (book_id) REFERENCES `books` (id) ON DELETE CASCADE;

ALTER TABLE `book_rentals`
ADD CONSTRAINT `fk_rentals_books` FOREIGN KEY (book_id) REFERENCES `books` (id) ON DELETE CASCADE;

ALTER TABLE `book_rentals`
ADD CONSTRAINT `fk_rentals_users` FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE;

ALTER TABLE `book_rentals`
ADD CONSTRAINT `fk_rentals_sellers` FOREIGN KEY (seller_id) REFERENCES `sellers` (id) ON DELETE CASCADE;

ALTER TABLE `book_returns`
ADD CONSTRAINT `fk_returns_rentals` FOREIGN KEY (rental_id) REFERENCES `book_rentals` (id) ON DELETE CASCADE;

ALTER TABLE `book_returns`
ADD CONSTRAINT `fk_returns_users` FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE;

ALTER TABLE `book_returns`
ADD CONSTRAINT `fk_returns_sellers` FOREIGN KEY (seller_id) REFERENCES `sellers` (id) ON DELETE CASCADE;

ALTER TABLE `book_returns`
ADD CONSTRAINT `fk_returns_books` FOREIGN KEY (book_id) REFERENCES `books` (id) ON DELETE CASCADE;

ALTER TABLE `book_swaps`
ADD CONSTRAINT `fk_swaps_requesters` FOREIGN KEY (requester_id) REFERENCES `users` (id) ON DELETE CASCADE;

ALTER TABLE `book_swaps`
ADD CONSTRAINT `fk_swaps_req_books` FOREIGN KEY (requested_book_id) REFERENCES `books` (id) ON DELETE CASCADE;

ALTER TABLE `book_swaps`
ADD CONSTRAINT `fk_swaps_off_books` FOREIGN KEY (offered_book_id) REFERENCES `books` (id) ON DELETE CASCADE;

ALTER TABLE `notifications`
ADD CONSTRAINT `fk_notif_users` FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE;
