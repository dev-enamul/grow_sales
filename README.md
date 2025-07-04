# GrowSales Backend

This is the backend repository for the GrowSales application, built with Laravel. It provides the API endpoints and handles data management for the GrowSales frontend.

## Features

- User Authentication & Authorization
- Company and User Management
- Product and Category Management
- Lead and Sales Management
- Reporting and Analytics
- And more...

## Technologies Used

- **Laravel**: PHP Framework
- **PHP**: Programming Language
- **MySQL**: Database
- **Composer**: PHP Dependency Manager
- **NPM/Yarn**: Frontend Asset Management (if applicable for `vite.config.js`)

## Getting Started

Follow these steps to get the development environment up and running.

### Prerequisites

- PHP >= 8.1
- Composer
- Node.js & npm (or Yarn)
- MySQL Database

### Installation

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/your-username/GrowSales-Backend.git
    cd GrowSales-Backend
    ```

2.  **Install PHP dependencies:**
    ```bash
    composer install
    ```

3.  **Copy the environment file:**
    ```bash
    cp .env.example .env
    ```

4.  **Generate application key:**
    ```bash
    php artisan key:generate
    ```

5.  **Configure your database:**
    Open the `.env` file and update the database connection details:
    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=growsales_db
    DB_USERNAME=root
    DB_PASSWORD=
    ```

6.  **Run database migrations:**
    ```bash
    php artisan migrate
    ```

7.  **Seed the database (optional):**
    ```bash
    php artisan db:seed
    ```

8.  **Install Node.js dependencies and compile assets (if using Vite/frontend assets):**
    ```bash
    npm install
    npm run dev
    # or npm run build for production
    ```

9.  **Start the Laravel development server:**
    ```bash
    php artisan serve
    ```

    The application will be accessible at `http://127.0.0.1:8000`.

## API Endpoints

(You can add a section here detailing key API endpoints, e.g., `/api/login`, `/api/products`, etc., or link to API documentation if available.)

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).