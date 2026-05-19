import React, { useEffect, useState } from 'react';
import DashboardNav from '../components/DashboardNav.jsx';

export default function Referrals() {
  const [referrals, setReferrals] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    async function fetchReferrals() {
      try {
        const response = await fetch('http://localhost/rms/backend/referrals/list.php', {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setReferrals(data.referrals);
        } else {
          setError(data.error || 'Unable to load referrals.');
        }
      } catch (err) {
        setError('Unable to reach referral service.');
      } finally {
        setLoading(false);
      }
    }

    fetchReferrals();
  }, []);

  return (
    <main className="container dashboard-page">
      <section className="section dashboard-section">
        <div className="nav-top">
          <div>
            <DashboardNav />
          </div>
          <a href="/" className="button-secondary" onClick={(e) => {
            e.preventDefault();
            fetch('http://localhost/rms/backend/auth/logout.php', {
              method: 'POST',
              credentials: 'include',
            }).then(() => {
              window.location.href = '/';
            });
          }}>Sign Out</a>
        </div>
        <div className="section-title">
          <span>Referral Management</span>
          <h2>Track every referral from submission to closure.</h2>
        </div>

        {loading ? (
          <p className="dashboard-message">Loading referrals...</p>
        ) : error ? (
          <p className="dashboard-message dashboard-error">{error}</p>
        ) : (
          <div className="table-card">
            {referrals.length === 0 ? (
              <p>No referrals available yet.</p>
            ) : (
              <table className="data-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Urgency</th>
                    <th>Status</th>
                    <th>From / To</th>
                    <th>Submitted</th>
                  </tr>
                </thead>
                <tbody>
                  {referrals.map((item) => (
                    <tr key={item.id}>
                      <td>{item.id}</td>
                      <td>{item.patient_name}</td>
                      <td>{item.urgency}</td>
                      <td className={`status-pill status-${item.status}`}>
                        {item.status}
                      </td>
                      <td>
                        {item.referring_facility} → {item.receiving_facility}
                      </td>
                      <td>{new Date(item.created_at).toLocaleDateString()}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        )}
      </section>
    </main>
  );
}
