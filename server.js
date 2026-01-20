const express = require('express');
const authRoutes = require('./src/routes/auth.routes');
const errorMiddleware = require('./src/middlewares/error.middleware');

const app = express();

app.use(express.json());
app.use(authRoutes);

// precisa ser o último app.use
app.use(errorMiddleware);

const PORT = 3000;

app.listen(PORT, () => {
  console.log(`Servidor rodando em http://localhost:${PORT}`);
});