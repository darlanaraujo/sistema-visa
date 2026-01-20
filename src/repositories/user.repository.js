const { hashPassword } = require('../utils/password');

class UserRepository {
  constructor() {
    this.users = [];
    this.ready = this.init();
  }

  async init() {
    const adminPasswordHash = await hashPassword('123456');

    this.users = [
      {
        id: 1,
        email: 'admin@visa.com',
        passwordHash: adminPasswordHash,
        role: 'ADMIN',
        active: true,
      },
    ];
  }

  async findByEmail(email) {
    await this.ready;
    return this.users.find(user => user.email === email) || null;
  }
}

module.exports = new UserRepository();