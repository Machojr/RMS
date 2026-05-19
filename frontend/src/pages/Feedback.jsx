import React, { useEffect, useState } from 'react';
import DashboardNav from '../components/DashboardNav.jsx';

export default function Feedback() {
  const [feedbackItems, setFeedbackItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    async function fetchFeedback() {
      try {
        const response = await fetch('http://localhost/rms/backend/feedback/list.php', {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setFeedbackItems(data.feedback);
        } else {
          setError(data.error || 'Unable to load feedback.');
        }
      } catch (err) {
        setError('Unable to reach feedback service.');
      } finally {
        setLoading(false);
      }
    }

    fetchFeedback();
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
          <span>Clinical Feedback</span>
          <h2>View outcome notes and discharge summaries from receiving facilities.</h2>
        </div>

        {loading ? (
          <p className="dashboard-message">Loading feedback...</p>
        ) : error ? (
          <p className="dashboard-message dashboard-error">{error}</p>
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
        )}
      </section>
    </main>
  );
}
