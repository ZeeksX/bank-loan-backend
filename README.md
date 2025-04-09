# Bank Loan Backend

This project is a backend service for managing bank loan applications, approvals, and repayments. It provides APIs for handling customer data, loan processing, and payment tracking.

## Features

- Customer management (CRUD operations)
- Loan application and approval workflows
- Payment tracking and history
- Secure authentication and authorization
- RESTful API design

## Technologies Used

- **Programming Language**: PHP
- **Database**: MySQL
- **Authentication**: JWT
- **Other Tools**: Docker, Swagger for API documentation

## Installation

1. Clone the repository:
    ```bash
    git clone https://github.com/ZeeksX/bank-loan-backend.git
    cd bank-loan-backend
    ```

2. Install dependencies:
    ```bash
    composer install
    ```

3. Set up environment variables:
    - Copy the `.env.example` file to `.env`:
      ```bash
      cp .env.example .env
      ```
    - Update the `.env` file with your database credentials and other required variables.

4. Run database migrations:
    ```bash
    php migrations.php
    ```

5. Start the development server:
    ```bash
    php -S localhost:8000 -t public
    ```

## API Documentation

The API documentation is available at `/api-docs` when the server is running. Use tools like Postman or Swagger UI to explore the endpoints.

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Commit your changes and push the branch.
4. Open a pull request.

## License

This project is licensed under the [MIT License](LICENSE).

## Contact

For questions or support, please contact [ikinwotezekiel@gmail.com].