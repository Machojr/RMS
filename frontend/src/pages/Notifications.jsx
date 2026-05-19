import React, { useEffect, useState } from 'react';
import DashboardNav from '../components/DashboardNav.jsx';

export default function Notifications() {
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    async function fetchNotifications() {
      try {
        const response = await fetch('http://localhost/rms/backend/notifications/list.php', {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setNotifications(data.notifications);
        } else {
          setError(data.error || 'Unable to load notifications.');
        }
      } catch (err) {
        setError('Unable to reach notifications service.');
      } finally {
        setLoading(false);
      }
    }

    fetchNotifications();
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
          <span>Notifications</span>
          <h2>Audit your referral communication and recent alerts.</h2>
        </div>

        {loading ? (
          <p className="dashboard-message">Loading notifications...</p>
        ) : error ? (
          <p className="dashboard-message dashboard-error">{error}</p>
        ) : (
          <div className="grid-card notification-grid">
            {notifications.length === 0 ? (
              <p>No notifications available yet.</p>
            ) : (
              notifications.map((item) => (
                <div key={item.id} className="notification-card">
                  <div className="notification-meta">
                    <span className={`notification-type type-${item.type}`}>{item.type.toUpperCase()}</span>
                    <span>{new Date(item.sent_at).toLocaleString()}</span>
                  </div>
                  <p><strong>Subject:</strong> {item.subject}</p>
                  <p><strong>Recipient:</strong> {item.recipient_email || item.recipient_phone}</p>
                  <p><strong>Status:</strong> {item.status}</p>
                  {item.referral_id && <p><strong>Referral:</strong> #{item.referral_id} ({item.patient_name})</p>}
                </div>
              ))
            )}
          </div>
        )}
      </section>
    </main>
  );
}
