# Laravel Project with Stripe

This project is a **Stripe integration** in Laravel that allows you to create customers, save cards, list cards, and make charges. It includes a demo accessible at `/test`.

## Requirements

-   PHP >= 8.1
-   Composer
-   Laravel >= 10
-   Stripe PHP SDK (`stripe/stripe-php`)
-   Web server (e.g., Artisan, Nginx, Apache)

---

## Installation

1. **Clone the repository**

```bash
git clone [<YOUR_REPOSITORY_URL>](https://github.com/JoseEnriqueMendo/CourtwinStripe.git)
cd <project-name>
```

2. **Install dependencies**

```bash
composer install
```

3. **Copy the environment file**

```bash
cp .env.example .env
```

4. **Configure environment variables**

```bash
STRIPE_SECRET=sk_test_xxxxxxxx
PUBLIC_STRIPE_SECRET=pk_test_xxxxxxxx
```

5. **Configure services.php**

```bash
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET')
],
```

6. **Generate the application key**

```bash
 php artisan key:generate

```

7. **Start the Laravel server**

```bash
php artisan serve
```

**Demo**

The demo is available at:

```
http://127.0.0.1:8000/
```
