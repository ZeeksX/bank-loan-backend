// mongo-init.js
db = db.getSiblingDB(process.env.MONGO_INITDB_DATABASE || 'bank_loan_db');

const coll = (name) => {
  try { db.createCollection(name); } catch (e) {}
};

coll("customers");
db.customers.createIndex({ email: 1 }, { unique: true });
db.customers.createIndex({ ssn: 1 }, { unique: true });

coll("departments");
db.departments.createIndex({ name: 1 }, { unique: true });

coll("bank_employees");
db.bank_employees.createIndex({ email: 1 }, { unique: true });

print("mongo-init.js done");
