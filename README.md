# Traders Lab - Professional Trading Journal

![Traders Lab Banner](https://via.placeholder.com/1200x400/1E293B/FFFFFF?text=Traders+Lab+-+The+Ultimate+Trading+Journal)

A premium, fully-featured Trading Journal web application designed for Crypto, Forex, and Stock traders. Built with modern web technologies, this platform helps traders meticulously track their performance, manage their portfolios, share analysis with the community, and stay updated with the latest market fundamentals.

## ✨ Features

* **Comprehensive Trade Tracking:** Record, edit, and close trades across Crypto, Forex, and Stocks. Automatically calculates PnL (Profit & Loss), Win Rates, and Pip Gains.
* **Advanced Analytics Dashboard:** Visual performance tracking powered by Chart.js, breaking down win rates, monthly PnL, and portfolio growth.
* **Community Chart Sharing:** A dedicated "Market Updates" feed where traders can share their technical analysis setups, strategies, and chart screenshots with others.
* **Admin Control Panel:** A powerful dashboard for administrators to monitor platform statistics, manage users, review all trades, and schedule group meetings.
* **Watchlist & Fundamentals:** Keep track of high-probability setups and read the latest market news and fundamental analysis.
* **Zoom Meeting Integration:** Dedicated section for accessing live community trading sessions.
* **Premium UI/UX:** Built with a custom, highly responsive CSS design system featuring glassmorphism, smooth animations, and a sleek dark/light aesthetic.
* **Robust Security:** Built-in CSRF protection, secure password hashing, and role-based access control (Admin vs User).

## 🛠️ Technology Stack

* **Frontend:** HTML5, Vanilla CSS3 (Custom Design System), Vanilla JavaScript
* **Backend:** PHP 8+
* **Database:** MySQL
* **Libraries:** Chart.js (Data Visualization), FontAwesome (Icons), Google Fonts (Outfit)

## 🚀 Installation & Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/traders-lab.git
   cd traders-lab
   ```

2. **Database Setup**
   * Create a new MySQL database named `crypto_journal`.
   * Import the provided `database.sql` file into your MySQL server to build the table schema.
   * *(Note: If you run into missing columns for trades, ensure you have run the latest ALTER TABLE migrations for `market_type`, `lot_size`, and `pip_gain`)*.

3. **Configure the Application**
   * Navigate to `config/database.php` (or wherever your DB connection is managed).
   * Update the database credentials to match your local or production server environment:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'crypto_journal');
     ```

4. **Run the Application**
   * Host the project folder using a local server like XAMPP, WAMP, or MAMP.
   * Open your browser and navigate to `http://localhost/traders-lab` (or your specific path).

## 📱 Mobile Responsiveness

The application is completely fully-responsive out of the box. It features a collapsible sidebar navigation with a custom hamburger toggle menu tailored specifically for mobile and tablet users.

## 🤝 Contributing

Contributions, issues, and feature requests are welcome! Feel free to check the [issues page](https://github.com/yourusername/traders-lab/issues).

## 📝 License

This project is open-source and available under the [MIT License](LICENSE).
