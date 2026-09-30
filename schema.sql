-- schema.sql
-- Creates the database and table for the basic product catalog site,
-- then seeds it with a few sample products.

CREATE DATABASE IF NOT EXISTS product_catalog
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE product_catalog;

CREATE TABLE IF NOT EXISTS products (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120)   NOT NULL,
  description VARCHAR(500)   NOT NULL,
  price       DECIMAL(10,2)  NOT NULL,
  image_url   VARCHAR(500)   NULL,
  created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO products (name, description, price, image_url) VALUES
  ('Ceramic Mug', 'Hand-glazed 350ml mug, dishwasher safe.', 12.50, 'https://picsum.photos/seed/mug/400/300'),
  ('Canvas Tote Bag', 'Heavy-duty cotton canvas, fits a laptop.', 18.00, 'https://picsum.photos/seed/tote/400/300'),
  ('Desk Lamp', 'Adjustable LED lamp with warm/cool modes.', 34.99, 'https://picsum.photos/seed/lamp/400/300'),
  ('Notebook Set', 'Pack of 3 dot-grid notebooks, A5 size.', 9.75, 'https://picsum.photos/seed/notebook/400/300'),
  ('Wireless Mouse', 'Compact 2.4GHz mouse with USB receiver.', 22.00, 'https://picsum.photos/seed/mouse/400/300');
