# 🛒 E-Commerce API — Laravel 10

A RESTful E-Commerce API built with **Laravel 10**, featuring:
- Product & Order management & Cart
- PayPal payment integration
- Role & Permission system using **Spatie**
- Authentication & authorization
- Clean Postman API collection

---

## 🚀 Features

- 🧾 Products CRUD
- 📦 Orders & Order Items
- 💳 PayPal Payment Integration
- 🔐 Authentication (Sanctum / Token-based)
- 👥 Roles & Permissions using **spatie/laravel-permission**
- 📮 Postman Collection for easy testing

---

## 🧱 Tech Stack

- **Backend:** Laravel 10
- **Auth:** Laravel Sanctum
- **Payments:** PayPal REST API
- **Authorization:** Spatie Laravel Permission
- **Database:** MySQL
- **API Testing:** Postman

---

## 📂 Project Structure

app/\
routes/\
database/\
postman collection/

---

## ⚙️ Installation

1. Clone the repository:
```bash
git clone https://github.com/amar21112/E-commerce-api
cd E-commerce-api
Install dependencies:

composer install
Copy environment file:

cp .env.example .env
Generate app key:

php artisan key:generate
Configure .env:

Database credentials

PayPal keys

Sanctum config

Run migrations & seeders:

php artisan migrate --seed
Start the server:

php artisan serve
🔐 Roles & Permissions (Spatie)
This project uses:

spatie/laravel-permission
Example roles:

Admin

Salesman

User

Permissions are assigned using:

$role->givePermissionTo('create product');
Middleware examples:

->middleware(['auth:sanctum', 'role:admin'])
->middleware(['auth:sanctum', 'permission:edit products'])
💳 PayPal Payment Integration
Create PayPal app (Sandbox)

Set in .env:

PAYPAL_CLIENT_ID=your_client_id
PAYPAL_SECRET=your_secret
PAYPAL_MODE=sandbox
The API handles:

Create PayPal Order

Capture Payment

Store Payment Status

📮 Postman Collection
The Postman collection is located in:

postmanCollections/collection_name.postman_collection.json
How to use:
Open Postman

Click Import

Upload the collection file


🧪 Testing
Use Postman to test:

Auth

Products

Orders

Payments

👨‍💻 Author
Ammar Yasser
Backend Developer (Laravel / API)

⭐ If you like this project
Give it a ⭐ on GitHub 😄
