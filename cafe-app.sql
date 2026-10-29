-- Membuat dan menggunakan database
CREATE DATABASE cafe_app;
USE cafe_app;

-- 1. TABEL USERS (Kini dilengkapi username unique)
CREATE TABLE users (
	id_user INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR (100),
    username VARCHAR (50) UNIQUE NOT NULL,
    password VARCHAR (255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer'
);

-- 2. TABEL MENUS
CREATE TABLE menus (
	id_menu INT AUTO_INCREMENT PRIMARY KEY,
    nama_menu VARCHAR (100) NOT NULL,
    kategori VARCHAR (30) NOT NULL,
    harga INT NOT NULL,
    deskripsi TEXT,
    gambar VARCHAR (255) NULL
);

-- 3. TABEL ORDERS
CREATE TABLE orders (
    id_order INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    total_harga INT NOT NULL,
    metode_pembayaran ENUM('Cash', 'QRIS') NOT NULL,
    status_pesanan ENUM('Pending', 'Sedang Dibuat', 'Siap Pick-up', 'Selesai') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ALTER TABLE orders ADD tipe_order ENUM('dine_in', 'take_away') NOT NULL;
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);

-- 4. TABEL ORDER_ITEMS
CREATE TABLE order_items (
    id_order_item INT AUTO_INCREMENT PRIMARY KEY,
    id_order INT NOT NULL,
    id_menu INT NOT NULL,
    jumlah INT NOT NULL,
    subtotal INT NOT NULL,
    FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE,
    FOREIGN KEY (id_menu) REFERENCES menus(id_menu) ON DELETE RESTRICT
);





















