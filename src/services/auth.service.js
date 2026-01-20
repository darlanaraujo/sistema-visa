const UserRepository = require('../repositories/user.repository');
const { comparePassword } = require('../utils/password');
const AppError = require('../errors/AppError');

class AuthService {
  async login({ email, password }) {
    if (!email || !password) {
      throw new AppError('Email e senha são obrigatórios', 400);
    }

    const user = await UserRepository.findByEmail(email);

    if (!user) {
      throw new AppError('Credenciais inválidas', 401);
    }

    if (!user.active) {
      throw new AppError('Usuário inativo', 403);
    }

    const ok = await comparePassword(password, user.passwordHash);

    if (!ok) {
      throw new AppError('Credenciais inválidas', 401);
    }

    return {
      message: 'Login realizado com sucesso',
      user: {
        id: user.id,
        email: user.email,
        role: user.role,
      },
    };
  }
}

module.exports = new AuthService();