import React, { useEffect, useState } from 'react';
import DashboardNav from '../components/DashboardNav.jsx';

const sectionMeta = {
  overview: {
    title: 'Overview',
    subtitle: 'Drive referral performance with a role-specific dashboard.',
  },
  referrals: {
    title: 'Referral Management',
    subtitle: 'Track every referral from submission to closure.',
  },
  facilities: {
    title: 'Facilities Network',
    subtitle: 'Review facility capacity, tier, and regional coverage.',
  },
  feedback: {
    title: 'Clinical Feedback',
    subtitle: 'View outcome notes and discharge summaries from receiving facilities.',
  },
  notifications: {
    title: 'Notifications',
    subtitle: 'Audit your referral communication and recent alerts.',
  },
};

export default function Dashboard() {
  const [activeTab, setActiveTab] = useState('overview');
  const [summary, setSummary] = useState(null);
  const [sectionLoading, setSectionLoading] = useState(false);
  const [sectionError, setSectionError] = useState('');
  const [referrals, setReferrals] = useState([]);
  const [facilities, setFacilities] = useState([]);
  const [feedbackItems, setFeedbackItems] = useState([]);
  const [notifications, setNotifications] = useState([]);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const requestedTab = params.get('tab');
    if (requestedTab && Object.keys(sectionMeta).includes(requestedTab)) {
      setActiveTab(requestedTab);
    }
  }, []);

  useEffect(() => {
    async function fetchSummary() {
      setSectionError('');
      setSectionLoading(true);
      try {
        const response = await fetch('http://localhost/rms/backend/dashboard/summary.php', {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setSummary(data.summary);
        } else {
          setSectionError(data.error || 'Unable to load dashboard summary.');
        }
      } catch (err) {
        setSectionError('Unable to reach the dashboard service.');
      } finally {
        setSectionLoading(false);
      }
    }

    if (!summary) {
      fetchSummary();
    }
  }, [summary]);

  useEffect(() => {
    async function fetchReferrals() {
      setSectionError('');
      setSectionLoading(true);
      try {
        const response = await fetch('http://localhost/rms/backend/referrals/list.php', {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setReferrals(data.referrals);
        } else {
          setSectionError(data.error || 'Unable to load referrals.');
        }
      } catch (err) {
        setSectionError('Unable to reach referral service.');
      } finally {
        setSectionLoading(false);
      }
    }

    async function fetchFacilities() {
      setSectionError('');
      setSectionLoading(true);
      try {
        const response = await fetch('http://localhost/rms/backend/facilities/manage_facilities.php', {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setFacilities(data.facilities);
        } else {
          setSectionError(data.error || 'Unable to load facilities.');
        }
      } catch (err) {
        setSectionError('Unable to reach facilities service.');
      } finally {
        setSectionLoading(false);
      }
    }

    async function fetchFeedback() {
      setSectionError('');
      setSectionLoading(true);
      try {
        const response = await fetch('http://localhost/rms/backend/feedback/list.php', {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setFeedbackItems(data.feedback);
        } else {
          setSectionError(data.error || 'Unable to load feedback.');
        }
      } catch (err) {
        setSectionError('Unable to reach feedback service.');
      } finally {
        setSectionLoading(false);
      }
    }

    async function fetchNotifications() {
      setSectionError('');
      setSectionLoading(true);
      try {
        const response = await fetch('http://localhost/rms/backend/notifications/list.php', {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setNotifications(data.notifications);
        } else {
          setSectionError(data.error || 'Unable to load notifications.');
        }
      } catch (err) {
        setSectionError('Unable to reach notifications service.');
      } finally {
        setSectionLoading(false);
      }
    }

    if (activeTab === 'referrals' && referrals.length === 0) {
      fetchReferrals();
    }

    if (activeTab === 'facilities' && facilities.length === 0) {
      fetchFacilities();
    }

    if (activeTab === 'feedback' && feedbackItems.length === 0) {
      fetchFeedback();
    }

    if (activeTab === 'notifications' && notifications.length === 0) {
      fetchNotifications();
    }
  }, [activeTab, referrals.length, facilities.length, feedbackItems.length, notifications.length]);

  const signOut = async (event) => {
    event.preventDefault();
    await fetch('http://localhost/rms/backend/auth/logout.php', {
      method: 'POST',
      credentials: 'include',
    });
    window.location.href = '/';
  };

  return (
    <main className="container dashboard-page">
      <section className="section dashboard-section">
        <div className="nav-top">
          <div>
            <DashboardNav activeTab={activeTab} onChange={setActiveTab} />
          </div>
          <a href="/" className="button-secondary" onClick={signOut}>Sign Out</a>
        </div>

        <div className="section-title">
          <span>{sectionMeta[activeTab].title}</span>
          <h2>{sectionMeta[activeTab].subtitle}</h2>
        </div>

        {activeTab === 'overview' && (
          sectionLoading ? (
            <p className="dashboard-message">Loading overview...</p>
          ) : sectionError ? (
            <p className="dashboard-message dashboard-error">{sectionError}</p>
          ) : (
            <div className="dashboard-grid">
              <div className="dashboard-card highlight-card">
                <span>Total Referrals</span>
                <strong>{summary?.referrals ?? 0}</strong>
              </div>
              <div className="dashboard-card">
                <span>Pending Referrals</span>
                <strong>{summary?.pending_referrals ?? 0}</strong>
              </div>
              <div className="dashboard-card">
                <span>Facilities</span>
                <strong>{summary?.facilities ?? 0}</strong>
              </div>
              <div className="dashboard-card">
                <span>Active Users</span>
                <strong>{summary?.active_users ?? 0}</strong>
              </div>
            </div>
          )
        )}

        {activeTab === 'referrals' && (
          sectionLoading ? (
            <p className="dashboard-message">Loading referrals...</p>
          ) : sectionError ? (
            <p className="dashboard-message dashboard-error">{sectionError}</p>
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
          )
        )}

        {activeTab === 'facilities' && (
          sectionLoading ? (
            <p className="dashboard-message">Loading facilities...</p>
          ) : sectionError ? (
            <p className="dashboard-message dashboard-error">{sectionError}</p>
          ) : (
            <div className="grid-card facility-grid">
              {facilities.length === 0 ? (
                <p>No facilities available yet.</p>
              ) : (
                facilities.map((facility) => (
                  <div key={facility.id} className="facility-card">
                    <div className="facility-header">
                      <h3>{facility.name}</h3>
                      <span className="tier-tag">{facility.tier}</span>
                    </div>
                    <p>{facility.region}, {facility.district}</p>
                    <p>{facility.address}</p>
                    <p>{facility.phone} · {facility.email}</p>
                    <p><strong>Capacity:</strong> {facility.capacity}</p>
                  </div>
                ))
              )}
            </div>
          )
        )}

        {activeTab === 'feedback' && (
          sectionLoading ? (
            <p className="dashboard-message">Loading feedback...</p>
          ) : sectionError ? (
            <p className="dashboard-message dashboard-error">{sectionError}</p>
          ) : (
            <div className="grid-card feedback-grid">
              {feedbackItems.length === 0 ? (
                <p>No feedback records available yet.</p>
              ) : (
                feedbackItems.map((item) => (
                  <div key={item.id} className="feedback-card">
                    <div className="feedback-meta">
                      <strong>Referral #{item.referral_id}</strong>
                      <span>{new Date(item.sent_at).toLocaleDateString()}</span>
                    </div>
                    <p><strong>Patient:</strong> {item.patient_name}</p>
                    <p><strong>Sent by:</strong> {item.sent_by}</p>
                    <p><strong>Outcome:</strong> {item.clinical_outcome}</p>
                    <p><strong>Treatment:</strong> {item.treatment_given}</p>
                    <p><strong>Summary:</strong> {item.discharge_summary}</p>
                  </div>
                ))
              )}
            </div>
          )
        )}

        {activeTab === 'notifications' && (
          sectionLoading ? (
            <p className="dashboard-message">Loading notifications...</p>
          ) : sectionError ? (
            <p className="dashboard-message dashboard-error">{sectionError}</p>
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
          )
        )}
      </section>
    </main>
  );
}
