# ZiMaTech Manufacturing Management System

[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D_8.2-blue.svg)](https://php.net)
[![Vite](https://img.shields.io/badge/Vite-6.x-646CFF.svg)](https://vite.dev)
[![Electron](https://img.shields.io/badge/Electron-39.x-47848F.svg)](https://electronjs.org)
[![Pest](https://img.shields.io/badge/Tests-Pest-00a2ed.svg)](https://pestphp.com)

**ZiMaTech** is a modern, enterprise-grade manufacturing and production management system built on **Laravel 12** and styled with **Tailwind CSS v4** and **Bootstrap 5**. Designed specifically for engineering, metalworking, and fabrication shops, ZiMaTech offers deep tracking of production times, machine utilization, component measurement histories, and supplier relationships. 

To bridge the gap between office staff and shop floor workers, ZiMaTech operates as a **hybrid application**: it can be run as a standard web application, installed on tablets/mobiles as a **Progressive Web App (PWA)**, or run locally as a native **Electron desktop application**.

---

## 📖 Table of Contents

- [Core Features](#-core-features)
- [Architecture & Module Flags](#-architecture--module-flags)
- [Technology Stack](#-technology-stack)
- [System Requirements](#-system-requirements)
- [Installation & Setup](#-installation--setup)
- [Development Workflow](#-development-workflow)
- [Electron Desktop Shell](#-electron-desktop-shell)
- [PWA & Offline Capability](#-pwa--offline-capability)
- [Testing & Code Quality](#-testing--code-quality)
- [Localization](#-localization)

---

## 🌟 Core Features

| Module | German Name | Purpose & Functionality | Key Database Entities |
| :--- | :--- | :--- | :--- |
| **Time Tracking** | *Zeiterfassung* | Real-time tracking of employee hours worked on specific projects and machines. Includes process start/pause functionality, machine state recording, and a request/approval workflow for correcting time logs. | `TimeRecord`, `TimeLog`, `TimeChangeRequest`, `Process`, `ProcessPause` |
| **Project Management** | *Projektverwaltung* | Full management of customer orders, work breakdown positions, and specific project services. | `Project`, `Position`, `ProjectService`, `ProjectStatus` |
| **Component Tracking** | *Bauteile* | Tracks physical manufacturing components (`Bauteile`), including exact physical dimensions, materials, and measurement verification records. | `Bauteil`, `BauteilMeasurement`, `Material` |
| **Supplier Ecosystem** | *Lieferanten* | Tracks external supplier profiles, supplier-provided services, custom project assignments, and formal supplier price offers. | `Supplier`, `SupplierOffer`, `SupplierProject`, `SupplierService` |
| **Project Offers** | *Angebote* | Build complex pricing calculations with modular line items and export custom offers as professional PDFs or send them directly via email. | `ProjectOffer`, `OfferCalculation`, `OfferCalculationItem`, `OfferFile`, `OfferEmail` |
| **Production Schedule** | *Produktionsplan* | Weekly scheduler dashboard and calendar views for allocating machines, human resources, and timelines. | `ProductionSchedule` |
| **Hardware Helpdesk** | *Drucker-Probleme* | Report and track printer or local office hardware issues, including screenshots/attachments and email integration. | `PrinterProblem`, `PrinterProblemAttachment`, `PrinterProblemEmail` |
| **Feedback System** | *Feedback* | Internal feedback loop for employees to report bugs or request features. | `Feedback` |

---

## 🏗️ Architecture & Module Flags

Features and menus can be dynamically toggled using the module settings configuration file:
* **Configuration File:** `config/modules.php`

```php
return [
    'teams' => true,
    'projects' => true,
    'time' => true,
    'project_offers' => false, // Set to true to enable offer generation
    'emails' => false,         // Set to true to enable email integrations
    'suppliers' => true,
    'settings' => true,
    'feedback' => true,
    'tablar' => true,
    'scheduler' => true,
];
```

Routes and controllers query these module flags (`if (config('modules.projects'))`) before registering application endpoints, making the application lightweight and customizable for different deployments.

---

## 💻 Technology Stack

### Backend
* **Framework:** Laravel 12 (Core routing, MVC, Eloquent ORM, Blade templating)
* **Testing:** Pest PHP
* **Exports:** 
  * PDF Generation: `barryvdh/laravel-dompdf` (DomPDF wrapper)
  * Excel Sheets: `phpoffice/phpspreadsheet`
  * Presentations: `phpoffice/phppresentation`
* **Email Clients:** 
  * Outbound (Microsoft Graph): Kiota HTTP & Microsoft Graph SDK v2 (for Office 365)
  * Inbound (IMAP): `webklex/laravel-imap`

### Frontend & UI
* **Styling & Theme:** Tailwind CSS v4 & Bootstrap 5 (hybrid style structure)
* **Bundler:** Vite (Laravel-Vite plugin)
* **Assets:** Custom Sass (`resources/sass/app.scss`) and modern icons (Bootstrap Icons)
* **Interactions:** SweetAlert2 for notifications and dialog confirmation boxes

### Desktop Wrapper
* **Shell:** Electron v39.x
* **Builder:** `electron-builder` (packages native Windows executables)

---

## 📋 System Requirements

* **PHP:** `^8.2` (with standard extensions: `pdo_mysql`, `gd`, `zip`, `xml`, `mbstring`)
* **Node.js:** `^20.0` or higher
* **Database:** MySQL / MariaDB (or SQLite for local/testing)
* **Composer:** `^2.2` or higher

---

## 🚀 Installation & Setup

Follow these steps to set up the application for local development:

### 1. Clone the Repository
```bash
git clone <repository-url> zimatech-backend
cd zimatech-backend
```

### 2. Run the Composer Setup Command
The project includes a custom Composer script that runs migrations, installs dependencies, and builds assets:
```bash
composer run setup
```
*This command runs under the hood:*
* `composer install`
* Copies `.env.example` to `.env` (if not present)
* `php artisan key:generate`
* `php artisan migrate --force`
* `npm install`
* `npm run build`

### 3. Database Configuration
Edit the generated `.env` file to configure your local MySQL/MariaDB credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zimatech
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Create Directory Link
To ensure user-uploaded PDFs, reports, and component pictures are served correctly, link the storage directory:
```bash
php artisan storage:link
```

---

## 🛠️ Development Workflow

### Running Locally
To launch the backend server, the queue listener, and the Vite asset server concurrently, use:
```bash
composer run dev
```
This script uses `npx concurrently` to multiplex:
* **Laravel Server:** `php artisan serve` (running at `http://127.0.0.1:8000`)
* **Queue Listener:** `php artisan queue:listen --tries=1`
* **Vite Dev Server:** `npm run dev`

---

## 🖥️ Electron Desktop Shell

For shop-floor terminals, the application can be run directly inside an Electron window without launching a separate web browser.

### Launch Electron
To start the Electron application:
```bash
npm run start
```
*Note: The Electron main script (`electron/main.js`) will verify if the Laravel server is running at `http://127.0.0.1:8000`. If it is not running, it automatically spawns a child process to launch `php artisan serve`.*

### Package Desktop Application
To compile and package the desktop app into a standalone Windows installer (`.exe`):
```bash
npm run build-electron
```
The resulting executable will be placed in the `dist/` directory.

---

## 📱 PWA & Offline Capability

ZiMaTech contains full Progressive Web App support, allowing workers to install the dashboard on mobile phones and warehouse tablets.

* **Manifest:** `public/manifest.json` defines colors, display settings, and app icons.
* **Service Worker:** `public/sw.js` caches static CSS/JS files and basic shell resources.
* **Offline Fallback:** When a network connection is lost, the PWA serves `public/offline.html` to inform users of the offline state while retaining core cached templates.

---

## 🧪 Testing & Code Quality

### Running Tests
The project uses the **Pest PHP** testing framework.
To run the automated tests:
```bash
composer run test
```
*This command clears config caches and runs Pest tests.*

Or run Pest directly:
```bash
php artisan test
```

### Code Formatting
To ensure your code adheres to Laravel's style guidelines, run Laravel Pint (the code styling tool):
```bash
./vendor/bin/pint
```

---

## 🌐 Localization

The application is localized in **German (`de`)**. All UI strings, error messages, and labels use Laravel translation files located in the `lang/de/` directory.

Users can switch languages via the locale controller endpoint:
`GET /language/{locale}` (supported locales: `de`, `en`).
