import express from 'express';
import path from 'path';
import fs from 'fs';
import cors from 'cors';
import { v4 as uuidv4 } from 'uuid';
import { createServer as createViteServer } from 'vite';

const app = express();
const PORT = 3000;

app.use(cors());
app.use(express.json());

// JSON Database simulation
const DB_FILE = path.join(process.cwd(), 'database.json');

const defaultDb = {
  users: [
    { id: '1', username: 'admin', password: 'password', role: 'admin' },
    { id: '2', username: 'pharmacist', password: 'password', role: 'pharmacist' }
  ],
  medicines: [
    { id: uuidv4(), name: 'Paracetamol', category: 'Painkiller', company: 'XYZ Pharma', batchNo: 'B001', quantity: 150, price: 10, expiryDate: '2027-01-01' },
    { id: uuidv4(), name: 'Amoxicillin', category: 'Antibiotic', company: 'ABC Med', batchNo: 'B002', quantity: 20, price: 50, expiryDate: '2026-05-15' },
    { id: uuidv4(), name: 'Vitamin C', category: 'Supplement', company: 'HealthCo', batchNo: 'B003', quantity: 0, price: 5, expiryDate: '2025-12-01' }
  ],
  suppliers: [
    { id: uuidv4(), name: 'Global Pharma Distributors', contact: '123-456-7890', email: 'contact@globalpharma.com', address: '123 Health St' }
  ],
  sales: [],
  categories: ['Painkiller', 'Antibiotic', 'Supplement', 'Syrup', 'Injection']
};

function readDb() {
  if (!fs.existsSync(DB_FILE)) {
    writeDb(defaultDb);
    return defaultDb;
  }
  try {
    return JSON.parse(fs.readFileSync(DB_FILE, 'utf-8'));
  } catch (error) {
    console.error('Error parsing database.json, resetting to default:', error);
    writeDb(defaultDb);
    return defaultDb;
  }
}

function writeDb(data: any) {
  fs.writeFileSync(DB_FILE, JSON.stringify(data, null, 2));
}

// Authentication Middleware simulating sessions
app.post('/api/login', (req, res) => {
  const { username, password } = req.body;
  const db = readDb();
  const user = db.users.find((u: any) => u.username === username && u.password === password);
  if (user) {
    res.json({ success: true, user: { id: user.id, username: user.username, role: user.role } });
  } else {
    res.status(401).json({ success: false, message: 'Invalid credentials' });
  }
});

// Dashboard stats
app.get('/api/dashboard', (req, res) => {
  const db = readDb();
  const today = new Date().toISOString().split('T')[0];
  
  const totalMedicines = db.medicines.length;
  const lowStock = db.medicines.filter((m: any) => m.quantity < 30).length;
  const totalSales = db.sales.reduce((sum: number, s: any) => sum + s.total, 0);
  const todaySales = db.sales
    .filter((s: any) => s.date.startsWith(today))
    .reduce((sum: number, s: any) => sum + s.total, 0);

  const recentMedicines = [...db.medicines].reverse().slice(0, 5);

  res.json({ totalMedicines, lowStock, totalSales, todaySales, recentMedicines });
});

// Medicines API
app.get('/api/medicines', (req, res) => {
  res.json(readDb().medicines);
});

app.post('/api/medicines', (req, res) => {
  const db = readDb();
  const newMedicine = { id: uuidv4(), ...req.body };
  db.medicines.push(newMedicine);
  writeDb(db);
  res.json({ success: true, medicine: newMedicine });
});

app.put('/api/medicines/:id', (req, res) => {
  const db = readDb();
  const index = db.medicines.findIndex((m: any) => m.id === req.params.id);
  if (index !== -1) {
    db.medicines[index] = { ...db.medicines[index], ...req.body };
    writeDb(db);
    res.json({ success: true, medicine: db.medicines[index] });
  } else {
    res.status(404).json({ success: false, message: 'Not found' });
  }
});

app.delete('/api/medicines/:id', (req, res) => {
  const db = readDb();
  db.medicines = db.medicines.filter((m: any) => m.id !== req.params.id);
  writeDb(db);
  res.json({ success: true });
});

// Categories
app.get('/api/categories', (req, res) => {
  res.json(readDb().categories);
});

// Suppliers API
app.get('/api/suppliers', (req, res) => {
  res.json(readDb().suppliers);
});
app.post('/api/suppliers', (req, res) => {
  const db = readDb();
  const newSupplier = { id: uuidv4(), ...req.body };
  db.suppliers.push(newSupplier);
  writeDb(db);
  res.json({ success: true, supplier: newSupplier });
});
app.put('/api/suppliers/:id', (req, res) => {
  const db = readDb();
  const index = db.suppliers.findIndex((s: any) => s.id === req.params.id);
  if (index !== -1) {
    db.suppliers[index] = { ...db.suppliers[index], ...req.body };
    writeDb(db);
    res.json({ success: true, supplier: db.suppliers[index] });
  } else {
    res.status(404).json({ success: false, message: 'Not found' });
  }
});
app.delete('/api/suppliers/:id', (req, res) => {
  const db = readDb();
  db.suppliers = db.suppliers.filter((s: any) => s.id !== req.params.id);
  writeDb(db);
  res.json({ success: true });
});

// Billing/Sales API
app.get('/api/sales', (req, res) => {
  res.json(readDb().sales);
});

app.post('/api/sales', (req, res) => {
  const db = readDb();
  const { cart, total, customerName, sellerId } = req.body;
  
  // Reduce stock
  for (const item of cart) {
    const medIndex = db.medicines.findIndex((m: any) => m.id === item.medicineId);
    if (medIndex !== -1) {
      db.medicines[medIndex].quantity -= item.quantity;
      if(db.medicines[medIndex].quantity < 0) db.medicines[medIndex].quantity = 0;
    }
  }

  const newSale = {
    id: uuidv4(),
    customerName,
    sellerId,
    items: cart,
    total,
    date: new Date().toISOString()
  };

  db.sales.push(newSale);
  writeDb(db);
  
  res.json({ success: true, sale: newSale });
});

// CSV Import
app.post('/api/medicines/import', (req, res) => {
  const { medicines } = req.body;
  const db = readDb();
  const newMeds = medicines.map((m: any) => ({
    id: uuidv4(),
    name: m.name,
    category: m.category,
    company: m.company,
    batchNo: m.batchNo,
    quantity: Number(m.quantity),
    price: Number(m.price),
    expiryDate: m.expiryDate
  }));
  db.medicines.push(...newMeds);
  writeDb(db);
  res.json({ success: true, count: newMeds.length });
});


// Vite initialization
async function startServer() {
  if (process.env.NODE_ENV !== "production") {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: "spa",
    });
    app.use(vite.middlewares);
  } else {
    const distPath = path.join(process.cwd(), 'dist');
    app.use(express.static(distPath));
    app.get('*all', (req, res) => {
      res.sendFile(path.join(distPath, 'index.html'));
    });
  }

  app.listen(PORT, "0.0.0.0", () => {
    console.log(`Server running on http://localhost:${PORT}`);
  });
}

startServer();
