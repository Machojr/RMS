import React, { useEffect, useState } from 'react';
import DashboardNav from '../components/DashboardNav.jsx';
import { apiUrl } from '../config/api.js';

const sectionMeta = {
  overview: {
    title: 'Central Dashboard',
    subtitle: 'Access referrals, facilities, feedback and alerts from one page.',
  },
  referrals: {
    title: 'Referral Management',
    subtitle: 'Track every referral from submission to closure.',
  },
  patients: {
    title: 'Patient Management',
    subtitle: 'View patient profiles linked to referral activity.',
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
  const [contentVisible, setContentVisible] = useState(true);
  const [summary, setSummary] = useState(null);
  const [loadingState, setLoadingState] = useState({
    overview: false,
    referrals: false,
    patients: false,
    facilities: false,
    feedback: false,
    notifications: false,
  });
  const [sectionErrors, setSectionErrors] = useState({
    overview: '',
    referrals: '',
    patients: '',
    facilities: '',
    feedback: '',
    notifications: '',
  });
  const [referrals, setReferrals] = useState([]);
  const [patients, setPatients] = useState([]);
  const [facilities, setFacilities] = useState([]);
  const [feedbackItems, setFeedbackItems] = useState([]);
  const [notifications, setNotifications] = useState([]);
  const [feedbackForm, setFeedbackForm] = useState({
    referral_id: '',
    clinical_outcome: '',
    treatment_given: '',
    discharge_summary: '',
    follow_up_instructions: '',
  });
  const [feedbackMessage, setFeedbackMessage] = useState('');
  const [submittingFeedback, setSubmittingFeedback] = useState(false);
  const [actionProcessing, setActionProcessing] = useState(null);
  const [actionError, setActionError] = useState('');
  const [showReferralForm, setShowReferralForm] = useState(false);
  const [referralForm, setReferralForm] = useState({
    patient_first_name: '',
    patient_last_name: '',
    gender: 'male',
    date_of_birth: '',
    phone: '',
    address: '',
    national_id: '',
    receiving_facility_id: '',
    urgency: 'routine',
    clinical_reason: '',
    clinical_findings: '',
    requested_services: '',
  });
  const [referralMessage, setReferralMessage] = useState('');
  const [submittingReferral, setSubmittingReferral] = useState(false);
  const [selectedReferral, setSelectedReferral] = useState(null);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const requestedTab = params.get('tab');
    if (requestedTab && Object.keys(sectionMeta).includes(requestedTab)) {
      setActiveTab(requestedTab);
    }
  }, []);

  useEffect(() => {
    if (!selectedReferral) {
      return undefined;
    }

    const handleKeyDown = (event) => {
      if (event.key === 'Escape') {
        setSelectedReferral(null);
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [selectedReferral]);

  useEffect(() => {
    async function fetchSummary() {
      setSectionErrors((prev) => ({ ...prev, overview: '' }));
      setLoadingState((prev) => ({ ...prev, overview: true }));
      try {
        const response = await fetch(apiUrl('/dashboard/summary.php'), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setSummary(data.summary);
        } else {
          setSectionErrors((prev) => ({
            ...prev,
            overview: data.error || 'Unable to load dashboard summary.',
          }));
        }
      } catch (err) {
        setSectionErrors((prev) => ({
          ...prev,
          overview: 'Unable to reach the dashboard service.',
        }));
      } finally {
        setLoadingState((prev) => ({ ...prev, overview: false }));
      }
    }

    if (!summary) {
      fetchSummary();
    }
  }, [summary]);

  useEffect(() => {
    async function fetchReferrals() {
      setSectionErrors((prev) => ({ ...prev, referrals: '' }));
      setLoadingState((prev) => ({ ...prev, referrals: true }));
      try {
        const response = await fetch(apiUrl('/referrals/list.php'), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setReferrals(data.referrals);
        } else {
          setSectionErrors((prev) => ({
            ...prev,
            referrals: data.error || 'Unable to load referrals.',
          }));
        }
      } catch (err) {
        setSectionErrors((prev) => ({
          ...prev,
          referrals: 'Unable to reach referral service.',
        }));
      } finally {
        setLoadingState((prev) => ({ ...prev, referrals: false }));
      }
    }

    async function fetchFacilities() {
      setSectionErrors((prev) => ({ ...prev, facilities: '' }));
      setLoadingState((prev) => ({ ...prev, facilities: true }));
      try {
        const response = await fetch(apiUrl('/facilities/manage_facilities.php'), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setFacilities(data.facilities);
        } else {
          setSectionErrors((prev) => ({
            ...prev,
            facilities: data.error || 'Unable to load facilities.',
          }));
        }
      } catch (err) {
        setSectionErrors((prev) => ({
          ...prev,
          facilities: 'Unable to reach facilities service.',
        }));
      } finally {
        setLoadingState((prev) => ({ ...prev, facilities: false }));
      }
    }

    async function fetchPatients() {
      setSectionErrors((prev) => ({ ...prev, patients: '' }));
      setLoadingState((prev) => ({ ...prev, patients: true }));
      try {
        const response = await fetch(apiUrl('/patients/list.php'), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setPatients(data.patients);
        } else {
          setSectionErrors((prev) => ({
            ...prev,
            patients: data.error || 'Unable to load patients.',
          }));
        }
      } catch (err) {
        setSectionErrors((prev) => ({
          ...prev,
          patients: 'Unable to reach patient service.',
        }));
      } finally {
        setLoadingState((prev) => ({ ...prev, patients: false }));
      }
    }

    async function fetchFeedback() {
      setSectionErrors((prev) => ({ ...prev, feedback: '' }));
      setLoadingState((prev) => ({ ...prev, feedback: true }));
      try {
        const response = await fetch(apiUrl('/feedback/list.php'), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setFeedbackItems(data.feedback);
        } else {
          setSectionErrors((prev) => ({
            ...prev,
            feedback: data.error || 'Unable to load feedback.',
          }));
        }
      } catch (err) {
        setSectionErrors((prev) => ({
          ...prev,
          feedback: 'Unable to reach feedback service.',
        }));
      } finally {
        setLoadingState((prev) => ({ ...prev, feedback: false }));
      }
    }

    async function fetchNotifications() {
      setSectionErrors((prev) => ({ ...prev, notifications: '' }));
      setLoadingState((prev) => ({ ...prev, notifications: true }));
      try {
        const response = await fetch(apiUrl('/notifications/list.php'), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setNotifications(data.notifications);
        } else {
          setSectionErrors((prev) => ({
            ...prev,
            notifications: data.error || 'Unable to load notifications.',
          }));
        }
      } catch (err) {
        setSectionErrors((prev) => ({
          ...prev,
          notifications: 'Unable to reach notifications service.',
        }));
      } finally {
        setLoadingState((prev) => ({ ...prev, notifications: false }));
      }
    }

    if (activeTab === 'referrals' && referrals.length === 0) {
      fetchReferrals();
    }

    if (activeTab === 'patients' && patients.length === 0) {
      fetchPatients();
    }

    if (activeTab === 'facilities' && facilities.length === 0) {
      fetchFacilities();
    }

    if (activeTab === 'referrals' && facilities.length === 0) {
      fetchFacilities();
    }

    if (activeTab === 'feedback' && feedbackItems.length === 0) {
      fetchFeedback();
    }

    if (activeTab === 'feedback' && referrals.length === 0 && summary?.user?.role === 'admin') {
      fetchReferrals();
    }

    if (activeTab === 'notifications' && notifications.length === 0) {
      fetchNotifications();
    }
  }, [activeTab, referrals.length, patients.length, facilities.length, feedbackItems.length, notifications.length]);
  
  const submitReferral = async (event) => {
    event.preventDefault();
    setSubmittingReferral(true);
    setReferralMessage('');

    try {
      const response = await fetch(apiUrl('/referrals/create.php'), {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(referralForm),
      });

      const data = await response.json();
      if (response.ok && data.success) {
        setReferralMessage('Referral created successfully. Refreshing list...');
        setShowReferralForm(false);
        setReferralForm({
          patient_first_name: '',
          patient_last_name: '',
          gender: 'male',
          date_of_birth: '',
          phone: '',
          address: '',
          national_id: '',
          receiving_facility_id: '',
          urgency: 'routine',
          clinical_reason: '',
          clinical_findings: '',
          requested_services: '',
        });
        setReferrals([]);
      } else {
        setReferralMessage(data.error || 'Unable to create referral.');
      }
    } catch (err) {
      setReferralMessage('Unable to reach referral service.');
    }

    setSubmittingReferral(false);
  };

  const updateReferralStatus = async (referralId, status) => {
    setActionProcessing(referralId);
    setActionError('');
    try {
      const response = await fetch(apiUrl('/referrals/update_status.php'), {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ referral_id: referralId, status }),
      });
      const data = await response.json();
      if (response.ok && data.success) {
        setReferrals([]); // trigger refresh
      } else {
        setActionError(data.error || data.message || 'Unable to update status');
      }
    } catch (err) {
      setActionError('Unable to reach update service.');
    }
    setActionProcessing(null);
  };

  const submitFeedback = async (event) => {
    event.preventDefault();
    setSubmittingFeedback(true);
    setFeedbackMessage('');

    try {
      const response = await fetch(apiUrl('/feedback/create.php'), {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(feedbackForm),
      });
      const data = await response.json();
      if (response.ok && data.success) {
        setFeedbackMessage('Feedback submitted successfully.');
        setFeedbackForm((prev) => ({
          ...prev,
          clinical_outcome: '',
          treatment_given: '',
          discharge_summary: '',
          follow_up_instructions: '',
        }));
        setFeedbackItems([]);
      } else {
        setFeedbackMessage(data.error || 'Unable to submit feedback.');
      }
    } catch (err) {
      setFeedbackMessage('Unable to reach feedback service.');
    }

    setSubmittingFeedback(false);
  };

  useEffect(() => {
    setContentVisible(false);
    const timeout = window.setTimeout(() => {
      setContentVisible(true);
    }, 120);
    return () => window.clearTimeout(timeout);
  }, [activeTab]);

  useEffect(() => {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', activeTab);
    window.history.replaceState(null, '', url.toString());
  }, [activeTab]);

  const signOut = async (event) => {
    event.preventDefault();
    await fetch(apiUrl('/auth/logout.php'), {
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

        <div className={`dashboard-section-content ${contentVisible ? 'visible' : 'hidden'}`}>

        {activeTab === 'overview' && (
          loadingState.overview ? (
            <p className="dashboard-message">Loading overview...</p>
          ) : sectionErrors.overview ? (
            <p className="dashboard-message dashboard-error">{sectionErrors.overview}</p>
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
          loadingState.referrals ? (
            <p className="dashboard-message">Loading referrals...</p>
          ) : sectionErrors.referrals ? (
            <p className="dashboard-message dashboard-error">{sectionErrors.referrals}</p>
          ) : (
            <>
              {summary?.user?.role === 'co' && (
                <div className="form-card" style={{ marginBottom: '1.75rem' }}>
                  <div className="nav-top" style={{ alignItems: 'center', justifyContent: 'space-between', marginBottom: '1rem' }}>
                    <div>
                      <h3 style={{ margin: 0 }}>Create a new referral</h3>
                      <p style={{ margin: '0.5rem 0 0', color: '#475569' }}>
                        Submit a new referral to another facility in the network.
                      </p>
                    </div>
                    <button
                      type="button"
                      className="button button-secondary"
                      onClick={() => {
                        setShowReferralForm((prev) => !prev);
                        setReferralMessage('');
                      }}
                    >
                      {showReferralForm ? 'Hide form' : 'New referral'}
                    </button>
                  </div>

                  {showReferralForm && (
                    <form onSubmit={submitReferral} className="login-form">
                      <div className="form-field">
                        <span>Patient First Name</span>
                        <input
                          type="text"
                          name="patient_first_name"
                          value={referralForm.patient_first_name}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          required
                          className="form-input"
                        />
                      </div>
                      <div className="form-field">
                        <span>Patient Last Name</span>
                        <input
                          type="text"
                          name="patient_last_name"
                          value={referralForm.patient_last_name}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          required
                          className="form-input"
                        />
                      </div>
                      <div className="form-field">
                        <span>Gender</span>
                        <select
                          name="gender"
                          value={referralForm.gender}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          className="form-input"
                        >
                          <option value="male">Male</option>
                          <option value="female">Female</option>
                          <option value="other">Other</option>
                        </select>
                      </div>
                      <div className="form-field">
                        <span>Date of birth</span>
                        <input
                          type="date"
                          name="date_of_birth"
                          value={referralForm.date_of_birth}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          className="form-input"
                        />
                      </div>
                      <div className="form-field">
                        <span>Phone</span>
                        <input
                          type="tel"
                          name="phone"
                          value={referralForm.phone}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          className="form-input"
                        />
                      </div>
                      <div className="form-field">
                        <span>Receiving facility</span>
                        <select
                          name="receiving_facility_id"
                          value={referralForm.receiving_facility_id}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          required
                          className="form-input"
                        >
                          <option value="">Choose facility</option>
                          {facilities
                            .filter((facility) => facility.id !== summary?.user?.facility_id)
                            .map((facility) => (
                              <option key={facility.id} value={facility.id}>
                                {facility.name} ({facility.region})
                              </option>
                            ))}
                        </select>
                      </div>
                      <div className="form-field">
                        <span>Urgency</span>
                        <select
                          name="urgency"
                          value={referralForm.urgency}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          className="form-input"
                        >
                          <option value="routine">Routine</option>
                          <option value="urgent">Urgent</option>
                          <option value="emergency">Emergency</option>
                        </select>
                      </div>
                      <div className="form-field">
                        <span>Patient condition / clinical reason</span>
                        <textarea
                          name="clinical_reason"
                          value={referralForm.clinical_reason}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          required
                          className="form-input"
                          rows="3"
                        />
                      </div>
                      <div className="form-field">
                        <span>Clinical findings</span>
                        <textarea
                          name="clinical_findings"
                          value={referralForm.clinical_findings}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          className="form-input"
                          rows="3"
                        />
                      </div>
                      <div className="form-field">
                        <span>Requested services</span>
                        <textarea
                          name="requested_services"
                          value={referralForm.requested_services}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          className="form-input"
                          rows="2"
                        />
                      </div>
                      {referralMessage && (
                        <p className="dashboard-message" style={{ marginBottom: '1rem' }}>
                          {referralMessage}
                        </p>
                      )}
                      <button type="submit" className="button" disabled={submittingReferral}>
                        {submittingReferral ? 'Submitting...' : 'Submit Referral'}
                      </button>
                    </form>
                  )}
                </div>
              )}
              <div className="table-card">
                {referrals.length === 0 ? (
                  <p>No referrals available yet.</p>
                ) : (
                  <table className="data-table">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Condition</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>From / To</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {referrals.map((item) => (
                        <tr key={item.id}>
                          <td>{item.id}</td>
                          <td>{item.patient_name}</td>
                          <td className="referral-summary-cell">
                            <p>{item.clinical_reason || 'No condition details recorded.'}</p>
                            <button
                              type="button"
                              className="table-icon-button"
                              onClick={() => setSelectedReferral(item)}
                            >
                              <i className="fa-solid fa-eye" aria-hidden="true"></i>
                              View details
                            </button>
                          </td>
                          <td>{item.urgency}</td>
                          <td className={`status-pill status-${item.status}`}>
                            {item.status}
                          </td>
                          <td>
                            <span className="facility-route">
                              <span>{item.referring_facility}</span>
                              <i className="fa-solid fa-arrow-right" aria-hidden="true"></i>
                              <span>{item.receiving_facility}</span>
                            </span>
                          </td>
                          <td>{new Date(item.created_at).toLocaleDateString()}</td>
                          <td>
                            {summary?.user?.role === 'admin' && (
                              <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                                {item.status === 'pending' && (
                                  <>
                                    <button className="button" disabled={actionProcessing === item.id} onClick={() => updateReferralStatus(item.id, 'accepted')}>Accept</button>
                                    <button className="button button-secondary" disabled={actionProcessing === item.id} onClick={() => {
                                      const reason = window.prompt('Rejection reason (optional):');
                                      updateReferralStatus(item.id, 'rejected');
                                    }}>Reject</button>
                                  </>
                                )}
                                {item.status === 'accepted' && (
                                  <button className="button" disabled={actionProcessing === item.id} onClick={() => updateReferralStatus(item.id, 'in_progress')}>Start</button>
                                )}
                                {item.status === 'in_progress' && (
                                  <button className="button" disabled={actionProcessing === item.id} onClick={() => updateReferralStatus(item.id, 'completed')}>Complete</button>
                                )}
                              </div>
                            )}
                            {actionError && actionProcessing === null && <p className="dashboard-message dashboard-error">{actionError}</p>}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </div>
            </>
          )
        )}

        {activeTab === 'patients' && (
          loadingState.patients ? (
            <p className="dashboard-message">Loading patients...</p>
          ) : sectionErrors.patients ? (
            <p className="dashboard-message dashboard-error">{sectionErrors.patients}</p>
          ) : (
            <div className="table-card">
              {patients.length === 0 ? (
                <p>No patient records available yet.</p>
              ) : (
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Patient</th>
                      <th>Gender</th>
                      <th>Date of Birth</th>
                      <th>Phone</th>
                      <th>Referrals</th>
                      <th>Last Referral</th>
                    </tr>
                  </thead>
                  <tbody>
                    {patients.map((patient) => (
                      <tr key={patient.id}>
                        <td>{patient.id}</td>
                        <td>
                          <strong>{patient.patient_name}</strong>
                          {patient.national_id && (
                            <span style={{ display: 'block', color: '#64748b', fontSize: '0.9rem' }}>
                              ID: {patient.national_id}
                            </span>
                          )}
                        </td>
                        <td>{patient.gender || 'Not set'}</td>
                        <td>{patient.date_of_birth || 'Not set'}</td>
                        <td>{patient.phone || 'Not set'}</td>
                        <td>{patient.referral_count}</td>
                        <td>
                          {patient.last_referral_at
                            ? new Date(patient.last_referral_at).toLocaleDateString()
                            : 'No referral'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          )
        )}

        {activeTab === 'facilities' && (
          loadingState.facilities ? (
            <p className="dashboard-message">Loading facilities...</p>
          ) : sectionErrors.facilities ? (
            <p className="dashboard-message dashboard-error">{sectionErrors.facilities}</p>
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
          loadingState.feedback ? (
            <p className="dashboard-message">Loading feedback...</p>
          ) : sectionErrors.feedback ? (
            <p className="dashboard-message dashboard-error">{sectionErrors.feedback}</p>
          ) : (
            <div className="grid-card feedback-grid">
              {summary?.user?.role === 'admin' && (
                <div className="feedback-card" style={{ marginBottom: '1.5rem' }}>
                  <h3>Submit Clinical Feedback</h3>
                  <form onSubmit={submitFeedback}>
                    <div className="form-field">
                      <span>Referral</span>
                      <select
                        name="referral_id"
                        value={feedbackForm.referral_id}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, referral_id: e.target.value }))}
                        className="form-input"
                        required
                      >
                        <option value="">Select a referral</option>
                        {referrals.map((ref) => (
                          <option key={ref.id} value={ref.id}>
                            #{ref.id} - {ref.patient_name} to {ref.receiving_facility} ({ref.status})
                          </option>
                        ))}
                      </select>
                    </div>
                    <div className="form-field">
                      <span>Clinical outcome</span>
                      <textarea
                        name="clinical_outcome"
                        value={feedbackForm.clinical_outcome}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, clinical_outcome: e.target.value }))}
                        className="form-input"
                        rows="2"
                        required
                      />
                    </div>
                    <div className="form-field">
                      <span>Treatment given</span>
                      <textarea
                        name="treatment_given"
                        value={feedbackForm.treatment_given}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, treatment_given: e.target.value }))}
                        className="form-input"
                        rows="2"
                      />
                    </div>
                    <div className="form-field">
                      <span>Discharge summary</span>
                      <textarea
                        name="discharge_summary"
                        value={feedbackForm.discharge_summary}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, discharge_summary: e.target.value }))}
                        className="form-input"
                        rows="2"
                      />
                    </div>
                    <div className="form-field">
                      <span>Follow-up instructions</span>
                      <textarea
                        name="follow_up_instructions"
                        value={feedbackForm.follow_up_instructions}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, follow_up_instructions: e.target.value }))}
                        className="form-input"
                        rows="2"
                      />
                    </div>
                    {feedbackMessage && (
                      <p className="dashboard-message" style={{ marginBottom: '1rem' }}>
                        {feedbackMessage}
                      </p>
                    )}
                    <button type="submit" className="button" disabled={submittingFeedback}>
                      {submittingFeedback ? 'Sending feedback...' : 'Send feedback'}
                    </button>
                  </form>
                </div>
              )}
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
          loadingState.notifications ? (
            <p className="dashboard-message">Loading notifications...</p>
          ) : sectionErrors.notifications ? (
            <p className="dashboard-message dashboard-error">{sectionErrors.notifications}</p>
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
        </div>
      </section>

      {selectedReferral && (
        <div
          className="modal-backdrop"
          role="presentation"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) {
              setSelectedReferral(null);
            }
          }}
        >
          <section
            className="referral-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="referral-modal-title"
          >
            <div className="modal-header">
              <div>
                <span>Referral #{selectedReferral.id}</span>
                <h3 id="referral-modal-title">{selectedReferral.patient_name}</h3>
              </div>
              <button
                type="button"
                className="modal-close-button"
                aria-label="Close referral details"
                onClick={() => setSelectedReferral(null)}
              >
                <i className="fa-solid fa-xmark" aria-hidden="true"></i>
              </button>
            </div>

            <div className="modal-meta-grid">
              <div>
                <span>Status</span>
                <strong className={`status-pill status-${selectedReferral.status}`}>
                  {selectedReferral.status}
                </strong>
              </div>
              <div>
                <span>Urgency</span>
                <strong>{selectedReferral.urgency}</strong>
              </div>
              <div>
                <span>Submitted</span>
                <strong>{new Date(selectedReferral.created_at).toLocaleDateString()}</strong>
              </div>
            </div>

            <div className="modal-route">
              <span>{selectedReferral.referring_facility}</span>
              <i className="fa-solid fa-arrow-right" aria-hidden="true"></i>
              <span>{selectedReferral.receiving_facility}</span>
            </div>

            <div className="modal-detail-stack">
              <div className="referral-detail-block">
                <span>Patient condition / clinical reason</span>
                <p>{selectedReferral.clinical_reason || 'Not provided'}</p>
              </div>
              <div className="referral-detail-block">
                <span>Clinical findings</span>
                <p>{selectedReferral.clinical_findings || 'Not provided'}</p>
              </div>
              <div className="referral-detail-block">
                <span>Requested services</span>
                <p>{selectedReferral.requested_services || 'Not provided'}</p>
              </div>
            </div>
          </section>
        </div>
      )}
    </main>
  );
}
