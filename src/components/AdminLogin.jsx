import { useState } from 'react';
import logo from '../assets/logo.webp';
import './AdminLogin.css';

const USERS = {
  admin: { password: 'admin123', role: 'admin' },
  hr: { password: 'hr123', role: 'hr' },
  security: { password: 'security123', role: 'security' },
};

export default function AdminLogin() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  const handleLogin = (e) => {
    e.preventDefault();
    const entry = USERS[username.trim()];
    if (!entry || entry.password !== password) {
      setError('Invalid credentials');
      return;
    }
    localStorage.setItem('adminRole', entry.role);
    window.location.hash = '#/admin';
  };

  return (
    <div className="login-page">
      <div className="login-card">
        <img src={logo} alt="Tap-N-Eat Logo" className="login-logo" />
        <div className="login-header">
          <h1>Welcome back</h1>
          <p>Secure access for Admin, HR, and Security panels</p>
        </div>

        <div className="login-credentials">
          <span className="pill" title="Click to copy">admin / admin123</span>
          <span className="pill">hr / hr123</span>
          <span className="pill">security / security123</span>
        </div>

        <form onSubmit={handleLogin} className="login-form">
          <div className="form-group">
            <label>Username</label>
            <input
              className="login-input"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              placeholder="Enter your username"
              autoComplete="username"
            />
          </div>
          <div className="form-group">
            <label>Password</label>
            <input
              className="login-input"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Enter your password"
              autoComplete="current-password"
            />
          </div>
          {error && <div className="status-box error">{error}</div>}
          <div className="login-actions">
            <button type="submit" className="login-btn">
              Sign in to Console
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
