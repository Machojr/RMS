import React, { useState } from 'react';
import { apiUrl } from '../config/api.js';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setLoading(true);
    setMessage('');

    try {
      const response = await fetch(apiUrl('/auth/login.php'), {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        setMessage('Login successful. Redirecting...');
        // Redirect kulingana na role ya user
        const role = data.user?.role;
        if (role === 'admin' || role === 'super_admin') {
          window.location.href = '/admin';
        } else {
          window.location.href = '/dashboard-landing';
        }
      } else {
        setMessage(data.message || 'Login failed. Please try again.');
      }
    } catch (error) {
      setMessage('Unable to reach the login service.');
    }

    setLoading(false);
  };

  return (
    <main className="login-page">
      <section className="section login-section">
        <div className="section-title">
          <span>Secure access</span>
          <h2>Sign in to your RMS portal.</h2>
        </div>

        <div className="form-card login-card-grid">
          <div className="login-info">
            <span className="badge">Referral network platform</span>
            <h3>Streamline patient referrals with confidence.</h3>
            <p>
              Log in to access referrals, facility updates, and admin oversight tools in a modern, secure interface designed for Tanzanian public healthcare.
            </p>

            <div className="login-stats">
              <div>
                <strong>5</strong>
                <span>system tiers</span>
              </div>
              <div>
                <strong>99.9%</strong>
                <span>uptime reliability</span>
              </div>
              <div>
                <strong>24/7</strong>
                <span>secure access</span>
              </div>
            </div>
          </div>

          <form onSubmit={handleSubmit} className="login-form">
            <label className="form-field">
              <span>Email</span>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                placeholder="you@example.com"
                className="form-input"
              />
            </label>

            <label className="form-field">
              <span>Password</span>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                placeholder="Enter your password"
                className="form-input"
              />
            </label>

            {message && <p className="form-message">{message}</p>}

            <button type="submit" className="button" disabled={loading}>
              {loading ? 'Signing in...' : 'Sign in'}
            </button>
          </form>
        </div>
      </section>
    </main>
  );
}
