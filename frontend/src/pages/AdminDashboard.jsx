import React, { useEffect, useMemo, useState } from 'react';
import AdminNav from '../components/AdminNav.jsx';
import { apiUrl } from '../config/api.js';

const sectionMeta = {
  overview: {
    title: 'System Administration',
    subtitle: 'Manage users and monitor referral activity across the RMS network.',
  },
  users: {
    title: 'User Management',
    subtitle: 'Add, update, and deactivate system users by role and facility.',
  },
  facilities: {
    title: 'Facilities Network',
    subtitle: 'View all registered health facilities across Tanzania.',
  },
};

const emptyUserForm = {
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  role: 'co',
  facility_id: '',
  phone: '',
  license_number: '',
  department_id: '',
};

const formatStatus = (referral) => {
  if (referral.status === 'pending' && referral.doctor_decision === 'accepted') return 'accepted';
  if (referral.status === 'pending' && referral.doctor_decision === 'rejected') return 'rejected';
  return referral.status;
};

export default function AdminDashboard() {
  const initialTab = new URLSearchParams(window.location.search).get('tab') || 'overview';
  const [activeTab, setActiveTab] = useState(sectionMeta[initialTab] ? initialTab : 'overview');
  const [contentVisible, setContentVisible] = useState(true);
  const [users, setUsers] = useState([]);
  const [facilities, setFacilities] = useState([]);
  const [referrals, setReferrals] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [loading, setLoading] = useState({ users: false, facilities: false, referrals: false });
  const [error, setError] = useState({ users: '', facilities: '', referrals: '' });
  const [showUserForm, setShowUserForm] = useState(false);
  const [editingUserId, setEditingUserId] = useState(null);
  const [userForm, setUserForm] = useState(emptyUserForm);
  const [formMessage, setFormMessage] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const summary = useMemo(() => {
    const today = new Date().toISOString().slice(0, 10);
    const weekAgo = new Date();
    weekAgo.setDate(weekAgo.getDate() - 6);

    const statusOf = (ref) => formatStatus(ref);
    const isToday = (ref) => (ref.created_at || '').slice(0, 10) === today;
    const isThisWeek = (ref) => {
      const createdAt = new Date(ref.created_at);
      return !Number.isNaN(createdAt.getTime()) && createdAt >= weekAgo;
    };

    return {
      total_users: users.length,
      active_users: users.filter((u) => u.is_active).length,
      admin_count: users.filter((u) => u.role === 'admin').length,
      co_count: users.filter((u) => u.role === 'co').length,
      receptionist_count: users.filter((u) => u.role === 'receptionist').length,
      total_referrals: referrals.length,
      accepted_today: referrals.filter((ref) => isToday(ref) && statusOf(ref) === 'accepted').length,
      rejected_today: referrals.filter((ref) => isToday(ref) && statusOf(ref) === 'rejected').length,
      completed_this_week: referrals.filter((ref) => isThisWeek(ref) && statusOf(ref) === 'completed').length,
      accepted_this_week: referrals.filter((ref) => isThisWeek(ref) && statusOf(ref) === 'accepted').length,
      rejected_this_week: referrals.filter((ref) => isThisWeek(ref) && statusOf(ref) === 'rejected').length,
    };
  }, [users, referrals]);

  const fetchUsers = async () => {
    setLoading((prev) => ({ ...prev, users: true }));
    setError((prev) => ({ ...prev, users: '' }));
    try {
      const res = await fetch(apiUrl('/users/manage.php?action=list'), { credentials: 'include' });
      const data = await res.json();
      if (res.ok && data.success) {
        setUsers(data.users);
      } else {
        setError((prev) => ({ ...prev, users: data.error || 'Failed to load users.' }));
      }
    } catch (err) {
      setError((prev) => ({ ...prev, users: 'Unable to reach user service.' }));
    } finally {
      setLoading((prev) => ({ ...prev, users: false }));
    }
  };

  const fetchFacilities = async () => {
    setLoading((prev) => ({ ...prev, facilities: true }));
    setError((prev) => ({ ...prev, facilities: '' }));
    try {
      const res = await fetch(apiUrl('/facilities/manage_facilities.php'), { credentials: 'include' });
      const data = await res.json();
      if (res.ok && data.success) {
        setFacilities(data.facilities);
      } else {
        setError((prev) => ({ ...prev, facilities: data.error || 'Failed to load facilities.' }));
      }
    } catch (err) {
      setError((prev) => ({ ...prev, facilities: 'Unable to reach facilities service.' }));
    } finally {
      setLoading((prev) => ({ ...prev, facilities: false }));
    }
  };

  const fetchReferrals = async () => {
    setLoading((prev) => ({ ...prev, referrals: true }));
    setError((prev) => ({ ...prev, referrals: '' }));
    try {
      const res = await fetch(apiUrl('/referrals/list.php'), { credentials: 'include' });
      const data = await res.json();
      if (res.ok && data.success) {
        setReferrals(data.referrals);
      } else {
        setError((prev) => ({ ...prev, referrals: data.error || 'Failed to load referral overview.' }));
      }
    } catch (err) {
      setError((prev) => ({ ...prev, referrals: 'Unable to reach referral service.' }));
    } finally {
      setLoading((prev) => ({ ...prev, referrals: false }));
    }
  };

  const fetchDepartments = async (facilityId) => {
    if (!facilityId) {
      setDepartments([]);
      return;
    }

    try {
      const res = await fetch(apiUrl(`/departments/list.php?facility_id=${facilityId}`), { credentials: 'include' });
      const data = await res.json();
      if (res.ok && data.success) {
        setDepartments(data.departments);
      } else {
        setDepartments([]);
      }
    } catch (err) {
      setDepartments([]);
    }
  };

  useEffect(() => {
    if (activeTab === 'overview') {
      fetchUsers();
      fetchFacilities();
      fetchReferrals();
    }
    if (activeTab === 'users') {
      fetchUsers();
      fetchFacilities();
    }
    if (activeTab === 'facilities') {
      fetchFacilities();
    }
  }, [activeTab]);

  useEffect(() => {
    setContentVisible(false);
    const timeout = setTimeout(() => setContentVisible(true), 120);
    return () => clearTimeout(timeout);
  }, [activeTab]);

  useEffect(() => {
    if (userForm.role === 'co') {
      fetchDepartments(userForm.facility_id);
    } else {
      setDepartments([]);
    }
  }, [userForm.role, userForm.facility_id]);

  const signOut = async (e) => {
    e.preventDefault();
    await fetch(apiUrl('/auth/logout.php'), { method: 'POST', credentials: 'include' });
    window.location.href = '/';
  };

  const resetForm = () => {
    setEditingUserId(null);
    setUserForm(emptyUserForm);
    setDepartments([]);
    setFormMessage('');
  };

  const beginEdit = (user) => {
    setEditingUserId(user.id);
    setShowUserForm(true);
    setFormMessage('');
    setUserForm({
      first_name: user.first_name || '',
      last_name: user.last_name || '',
      email: user.email || '',
      password: '',
      role: user.role || 'co',
      facility_id: user.facility_id || '',
      phone: user.phone || '',
      license_number: user.license_number || '',
      department_id: user.department_id || '',
    });
  };

  const submitUser = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setFormMessage('');

    const payload = { ...userForm };
    if (editingUserId) {
      delete payload.password;
    }

    try {
      const endpoint = editingUserId
        ? `/users/manage.php?action=update&id=${editingUserId}`
        : '/users/manage.php?action=create';
      const res = await fetch(apiUrl(endpoint), {
        method: editingUserId ? 'PUT' : 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (res.ok && data.success) {
        setFormMessage(editingUserId ? 'User updated successfully.' : 'User created successfully.');
        setShowUserForm(false);
        resetForm();
        fetchUsers();
      } else {
        setFormMessage(data.error || 'Failed to save user.');
      }
    } catch (err) {
      setFormMessage('Unable to reach server.');
    } finally {
      setSubmitting(false);
    }
  };

  const deactivateUser = async (userId) => {
    const ok = window.confirm('Deactivate this user account?');
    if (!ok) return;

    try {
      const res = await fetch(apiUrl(`/users/manage.php?action=delete&id=${userId}`), {
        method: 'DELETE',
        credentials: 'include',
      });
      const data = await res.json();
      if (res.ok && data.success) {
        fetchUsers();
      } else {
        setError((prev) => ({ ...prev, users: data.error || 'Failed to deactivate user.' }));
      }
    } catch (err) {
      setError((prev) => ({ ...prev, users: 'Unable to reach user service.' }));
    }
  };

  return (
    <main className="container dashboard-page">
      <section className="section dashboard-section">
        <div className="nav-top">
          <div>
            <AdminNav activeTab={activeTab} onChange={setActiveTab} />
          </div>
          <a href="/" className="button-secondary" onClick={signOut}>Sign Out</a>
        </div>

        <div className="section-title">
          <span>{sectionMeta[activeTab].title}</span>
          <h2>{sectionMeta[activeTab].subtitle}</h2>
        </div>

        <div className={`dashboard-section-content ${contentVisible ? 'visible' : 'hidden'}`}>
          {activeTab === 'overview' && (
            <>
              <div className="dashboard-grid">
                <div className="dashboard-card highlight-card">
                  <span>Total Referrals</span>
                  <strong>{summary.total_referrals}</strong>
                </div>
                <div className="dashboard-card">
                  <span>Accepted Today</span>
                  <strong>{summary.accepted_today}</strong>
                </div>
                <div className="dashboard-card">
                  <span>Rejected Today</span>
                  <strong>{summary.rejected_today}</strong>
                </div>
                <div className="dashboard-card">
                  <span>Completed This Week</span>
                  <strong>{summary.completed_this_week}</strong>
                </div>
                <div className="dashboard-card">
                  <span>Facilities</span>
                  <strong>{facilities.length}</strong>
                </div>
                <div className="dashboard-card">
                  <span>Active Users</span>
                  <strong>{summary.active_users}</strong>
                </div>
              </div>

              <div className="table-card" style={{ marginTop: '1.5rem' }}>
                <h3>Weekly Referral Report</h3>
                {loading.referrals ? (
                  <p className="dashboard-message">Loading referral overview...</p>
                ) : error.referrals ? (
                  <p className="dashboard-message dashboard-error">{error.referrals}</p>
                ) : (
                  <table className="data-table">
                    <thead>
                      <tr>
                        <th>Metric</th>
                        <th>Count</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Accepted referrals this week</td>
                        <td>{summary.accepted_this_week}</td>
                      </tr>
                      <tr>
                        <td>Rejected referrals this week</td>
                        <td>{summary.rejected_this_week}</td>
                      </tr>
                      <tr>
                        <td>Completed referrals this week</td>
                        <td>{summary.completed_this_week}</td>
                      </tr>
                    </tbody>
                  </table>
                )}
              </div>
            </>
          )}

          {activeTab === 'users' && (
            <>
              <div className="form-card" style={{ marginBottom: '1.75rem' }}>
                <div className="nav-top" style={{ alignItems: 'center', justifyContent: 'space-between', marginBottom: '1rem' }}>
                  <div>
                    <h3 style={{ margin: 0 }}>{editingUserId ? 'Update User' : 'Register New User'}</h3>
                    <p style={{ margin: '0.5rem 0 0', color: '#475569' }}>
                      Admin manages user accounts and national referral oversight.
                    </p>
                  </div>
                  <button
                    type="button"
                    className="button button-secondary"
                    onClick={() => {
                      if (showUserForm) resetForm();
                      setShowUserForm(!showUserForm);
                    }}
                  >
                    {showUserForm ? 'Hide form' : '+ New User'}
                  </button>
                </div>

                {showUserForm && (
                  <form onSubmit={submitUser} className="login-form">
                    <div className="form-grid" style={{ gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
                      <label className="form-field">
                        <span>First Name *</span>
                        <input type="text" value={userForm.first_name}
                          onChange={(e) => setUserForm({ ...userForm, first_name: e.target.value })}
                          className="form-input" required />
                      </label>
                      <label className="form-field">
                        <span>Last Name *</span>
                        <input type="text" value={userForm.last_name}
                          onChange={(e) => setUserForm({ ...userForm, last_name: e.target.value })}
                          className="form-input" required />
                      </label>
                      <label className="form-field">
                        <span>Email *</span>
                        <input type="email" value={userForm.email}
                          onChange={(e) => setUserForm({ ...userForm, email: e.target.value })}
                          className="form-input" required />
                      </label>
                      {!editingUserId && (
                        <label className="form-field">
                          <span>Password *</span>
                          <input type="text" value={userForm.password}
                            onChange={(e) => setUserForm({ ...userForm, password: e.target.value })}
                            className="form-input" required placeholder="Temporary password" />
                        </label>
                      )}
                      <label className="form-field">
                        <span>Phone</span>
                        <input type="text" value={userForm.phone}
                          onChange={(e) => setUserForm({ ...userForm, phone: e.target.value })}
                          className="form-input" />
                      </label>
                      <label className="form-field">
                        <span>Role *</span>
                        <select value={userForm.role}
                          onChange={(e) => setUserForm({ ...userForm, role: e.target.value, license_number: '', department_id: '', facility_id: e.target.value === 'admin' ? '' : userForm.facility_id })}
                          className="form-input" required>
                          <option value="co">CO / Doctor</option>
                          <option value="receptionist">Receptionist</option>
                          <option value="admin">Admin</option>
                        </select>
                      </label>
                      {userForm.role !== 'admin' && (
                        <label className="form-field">
                          <span>Facility *</span>
                          <select value={userForm.facility_id}
                            onChange={(e) => setUserForm({ ...userForm, facility_id: e.target.value, department_id: '' })}
                            className="form-input" required>
                            <option value="">Choose facility</option>
                            {facilities.map((f) => (
                              <option key={f.id} value={f.id}>{f.name} ({f.region})</option>
                            ))}
                          </select>
                        </label>
                      )}
                      {userForm.role === 'co' && (
                        <>
                          <label className="form-field">
                            <span>Department *</span>
                            <select value={userForm.department_id}
                              onChange={(e) => setUserForm({ ...userForm, department_id: e.target.value })}
                              className="form-input" required>
                              <option value="">Choose department</option>
                              {departments.map((department) => (
                                <option key={department.id} value={department.id}>{department.name}</option>
                              ))}
                            </select>
                          </label>
                          <label className="form-field">
                            <span>License Number *</span>
                            <input type="text" value={userForm.license_number}
                              onChange={(e) => setUserForm({ ...userForm, license_number: e.target.value })}
                              className="form-input" required />
                          </label>
                        </>
                      )}
                    </div>
                    {formMessage && <p className="dashboard-message" style={{ marginBottom: '1rem' }}>{formMessage}</p>}
                    <button type="submit" className="button" disabled={submitting}>
                      {submitting ? 'Saving...' : editingUserId ? 'Update User' : 'Register User'}
                    </button>
                  </form>
                )}
              </div>

              <div className="table-card">
                {loading.users ? (
                  <p className="dashboard-message">Loading users...</p>
                ) : error.users ? (
                  <p className="dashboard-message dashboard-error">{error.users}</p>
                ) : users.length === 0 ? (
                  <p>No users registered yet.</p>
                ) : (
                  <table className="data-table">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Facility</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {users.map((u) => (
                        <tr key={u.id}>
                          <td>{u.id}</td>
                          <td><strong>{u.first_name} {u.last_name}</strong></td>
                          <td>{u.email}</td>
                          <td><span className="status-pill" style={{ backgroundColor: '#e2e8f0' }}>{u.role}</span></td>
                          <td>{u.facility || 'N/A'}</td>
                          <td>{u.department || '-'}</td>
                          <td>
                            <span className={`status-pill ${u.is_active ? 'status-accepted' : 'status-rejected'}`}>
                              {u.is_active ? 'Active' : 'Inactive'}
                            </span>
                          </td>
                          <td>
                            <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                              <button type="button" className="table-icon-button" onClick={() => beginEdit(u)} title="Edit user">
                                <i className="fa-solid fa-pen-to-square"></i>
                              </button>
                              {u.is_active && (
                                <button type="button" className="table-icon-button" onClick={() => deactivateUser(u.id)} title="Deactivate user">
                                  <i className="fa-solid fa-user-slash"></i>
                                </button>
                              )}
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </div>
            </>
          )}

          {activeTab === 'facilities' && (
            loading.facilities ? (
              <p className="dashboard-message">Loading facilities...</p>
            ) : error.facilities ? (
              <p className="dashboard-message dashboard-error">{error.facilities}</p>
            ) : (
              <div className="grid-card facility-grid">
                {facilities.length === 0 ? (
                  <p>No facilities available.</p>
                ) : (
                  facilities.map((f) => (
                    <div key={f.id} className="facility-card">
                      <div className="facility-header">
                        <h3>{f.name}</h3>
                        <span className="tier-tag">{f.tier}</span>
                      </div>
                      <p>{f.region}, {f.district}</p>
                      <p>{f.address}</p>
                      <p>{f.phone} - {f.email}</p>
                      <p><strong>Capacity:</strong> {f.capacity}</p>
                    </div>
                  ))
                )}
              </div>
            )
          )}
        </div>
      </section>
    </main>
  );
}
