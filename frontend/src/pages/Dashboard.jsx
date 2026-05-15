import React, { useEffect, useState } from 'react';

export default function Dashboard() {
  const [summary, setSummary] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchSummary() {
      try {
        const response = await fetch('http://localhost/rms/backend/dashboard/summary.php', {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setSummary(data.summary);
        } else {
          setError(data.error || 'Unable to load dashboard data.');
        }
      } catch (err) {
        setError('Unable to reach the dashboard service.');
      } finally {
        setLoading(false);
      }
    }

    fetchSummary();
  }, []);

  return (
    <main className="container">
      <section className="section dashboard-section">
        <div className="section-title">
          <span>Dashboard</span>
          <h2>Welcome back to your referral overview.</h2>
        </div>

        {loading ? (
          <p className="dashboard-message">Loading dashboard...</p>
        ) : error ? (
          <p className="dashboard-message dashboard-error">{error}</p>
        ) : (
          <div className="dashboard-grid">
            <div className="dashboard-card">
              <span>Referrals</span>
              <strong>{summary.referrals}</strong>
            </div>
            <div className="dashboard-card">
              <span>Pending</span>
              <strong>{summary.pending_referrals}</strong>
            </div>
            <div className="dashboard-card">
              <span>Facilities</span>
              <strong>{summary.facilities}</strong>
            </div>
            <div className="dashboard-card">
              <span>Active Users</span>
              <strong>{summary.active_users}</strong>
            </div>
          </div>
        )}
      </section>
    </main>
  );
}
