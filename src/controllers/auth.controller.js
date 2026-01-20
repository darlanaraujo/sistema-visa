const AuthService = require('../services/auth.service');

class AuthController {
  async login(req, res, next) {
    try {
      const { email, password } = req.body;

      const result = await AuthService.login({ email, password });

      return res.status(200).json(result);
    } catch (err) {
      return next(err);
    }
  }
}

module.exports = new AuthController();