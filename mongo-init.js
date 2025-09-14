// File: mongo-init.js
db = db.getSiblingDB(process.env.MONGO_INITDB_DATABASE);

// Create collections with indexes
db.createCollection("customers");
db.customers.createIndex({ "email": 1 }, { unique: true });
db.customers.createIndex({ "ssn": 1 }, { unique: true });
db.customers.createIndex({ "account_number": 1 }, { unique: true });

db.createCollection("departments");
db.departments.createIndex({ "name": 1 }, { unique: true });

db.createCollection("bank_employees");
db.bank_employees.createIndex({ "email": 1 }, { unique: true });
db.bank_employees.createIndex({ "department_id": 1 });

db.createCollection("loan_products");
db.loan_products.createIndex({ "product_name": 1 });

db.createCollection("loan_applications");
db.loan_applications.createIndex({ "application_reference": 1 }, { unique: true });
db.loan_applications.createIndex({ "customer_id": 1 });
db.loan_applications.createIndex({ "product_id": 1 });

db.createCollection("collaterals");
db.collaterals.createIndex({ "customer_id": 1 });

db.createCollection("loans");
db.loans.createIndex({ "application_id": 1 });
db.loans.createIndex({ "customer_id": 1 });
db.loans.createIndex({ "product_id": 1 });

db.createCollection("documents");
db.documents.createIndex({ "customer_id": 1 });

db.createCollection("payment_schedules");
db.payment_schedules.createIndex({ "loan_id": 1 });
db.payment_schedules.createIndex({ "due_date": 1 });

db.createCollection("payment_transactions");
db.payment_transactions.createIndex({ "loan_id": 1 });
db.payment_transactions.createIndex({ "schedule_id": 1 });
db.payment_transactions.createIndex({ "customer_id": 1 });

db.createCollection("notifications");
db.notifications.createIndex({ "recipient_id": 1 });
db.notifications.createIndex({ "related_id": 1 });

db.createCollection("audit_logs");
db.audit_logs.createIndex({ "user_id": 1 });
db.audit_logs.createIndex({ "entity_id": 1 });

db.createCollection("refresh_tokens");
db.refresh_tokens.createIndex({ "token": 1 }, { unique: true });
db.refresh_tokens.createIndex({ "customer_id": 1 });

// Insert initial department
db.departments.insertOne({
  name: "Loan Department",
  description: "Handles all loan-related operations",
  created_at: new Date(),
  updated_at: new Date()
});

print("MongoDB initialization completed successfully!");