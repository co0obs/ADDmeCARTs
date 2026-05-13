# ADDmeCART 🛒

A lightweight e-commerce catalog and inventory management system built with **Symfony 7.x**, **Twig**, and **SQLite**.

---

## 🛠️ Prerequisites
- **PHP** (v8.2 or higher)
- **Composer** (PHP dependency manager)
- **Git**

---

## 🚀 First-Time Setup

```bash
# Clone the repository
git clone https://github.com/ajee0222/ADDmeCART.git
cd ADDmeCART

# Install dependencies
composer install

# Create database and schema
php bin/console doctrine:database:create
php bin/console doctrine:schema:create

# Clear cache (optional but recommended)
php bin/console cache:clear

# Start the development server
php -S 0.0.0.0:8000 -t public
```

Open your browser and navigate to: **http://localhost:8000**

---

## 👥 User Roles

| Role | Access |
|------|--------|
| **Customer** | Browse products, cart, wishlist, checkout, order history, support tickets, profile |
| **Seller** | Same as customer + create/manage products (Inventory Manager) |
| **Admin** | Sales dashboard, order management, support ticket replies, product oversight |

---

## 📁 Project Structure

```
ADDmeCART/
├── src/
│   ├── Controller/     # Request handling logic
│   ├── Entity/         # Database models (User, Product, Order, etc.)
│   ├── Repository/     # Data access repositories
│   └── ...
├── templates/           # Twig view templates
├── public/             # Static assets (CSS, images)
├── migrations/         # Database migration files
├── documents/          # SWDD.md documentation
└── var/                # Cache and database (SQLite)
```

---

## 🧪 Resetting the Database

To start fresh (deletes all data):

```bash
rm var/data.db
php bin/console doctrine:schema:create
php bin/console cache:clear
```

---

## 🛠️ Useful Commands

| Command | Description |
|---------|-------------|
| `php -S 0.0.0.0:8000 -t public` | Start dev server |
| `php bin/console cache:clear` | Clear cache |
| `php bin/console doctrine:schema:validate` | Validate DB sync |
| `php bin/console doctrine:migrations:migrate` | Run migrations |
